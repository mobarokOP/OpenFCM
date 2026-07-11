<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Topic extends Model
{
    use HasUuids;

    protected $fillable = ['application_id', 'name'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'topic_subscriptions')
            ->withTimestamps();
    }
}
