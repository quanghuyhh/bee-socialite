<?php

namespace Bee\Socialite\Http\Controllers;

use App\Http\Controllers\Controller;
use Bee\Socialite\Enums\SocialDriveEnum;
use Bee\Socialite\Services\SocialAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class SocialAuthController extends Controller
{
    public function __construct(
        public SocialAccountService $service,
    ) {
    }

    public function handleRedirect(SocialDriveEnum $provider, Request $request): RedirectResponse|\Illuminate\Http\RedirectResponse
    {
        $providerSettings = config("services.{$provider->value}");
        $scopes = explode(',', $providerSettings['scopes'] ?? '');

        return Socialite::driver($provider->value)->scopes($scopes)->redirect();
    }

    public function handleCallback(SocialDriveEnum $provider, Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        try {
            DB::beginTransaction();

            $socialUser = Socialite::driver($provider->value)->user();
            dd($socialUser);
            throw_if(
                empty($socialUser),
                new \Exception(trans('Failed to authenticate with '.ucfirst($provider->value).'. Please try again!'))
            );

            $user = $this->service->authWithSocialUser($socialUser, $provider->value);

            DB::commit();

            Auth::login($user);
            Session::regenerate();

            return Inertia::location(url()->previous() ?? route('home'));
        } catch (\Throwable $throwable) {
            DB::rollBack();
            dd($throwable);

            return redirect()->route('auth.login')->withErrors([
                'email' => $throwable->getMessage(),
            ]);
        }
    }
}
