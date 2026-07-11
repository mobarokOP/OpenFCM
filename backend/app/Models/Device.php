<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Device extends Model
{
    use HasUuids;

    protected $fillable = [
        'application_id', 'user_id', 'external_id', 'fcm_token', 'platform',
        'app_version', 'os_version', 'language', 'country', 'timezone',
        'notification_permission', 'active', 'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'notification_permission' => 'boolean',
            'active' => 'boolean',
            'last_active_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'device_tags')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(Topic::class, 'topic_subscriptions')
            ->withTimestamps();
    }

    public function getMaskedTokenAttribute(): string
    {
        $t = $this->fcm_token;

        return strlen($t) > 12 ? substr($t, 0, 6).'…'.substr($t, -4) : '••••';
    }
}
