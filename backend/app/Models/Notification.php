<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Notification extends Model
{
    use HasUuids;

    protected $fillable = [
        'application_id', 'title', 'body', 'image_url', 'large_icon', 'small_icon',
        'deep_link', 'data', 'ttl', 'priority', 'collapse_key', 'channel_id',
        'audience', 'status', 'recipients_count', 'sent_count', 'delivered_count',
        'failed_count', 'opened_count', 'scheduled_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'audience' => 'array',
            'ttl' => 'integer',
            'recipients_count' => 'integer',
            'sent_count' => 'integer',
            'delivered_count' => 'integer',
            'failed_count' => 'integer',
            'opened_count' => 'integer',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(NotificationTarget::class);
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(DeliveryLog::class);
    }

    public function schedule(): HasOne
    {
        return $this->hasOne(Schedule::class);
    }

    public function getCtrAttribute(): float
    {
        return $this->delivered_count > 0
            ? round($this->opened_count / $this->delivered_count * 100, 2)
            : 0.0;
    }
}
