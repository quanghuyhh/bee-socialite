<?php

namespace Bee\Socialite\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'provider_user_id',
        'provider',
        'email',
        'token',
        'refreshToken',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeUserExists(Builder $builder, string $provider_user_id, string $provider): Builder
    {
        $table = $this->getTable();

        return $builder->where(
            fn ($subQuery) => $subQuery->where("$table.provider_user_id", $provider_user_id)
                ->where("$table.provider", $provider)
        );
    }
}
