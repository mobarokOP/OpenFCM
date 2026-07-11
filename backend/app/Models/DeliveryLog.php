<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'notification_id', 'device_id', 'status', 'fcm_message_id',
        'error_code', 'error', 'retry_count', 'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'retry_count' => 'integer',
            'attempted_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
