<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'account_id', 'admin_user_id', 'action',
        'target_type', 'target_id', 'meta', 'ip',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public static function record(string $action, ?AdminUser $actor, array $meta = [], ?string $targetType = null, ?string $targetId = null): void
    {
        static::create([
            'account_id' => $actor?->account_id,
            'admin_user_id' => $actor?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'meta' => $meta,
            'ip' => request()->ip(),
        ]);
    }
}
