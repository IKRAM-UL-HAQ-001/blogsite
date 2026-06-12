<?php

namespace App\Jobs;

use App\Models\GeopoliticalEvent;
use App\Models\MarketImpact;
use App\Services\OpenAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeGeopoliticalImpactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    protected int $eventId;

    public function __construct(GeopoliticalEvent $event)
    {
        $this->eventId = $event->id;
        $this->onQueue('processing');
    }

    public function handle(OpenAIService $openAIService): void
    {
        $event = GeopoliticalEvent::find($this->eventId);

        if (!$event) {
            Log::warning("AnalyzeGeopoliticalImpactJob: Event ID {$this->eventId} not found.");
            return;
        }

        Log::info("Analyzing geopolitical event ID {$event->id}: {$event->title}");

        try {
            // Build context for AI analysis
            $context = $this->buildAnalysisContext($event);

            // Get geopolitical analysis from OpenAI
            $analysis = $openAIService->analyzeGeopoliticalEvent($context);

            // Update the event with AI analysis results
            $event->update([
                'ai_sentiment' => $analysis['sentiment'] ?? null,
                'ai_confidence_score' => $analysis['confidence_score'] ?? null,
                'ai_impact_level' => $analysis['impact_level'] ?? null,
                'ai_affected_assets' => $analysis['affected_assets'] ?? null,
                'ai_market_summary' => $analysis['market_summary'] ?? null,
                'ai_risk_factors' => $analysis['risk_factors'] ?? null,
                'ai_geopolitical_analysis' => $analysis['geopolitical_analysis'] ?? null,
                'ai_timeline_projection' => $analysis['timeline_projection'] ?? null,
                'ai_historical_parallels' => $analysis['historical_parallels'] ?? null,
                'status' => 'analyzed',
            ]);

            // Create market impact record
            MarketImpact::create([
                'geopolitical_event_id' => $event->id,
                'sentiment' => $analysis['sentiment'] ?? 'neutral',
                'score' => $analysis['sentiment_score'] ?? 0,
                'impact_level' => $analysis['impact_level'] ?? 'low',
                'affected_assets' => $analysis['affected_assets'] ?? [],
                'market_summary' => $analysis['market_summary'] ?? '',
            ]);

            // Auto-escalate based on AI assessment
            if (($analysis['impact_level'] ?? '') === 'high' && $event->escalation_level < 2) {
                $event->escalate(2);
                Log::info("Auto-escalated geopolitical event ID {$event->id} based on AI analysis.");
            }

            // Trigger article generation for high-impact events
            if (($analysis['impact_level'] ?? '') === 'high') {
                $impact = $event->marketImpact;
                if ($impact) {
                    GenerateArticleJob::dispatch($impact);
                }
            }

            Log::info("Geopolitical event ID {$event->id} analyzed. Impact: " . ($analysis['impact_level'] ?? 'unknown'));

        } catch (\Exception $e) {
            Log::error("Failed to analyze geopolitical event ID {$event->id}: " . $e->getMessage());
            $event->update(['status' => 'failed']);
        }
    }

    /**
     * Build a rich context string for AI analysis.
     */
    protected function buildAnalysisContext(GeopoliticalEvent $event): string
    {
        $typeLabel = $event->event_type_label;
        $severity = ucfirst($event->severity);
        $region = $event->region_label;
        $countries = $event->countries ? implode(', ', $event->countries) : 'Unknown';
        $escalation = $event->escalation_label;
        $duration = $event->duration_days;

        $context = "GEOPOLITICAL EVENT ANALYSIS REQUEST\n\n";
        $context .= "Title: {$event->title}\n";
        $context .= "Type: {$typeLabel}\n";
        $context .= "Severity: {$severity}\n";
        $context .= "Region: {$region}\n";
        $context .= "Countries: {$countries}\n";
        $context .= "Escalation Level: {$escalation}\n";

        if ($duration !== null) {
            $context .= "Duration: {$duration} days\n";
        }

        if ($event->description) {
            $context .= "\nDescription: {$event->description}\n";
        }

        // Add parent event context if exists
        if ($event->parentEvent) {
            $context .= "\nParent Event: {$event->parentEvent->title} (Severity: {$event->parentEvent->severity})\n";
        }

        return $context;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("AnalyzeGeopoliticalImpactJob failed for event ID {$this->eventId}: " . $exception->getMessage());

        $event = GeopoliticalEvent::find($this->eventId);
        if ($event) {
            $event->update(['status' => 'failed']);
        }
    }
}
