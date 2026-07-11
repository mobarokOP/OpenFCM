<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasUuids;

    protected $fillable = [
        'application_id', 'name', 'prefix', 'hash', 'last_used_at', 'revoked_at',
    ];

    protected $hidden = ['hash'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Generate a new plaintext key + persist its hash.
     * Returns [ApiKey $model, string $plaintext].
     */
    public static function generate(Application $app, string $name): array
    {
        $secret = Str::random(40);
        $plaintext = "op_live_{$secret}";

        $model = static::create([
            'application_id' => $app->id,
            'name' => $name,
            'prefix' => substr($plaintext, 0, 12),
            'hash' => hash('sha256', $plaintext),
        ]);

        return [$model, $plaintext];
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
