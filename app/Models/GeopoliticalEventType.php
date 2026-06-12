<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeopoliticalEventType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'default_severity',
        'keywords',
        'affected_markets',
        'risk_multipliers',
    ];

    protected $casts = [
        'keywords' => 'array',
        'affected_markets' => 'array',
        'risk_multipliers' => 'array',
    ];

    // ──────────────────────────────────────────────
    // 7 Tracked Geopolitical Event Types
    // ──────────────────────────────────────────────

    public const EVENT_TYPES = [
        'war' => [
            'code' => 'war',
            'name' => 'War',
            'category' => 'conflict',
            'description' => 'Full-scale armed conflict between nations or large-scale civil war with significant regional destabilization.',
            'default_severity' => 'critical',
            'keywords' => ['war', 'invasion', 'military offensive', 'armed conflict', 'ground troops', 'airstrike campaign', 'occupation', 'annexation', 'conquest'],
            'affected_markets' => ['defense', 'energy', 'commodities', 'forex', 'equities'],
            'risk_multipliers' => ['low' => 1.5, 'medium' => 2.5, 'high' => 4.0, 'critical' => 6.0],
        ],
        'military_escalation' => [
            'code' => 'military_escalation',
            'name' => 'Military Escalation',
            'category' => 'conflict',
            'description' => 'Troop mobilization, border skirmishes, naval confrontations, or proxy conflicts that risk wider escalation.',
            'default_severity' => 'high',
            'keywords' => ['military escalation', 'troop buildup', 'border clash', 'naval confrontation', 'missile test', 'military drill', 'proxy war', 'skirmish', 'mobilization', 'deployment', 'provocation', 'standoff'],
            'affected_markets' => ['defense', 'energy', 'forex', 'commodities'],
            'risk_multipliers' => ['low' => 1.2, 'medium' => 2.0, 'high' => 3.0, 'critical' => 4.5],
        ],
        'trade_war' => [
            'code' => 'trade_war',
            'name' => 'Trade War',
            'category' => 'trade',
            'description' => 'Escalating tariffs, trade barriers, decoupling policies, or economic warfare between major trading partners.',
            'default_severity' => 'high',
            'keywords' => ['trade war', 'tariff', 'trade barrier', 'import ban', 'export control', 'sanctions trade', 'decoupling', 'trade dispute', 'retaliatory tariff', 'trade restriction', 'embargo', 'anti-dumping'],
            'affected_markets' => ['equities', 'forex', 'commodities', 'supply_chain'],
            'risk_multipliers' => ['low' => 1.0, 'medium' => 1.8, 'high' => 2.8, 'critical' => 4.0],
        ],
        'sanctions' => [
            'code' => 'sanctions',
            'name' => 'Sanctions',
            'category' => 'trade',
            'description' => 'Economic, financial, or diplomatic sanctions imposed on nations, entities, or individuals by governments or international bodies.',
            'default_severity' => 'high',
            'keywords' => ['sanctions', 'economic sanctions', 'financial sanctions', 'asset freeze', 'travel ban', 'SWIFT ban', 'oil sanctions', 'arms embargo', 'restrictive measures', 'OFAC', 'EU sanctions'],
            'affected_markets' => ['energy', 'forex', 'commodities', 'equities'],
            'risk_multipliers' => ['low' => 1.0, 'medium' => 1.8, 'high' => 2.5, 'critical' => 3.5],
        ],
        'energy_crisis' => [
            'code' => 'energy_crisis',
            'name' => 'Energy Crisis',
            'category' => 'energy',
            'description' => 'Supply disruptions, price shocks, pipeline closures, OPEC+ decisions, or infrastructure attacks affecting global energy markets.',
            'default_severity' => 'high',
            'keywords' => ['energy crisis', 'oil crisis', 'gas crisis', 'pipeline', 'OPEC', 'oil supply cut', 'energy shortage', 'blackout', 'power grid', 'refinery attack', 'energy disruption', 'fuel shortage', 'gas cutoff', 'oil embargo', 'production cut'],
            'affected_markets' => ['energy', 'commodities', 'forex', 'equities', 'bonds'],
            'risk_multipliers' => ['low' => 1.2, 'medium' => 2.0, 'high' => 3.2, 'critical' => 5.0],
        ],
        'political_election' => [
            'code' => 'political_election',
            'name' => 'Political Election',
            'category' => 'political',
            'description' => 'National elections, referendums, coups, or regime changes that shift geopolitical alliances and economic policies.',
            'default_severity' => 'medium',
            'keywords' => ['election', 'referendum', 'vote', 'coup', 'regime change', 'political transition', 'leadership change', 'impeachment', 'resignation', 'presidential election', 'parliamentary election', 'snap election', 'political crisis'],
            'affected_markets' => ['forex', 'equities', 'bonds'],
            'risk_multipliers' => ['low' => 0.8, 'medium' => 1.5, 'high' => 2.5, 'critical' => 3.5],
        ],
        'banking_crisis' => [
            'code' => 'banking_crisis',
            'name' => 'Banking Crisis',
            'category' => 'financial',
            'description' => 'Bank failures, systemic liquidity crises, sovereign debt defaults, or contagion risks across financial institutions.',
            'default_severity' => 'high',
            'keywords' => ['banking crisis', 'bank failure', 'bank run', 'liquidity crisis', 'sovereign default', 'bailout', 'credit crisis', 'financial contagion', 'systemic risk', 'insolvency', 'bank collapse', 'deposit freeze', 'central bank emergency'],
            'affected_markets' => ['forex', 'bonds', 'equities', 'commodities'],
            'risk_multipliers' => ['low' => 1.2, 'medium' => 2.2, 'high' => 3.5, 'critical' => 5.0],
        ],
    ];

    public const CATEGORIES = [
        'conflict' => 'Military Conflict',
        'trade' => 'Trade & Sanctions',
        'political' => 'Political',
        'financial' => 'Financial',
        'energy' => 'Energy',
    ];

    public const REGIONS = [
        'middle_east' => 'Middle East',
        'europe' => 'Europe',
        'asia_pacific' => 'Asia Pacific',
        'americas' => 'Americas',
        'africa' => 'Africa',
        'global' => 'Global',
    ];

    public const SEVERITY_LEVELS = [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
        'critical' => 4,
    ];

    public const ESCALATION_LEVELS = [
        0 => 'New',
        1 => 'Monitoring',
        2 => 'Escalating',
        3 => 'Critical',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function geopoliticalEvents(): HasMany
    {
        return $this->hasMany(GeopoliticalEvent::class, 'event_type', 'code');
    }

    // ──────────────────────────────────────────────
    // Classification
    // ──────────────────────────────────────────────

    /**
     * Classify a text (title + description) into a geopolitical event type.
     * Returns the event type code or null if no match.
     */
    public static function classify(string $text): ?string
    {
        $text = strtolower($text);
        $bestMatch = null;
        $bestScore = 0;

        foreach (self::EVENT_TYPES as $code => $type) {
            $score = 0;
            foreach ($type['keywords'] as $keyword) {
                if (str_contains($text, strtolower($keyword))) {
                    // Longer keyword matches are more specific
                    $score += strlen($keyword);
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $code;
            }
        }

        return $bestMatch;
    }

    /**
     * Classify with confidence: returns ['type' => string|null, 'confidence' => float, 'scores' => array]
     */
    public static function classifyWithConfidence(string $text): array
    {
        $text = strtolower($text);
        $scores = [];

        foreach (self::EVENT_TYPES as $code => $type) {
            $score = 0;
            foreach ($type['keywords'] as $keyword) {
                if (str_contains($text, strtolower($keyword))) {
                    $score += strlen($keyword);
                }
            }
            $scores[$code] = $score;
        }

        arsort($scores);
        $maxScore = max($scores);
        $totalScore = array_sum($scores);

        $bestType = $maxScore > 0 ? array_key_first($scores) : null;
        $confidence = $totalScore > 0 ? round(($maxScore / $totalScore) * 100, 1) : 0;

        return [
            'type' => $bestType,
            'confidence' => $confidence,
            'scores' => $scores,
        ];
    }

    /**
     * Seed the 7 default event types into the database.
     */
    public static function seedDefaults(): int
    {
        $count = 0;
        foreach (self::EVENT_TYPES as $code => $type) {
            self::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $type['name'],
                    'category' => $type['category'],
                    'description' => $type['description'],
                    'default_severity' => $type['default_severity'],
                    'keywords' => $type['keywords'],
                    'affected_markets' => $type['affected_markets'],
                    'risk_multipliers' => $type['risk_multipliers'],
                ]
            );
            $count++;
        }
        return $count;
    }

    /**
     * Get the risk multiplier for a given event type and severity.
     */
    public function getRiskMultiplier(string $severity): float
    {
        return $this->risk_multipliers[$severity] ?? 1.0;
    }
}
