<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IndexingService
{
    protected ?string $googleCredsPath;
    protected ?string $indexNowKey;
    protected ?string $indexNowKeyLocation;

    public function __construct()
    {
        $this->googleCredsPath = config('indexing.google_credentials_path') 
            ? base_path(config('indexing.google_credentials_path')) 
            : null;
        $this->indexNowKey = config('indexing.indexnow_key');
        $this->indexNowKeyLocation = config('indexing.indexnow_key_location');
    }

    /**
     * Submit URL to Google Indexing API.
     */
    public function submitToGoogle(string $url, string $type = 'URL_UPDATED'): bool
    {
        if (empty($this->googleCredsPath) || !file_exists($this->googleCredsPath)) {
            Log::info("Google Indexing: Credentials file not found. Running in MOCK mode.");
            return true;
        }

        try {
            $creds = json_decode(file_get_contents($this->googleCredsPath), true);
            if (!$creds || !isset($creds['private_key']) || !isset($creds['client_email'])) {
                Log::warning("Google Indexing: Invalid service account JSON schema.");
                return false;
            }

            $accessToken = $this->getGoogleAccessToken($creds);
            if (!$accessToken) {
                return false;
            }

            $response = Http::withToken($accessToken)
                ->contentType('application/json')
                ->post('https://indexing.googleapis.com/v3/urlNotifications:publish', [
                    'url' => $url,
                    'type' => $type
                ]);

            if ($response->successful()) {
                Log::info("Google Indexing: Successfully submitted URL: {$url}");
                return true;
            } else {
                Log::error("Google Indexing error: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Google Indexing Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Submit URL to Bing via IndexNow.
     */
    public function submitToBing(string $url): bool
    {
        if (empty($this->indexNowKey)) {
            Log::info("IndexNow: Key not configured in .env. Running in MOCK mode.");
            return true;
        }

        try {
            $host = parse_url($url, PHP_URL_HOST);

            $response = Http::contentType('application/json')
                ->post('https://api.indexnow.org/IndexNow', [
                    'host' => $host,
                    'key' => $this->indexNowKey,
                    'keyLocation' => $this->indexNowKeyLocation,
                    'urlList' => [$url]
                ]);

            if ($response->status() === 200 || $response->status() === 202) {
                Log::info("IndexNow: Successfully submitted URL: {$url}");
                return true;
            } else {
                Log::error("IndexNow error: Code " . $response->status() . " - " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("IndexNow Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Exchange Service Account JWT for OAuth Access Token.
     */
    protected function getGoogleAccessToken(array $creds): ?string
    {
        $now = time();
        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        
        $claimSet = json_encode([
            'iss' => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/indexing',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlClaimSet = $this->base64UrlEncode($claimSet);

        $signatureInput = $base64UrlHeader . "." . $base64UrlClaimSet;
        
        $signature = '';
        $privateKey = $creds['private_key'];
        
        if (!openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            Log::error("Google Indexing: Failed to sign JWT assertion.");
            return null;
        }

        $base64UrlSignature = $this->base64UrlEncode($signature);
        $jwt = $signatureInput . "." . $base64UrlSignature;

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'] ?? null;
        }

        Log::error("Google Indexing OAuth Token Exchange Failed: " . $response->body());
        return null;
    }

    protected function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
