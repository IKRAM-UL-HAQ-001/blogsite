<?php

namespace App\Jobs;

use App\Models\RawArticle;
use App\Models\EconomicEvent;
use App\Models\MarketImpact;
use App\Services\OpenAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeMarketImpactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?RawArticle $rawArticle;
    protected ?EconomicEvent $economicEvent;

    /**
     * Create a new job instance.
     */
    public function __construct(?RawArticle $rawArticle = null, ?EconomicEvent $economicEvent = null)
    {
        $this->rawArticle = $rawArticle;
        $this->economicEvent = $economicEvent;
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAIService $openAIService): void
    {
        $text = '';
        $type = '';

        if ($this->rawArticle) {
            $text = "Title: " . $this->rawArticle->title . "\nBody: " . $this->rawArticle->body;
            $type = 'news';
        } elseif ($this->economicEvent) {
            $text = "Event: " . $this->economicEvent->event_name . 
                    "\nCountry: " . $this->economicEvent->country . 
                    "\nActual: " . $this->economicEvent->actual . 
                    "\nForecast: " . $this->economicEvent->forecast . 
                    "\nPrevious: " . $this->economicEvent->previous;
            $type = 'economic calendar event';
        } else {
            Log::warning("AnalyzeMarketImpactJob run without input model.");
            return;
        }

        try {
            $analysis = $openAIService->analyzeSentiment($text, $type);

            $impact = MarketImpact::create([
                'raw_article_id' => $this->rawArticle?->id,
                'economic_event_id' => $this->economicEvent?->id,
                'sentiment' => $analysis['sentiment'],
                'score' => $analysis['score'],
                'impact_level' => $analysis['impact_level'],
                'affected_assets' => $analysis['affected_assets'],
                'market_summary' => $analysis['market_summary'],
            ]);

            if ($this->rawArticle) {
                $this->rawArticle->update(['status' => 'analyzed']);
            }
            if ($this->economicEvent) {
                $this->economicEvent->update(['status' => 'analyzed']);
            }

            Log::info("MarketImpact created ID: {$impact->id}. Impact Level: {$impact->impact_level}");

            // If it is high impact, trigger article generation
            if ($impact->impact_level === 'high') {
                GenerateArticleJob::dispatch($impact);
            }
        } catch (\Exception $e) {
            Log::error("Failed to run AnalyzeMarketImpactJob: " . $e->getMessage());
            if ($this->rawArticle) {
                $this->rawArticle->update(['status' => 'failed']);
            }
            if ($this->economicEvent) {
                $this->economicEvent->update(['status' => 'failed']);
            }
        }
    }
}
