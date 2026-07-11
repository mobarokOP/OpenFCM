<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'plan'];

    public function adminUsers(): HasMany
    {
        return $this->hasMany(AdminUser::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
