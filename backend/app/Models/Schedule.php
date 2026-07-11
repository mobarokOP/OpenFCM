<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'notification_id', 'send_at', 'timezone', 'recurring', 'status', 'dispatched_at',
    ];

    protected function casts(): array
    {
        return [
            'send_at' => 'datetime',
            'dispatched_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
