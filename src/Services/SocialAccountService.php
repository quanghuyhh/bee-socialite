<?php

namespace Bee\Socialite\Services;

use App\Models\User;
use Bee\Socialite\Models\SocialAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialUser;

class SocialAccountService
{
    public function __construct(
        public SocialAccount $socialAccount,
    ) {
    }

    public function authWithSocialUser(SocialUser $socialUser, string $provider)
    {
        $socialAccount = $this->getUserWithSocialProvider($socialUser->getId(), $provider);
        $user = optional($socialAccount)->user;
        if (empty($user)) {
            $user = User::query()->firstOrCreate(
                [
                    'email' => $socialUser->getEmail(),
                ],
                [
                    'name' => $socialUser?->getName() ?? $socialUser?->getNickname() ?? fake()->name,
                    'email_verified_at' => now(),
                    'password' => Str::random(16),
                ]
            );
            $this->attachUserSocialAccount($user, $socialUser, $provider);
        }

        return $user;
    }

    public function getUserWithSocialProvider(string $provider_user_id, string $provider): ?Model
    {
        return $this->socialAccount->userExists($provider_user_id, $provider)->first();
    }

    public function attachUserSocialAccount(User $user, SocialUser $socialUser, string $provider): Model|Builder
    {
        return $this->socialAccount->firstOrCreate(
            [
                'provider_user_id' => $socialUser->getId(),
                'provider' => $provider,
            ],
            [
                'user_id' => $user->id,
                'email' => $socialUser?->getEmail(),
                'token' => $socialUser?->token,
                'refreshToken' => $socialUser?->refreshToken,
                'details' => (array) $socialUser,
            ]
        );
    }
}
