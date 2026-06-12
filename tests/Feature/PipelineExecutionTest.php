<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\NewsSource;
use App\Models\RawArticle;
use App\Models\MarketImpact;
use App\Models\Article;
use App\Jobs\AnalyzeMarketImpactJob;
use Tests\TestCase;

class PipelineExecutionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the full automation pipeline.
     */
    public function test_full_pipeline_execution_for_high_impact_news()
    {
        $source = NewsSource::create([
            'name' => 'Test Feed',
            'url' => 'https://example.com/rss',
            'type' => 'financial'
        ]);

        $rawNews = RawArticle::create([
            'news_source_id' => $source->id,
            'title' => 'Inflation rise reaches 40-year high in US CPI release',
            'url' => 'https://example.com/rss/inflation-high',
            'body' => 'Consumer Price Index rose by 8.5 percent in March compared to a year ago, sparking central bank interest rate pressure.',
            'published_at' => now(),
            'status' => 'pending'
        ]);

        AnalyzeMarketImpactJob::dispatchSync($rawNews, null);

        $this->assertEquals('analyzed', $rawNews->fresh()->status);

        $this->assertDatabaseHas('market_impacts', [
            'raw_article_id' => $rawNews->id,
            'sentiment' => 'bullish',
            'impact_level' => 'high'
        ]);

        $impact = MarketImpact::where('raw_article_id', $rawNews->id)->first();

        $this->assertDatabaseHas('articles', [
            'market_impact_id' => $impact->id,
            'status' => 'published'
        ]);

        $article = Article::where('market_impact_id', $impact->id)->first();

        $this->assertDatabaseHas('featured_images', [
            'article_id' => $article->id
        ]);

        $this->assertFileExists(public_path('sitemap.xml'));
    }
}
