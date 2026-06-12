<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EconomicIndicator extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'frequency',
        'description',
        'default_country',
        'default_importance',
        'keywords',
        'is_active',
    ];

    protected $casts = [
        'keywords' => 'array',
        'is_active' => 'boolean',
    ];

    // ──────────────────────────────────────────────
    // Indicator Registry: the 9 tracked indicators
    // ──────────────────────────────────────────────

    public const INDICATORS = [
        'cpi' => [
            'code' => 'cpi',
            'name' => 'Consumer Price Index (CPI)',
            'category' => 'inflation',
            'unit' => '%',
            'frequency' => 'monthly',
            'description' => 'Measures changes in the price level of a market basket of consumer goods and services. The most widely watched inflation indicator.',
            'default_country' => 'USD',
            'default_importance' => 'high',
            'keywords' => ['cpi', 'consumer price', 'inflation rate', 'inflation yoY'],
        ],
        'core_cpi' => [
            'code' => 'core_cpi',
            'name' => 'Core CPI',
            'category' => 'inflation',
            'unit' => '%',
            'frequency' => 'monthly',
            'description' => 'CPI excluding food and energy prices. Provides a clearer view of underlying inflation trends.',
            'default_country' => 'USD',
            'default_importance' => 'high',
            'keywords' => ['core cpi', 'core inflation', 'cpi ex food', 'cpi ex-energy'],
        ],
        'nfp' => [
            'code' => 'nfp',
            'name' => 'Non-Farm Payrolls (NFP)',
            'category' => 'employment',
            'unit' => 'K',
            'frequency' => 'monthly',
            'description' => 'Measures the change in the number of people employed during the previous month, excluding the farming industry. The most important employment indicator.',
            'default_country' => 'USD',
            'default_importance' => 'high',
            'keywords' => ['non-farm', 'nonfarm', 'nfp', 'payrolls', 'employment change'],
        ],
        'gdp' => [
            'code' => 'gdp',
            'name' => 'Gross Domestic Product (GDP)',
            'category' => 'growth',
            'unit' => '%',
            'frequency' => 'quarterly',
            'description' => 'Measures the total value of goods and services produced. The broadest measure of economic activity.',
            'default_country' => 'USD',
            'default_importance' => 'high',
            'keywords' => ['gdp', 'gross domestic', 'economic growth'],
        ],
        'ppi' => [
            'code' => 'ppi',
            'name' => 'Producer Price Index (PPI)',
            'category' => 'inflation',
            'unit' => '%',
            'frequency' => 'monthly',
            'description' => 'Measures the average change over time in the selling prices received by domestic producers. A leading indicator of consumer inflation.',
            'default_country' => 'USD',
            'default_importance' => 'medium',
            'keywords' => ['ppi', 'producer price', 'wholesale inflation', 'factory gate'],
        ],
        'pmi' => [
            'code' => 'pmi',
            'name' => 'Purchasing Managers Index (PMI)',
            'category' => 'growth',
            'unit' => '',
            'frequency' => 'monthly',
            'description' => 'An index of the prevailing direction of economic trends in manufacturing and services. Above 50 indicates expansion, below 50 contraction.',
            'default_country' => 'USD',
            'default_importance' => 'high',
            'keywords' => ['pmi', 'purchasing managers', 'manufacturing pmi', 'services pmi', 'ism manufacturing', 'ism services'],
        ],
        'interest_rate' => [
            'code' => 'interest_rate',
            'name' => 'Interest Rate Decision',
            'category' => 'monetary_policy',
            'unit' => '%',
            'frequency' => 'varies',
            'description' => 'Central bank interest rate decisions (FOMC, ECB, BoE, etc.). The single most impactful event for currency markets.',
            'default_country' => 'USD',
            'default_importance' => 'high',
            'keywords' => ['interest rate', 'fomc', 'federal funds', 'rate decision', 'rate hike', 'rate cut', 'ecb rate', 'bank rate', 'monetary policy'],
        ],
        'retail_sales' => [
            'code' => 'retail_sales',
            'name' => 'Retail Sales',
            'category' => 'spending',
            'unit' => '%',
            'frequency' => 'monthly',
            'description' => 'Measures the total receipts at stores that sell merchandise and related services to final consumers. A key gauge of consumer spending.',
            'default_country' => 'USD',
            'default_importance' => 'medium',
            'keywords' => ['retail sales', 'consumer spending', 'retail trade'],
        ],
        'unemployment_claims' => [
            'code' => 'unemployment_claims',
            'name' => 'Unemployment Claims',
            'category' => 'employment',
            'unit' => 'K',
            'frequency' => 'weekly',
            'description' => 'Initial jobless claims measuring the number of individuals who filed for unemployment insurance for the first time. A timely labor market indicator.',
            'default_country' => 'USD',
            'default_importance' => 'medium',
            'keywords' => ['unemployment claims', 'jobless claims', 'initial claims', 'unemployment rate'],
        ],
    ];

    public const CATEGORIES = [
        'inflation' => 'Inflation',
        'employment' => 'Employment',
        'growth' => 'Growth',
        'monetary_policy' => 'Monetary Policy',
        'spending' => 'Consumer Spending',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function economicEvents(): HasMany
    {
        return $this->hasMany(EconomicEvent::class, 'indicator_type', 'code');
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst(str_replace('_', ' ', $this->category));
    }

    /**
     * Seed all 9 indicator definitions into the database.
     */
    public static function seedDefaults(): int
    {
        $count = 0;
        foreach (self::INDICATORS as $code => $data) {
            $created = self::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'unit' => $data['unit'],
                    'frequency' => $data['frequency'],
                    'description' => $data['description'],
                    'default_country' => $data['default_country'],
                    'default_importance' => $data['default_importance'],
                    'keywords' => $data['keywords'],
                    'is_active' => true,
                ]
            );
            if ($created->wasRecentlyCreated) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Classify an event name to an indicator code.
     */
    public static function classify(string $eventName): ?string
    {
        $lower = strtolower($eventName);

        foreach (self::INDICATORS as $code => $data) {
            foreach ($data['keywords'] as $keyword) {
                if (str_contains($lower, strtolower($keyword))) {
                    return $code;
                }
            }
        }

        return null;
    }
}
