<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeRequestInputs
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->merge($this->sanitize($request->all()));

        return $next($request);
    }

    protected function sanitize(array $input): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return $this->sanitize($value);
            }

            if (! is_string($value)) {
                return $value;
            }

            $value = preg_replace('/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is', '', $value);
            $value = preg_replace('/<[^>]+on\w+\s*=\s*"[^"]*"/i', '', $value);
            $value = preg_replace('/<[^>]+on\w+\s*=\s*\'[^\']*\'/i', '', $value);
            $value = preg_replace('/javascript:\s*/i', '', $value);
            $value = strip_tags($value);

            return trim($value);
        }, $input);
    }
}
