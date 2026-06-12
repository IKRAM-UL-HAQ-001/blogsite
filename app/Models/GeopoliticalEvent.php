<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GeopoliticalEvent extends Model
{
    protected $fillable = [
        'title',
        'description',
        'event_type',
        'severity',
        'status',
        'countries',
        'region',
        'primary_country',
        'occurred_at',
        'escalated_at',
        'resolved_at',
        'raw_article_id',
        'news_source_id',
        'source_url',
        'ai_sentiment',
        'ai_confidence_score',
        'ai_impact_level',
        'ai_affected_assets',
        'ai_market_summary',
        'ai_risk_factors',
        'ai_geopolitical_analysis',
        'ai_timeline_projection',
        'ai_historical_parallels',
        'parent_event_id',
        'related_event_ids',
        'escalation_level',
    ];

    protected $casts = [
        'countries' => 'array',
        'occurred_at' => 'datetime',
        'escalated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'ai_confidence_score' => 'decimal:2',
        'ai_affected_assets' => 'array',
        'ai_risk_factors' => 'array',
        'ai_timeline_projection' => 'array',
        'ai_historical_parallels' => 'array',
        'related_event_ids' => 'array',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(GeopoliticalEventType::class, 'event_type', 'code');
    }

    public function rawArticle(): BelongsTo
    {
        return $this->belongsTo(RawArticle::class);
    }

    public function newsSource(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class);
    }

    public function parentEvent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_event_id');
    }

    public function childEvents(): HasMany
    {
        return $this->hasMany(self::class, 'parent_event_id');
    }

    public function marketImpact(): HasOne
    {
        return $this->hasOne(MarketImpact::class);
    }

    public function countryInvolvements()
    {
        return \Illuminate\Support\Facades\DB::table('geopolitical_event_country')
            ->where('geopolitical_event_id', $this->id);
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeByType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByRegion($query, string $region)
    {
        return $query->where('region', $region);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeClassified($query)
    {
        return $query->where('status', 'classified');
    }

    public function scopeAnalyzed($query)
    {
        return $query->where('status', 'analyzed');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', ['high', 'critical']);
    }

    public function scopeEscalating($query)
    {
        return $query->where('escalation_level', '>=', 2);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days))->orderBy('occurred_at', 'desc');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('occurred_at', now()->toDateString());
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['resolved', 'archived']);
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeByCategory($query, string $category)
    {
        $typesInCategory = collect(GeopoliticalEventType::EVENT_TYPES)
            ->where('category', $category)
            ->keys()
            ->toArray();

        return $query->whereIn('event_type', $typesInCategory);
    }

    // ──────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────

    public function getEventTypeLabelAttribute(): string
    {
        if ($this->event_type && isset(GeopoliticalEventType::EVENT_TYPES[$this->event_type])) {
            return GeopoliticalEventType::EVENT_TYPES[$this->event_type]['name'];
        }
        return 'Unclassified';
    }

    public function getEventCategoryAttribute(): string
    {
        if ($this->event_type && isset(GeopoliticalEventType::EVENT_TYPES[$this->event_type])) {
            return GeopoliticalEventType::EVENT_TYPES[$this->event_type]['category'];
        }
        return 'other';
    }

    public function getCategoryLabelAttribute(): string
    {
        $category = $this->event_category;
        return GeopoliticalEventType::CATEGORIES[$category] ?? ucfirst($category);
    }

    public function getRegionLabelAttribute(): string
    {
        return GeopoliticalEventType::REGIONS[$this->region] ?? ($this->region ?? 'Unknown');
    }

    public function getSeverityBadgeAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'secondary',
            default => 'secondary',
        };
    }

    public function getSeverityWeightAttribute(): int
    {
        return GeopoliticalEventType::SEVERITY_LEVELS[$this->severity] ?? 0;
    }

    public function getEscalationLabelAttribute(): string
    {
        return GeopoliticalEventType::ESCALATION_LEVELS[$this->escalation_level] ?? 'Unknown';
    }

    public function getIsCriticalAttribute(): bool
    {
        return in_array($this->severity, ['high', 'critical']);
    }

    public function getIsEscalatingAttribute(): bool
    {
        return $this->escalation_level >= 2;
    }

    public function getRiskScoreAttribute(): float
    {
        $severityWeight = $this->severity_weight;
        $escalationMultiplier = 1 + ($this->escalation_level * 0.25);
        $confidenceMultiplier = $this->ai_confidence_score ? ($this->ai_confidence_score / 100) : 0.5;

        // Get type-specific risk multiplier
        $typeMultiplier = 1.0;
        if ($this->event_type && isset(GeopoliticalEventType::EVENT_TYPES[$this->event_type])) {
            $riskMultipliers = GeopoliticalEventType::EVENT_TYPES[$this->event_type]['risk_multipliers'] ?? [];
            $typeMultiplier = $riskMultipliers[$this->severity] ?? 1.0;
        }

        return round($severityWeight * $escalationMultiplier * $confidenceMultiplier * $typeMultiplier, 2);
    }

    public function getDurationDaysAttribute(): ?int
    {
        if (!$this->occurred_at) {
            return null;
        }
        $end = $this->resolved_at ?? now();
        return $this->occurred_at->diffInDays($end);
    }

    // ──────────────────────────────────────────────
    // Business Logic
    // ──────────────────────────────────────────────

    /**
     * Classify this event by type from its title and description.
     */
    public function classify(): self
    {
        $text = $this->title . ' ' . ($this->description ?? '');
        $result = GeopoliticalEventType::classifyWithConfidence($text);

        if ($result['type']) {
            $this->event_type = $result['type'];

            // Override severity from type defaults if still at default
            $typeDef = GeopoliticalEventType::EVENT_TYPES[$result['type']] ?? null;
            if ($typeDef && $this->severity === 'medium') {
                $this->severity = $typeDef['default_severity'];
            }

            if ($this->status === 'pending') {
                $this->status = 'classified';
            }
        }

        return $this;
    }

    /**
     * Detect the region from the event text and countries.
     */
    public function detectRegion(): self
    {
        $text = strtolower($this->title . ' ' . ($this->description ?? ''));

        $regionKeywords = [
            'middle_east' => ['iran', 'iraq', 'israel', 'saudi', 'syria', 'yemen', 'lebanon', 'gaza', 'palestin', 'uae', 'qatar', 'kuwait', 'turkey', 'turkiye', 'opec'],
            'europe' => ['eu ', 'europe', 'germany', 'france', 'uk ', 'britain', 'ukraine', 'russia', 'nato', 'brexit', 'ecb'],
            'asia_pacific' => ['china', 'japan', 'korea', 'taiwan', 'india', 'australia', 'asean', 'south china sea', 'philippines', 'vietnam'],
            'americas' => ['us ', 'usa', 'america', 'brazil', 'mexico', 'canada', 'argentina', 'fed ', 'federal reserve'],
            'africa' => ['africa', 'nigeria', 'egypt', 'south africa', 'sahel', 'libya', 'sudan', 'ethiopia'],
        ];

        $bestRegion = null;
        $bestScore = 0;

        foreach ($regionKeywords as $region => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRegion = $region;
            }
        }

        // Check countries array
        if (!$bestRegion && $this->countries) {
            $countryToRegion = [
                'IR' => 'middle_east', 'IQ' => 'middle_east', 'IL' => 'middle_east',
                'SA' => 'middle_east', 'SY' => 'middle_east', 'AE' => 'middle_east',
                'DE' => 'europe', 'FR' => 'europe', 'GB' => 'europe', 'UA' => 'europe',
                'RU' => 'europe', 'CN' => 'asia_pacific', 'JP' => 'asia_pacific',
                'KR' => 'asia_pacific', 'IN' => 'asia_pacific', 'TW' => 'asia_pacific',
                'US' => 'americas', 'BR' => 'americas', 'MX' => 'americas', 'CA' => 'americas',
            ];
            foreach ($this->countries as $code) {
                if (isset($countryToRegion[$code])) {
                    $bestRegion = $countryToRegion[$code];
                    break;
                }
            }
        }

        if ($bestRegion) {
            $this->region = $bestRegion;
        }

        return $this;
    }

    /**
     * Escalate this event to a higher level.
     */
    public function escalate(int $level = null, string $reason = null): self
    {
        $newLevel = $level ?? min($this->escalation_level + 1, 3);
        $this->escalation_level = $newLevel;

        if ($newLevel >= 2 && !$this->escalated_at) {
            $this->escalated_at = now();
        }

        // Auto-upgrade severity for critical escalations
        if ($newLevel === 3 && $this->severity !== 'critical') {
            $this->severity = 'critical';
        }

        $this->save();

        return $this;
    }

    /**
     * Process: classify, detect region, save.
     */
    public function process(): self
    {
        if (empty($this->event_type)) {
            $this->classify();
        }

        if (empty($this->region)) {
            $this->detectRegion();
        }

        $this->save();

        return $this;
    }

    /**
     * Mark as resolved.
     */
    public function resolve(): self
    {
        $this->resolved_at = now();
        $this->status = 'resolved';
        $this->save();

        return $this;
    }
}
