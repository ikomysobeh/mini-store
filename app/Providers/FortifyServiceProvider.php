<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\Setting;
use App\Services\CartService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register custom RegisterResponse to force full page reload after registration
        // This ensures fresh CSRF token and prevents "CSRF token mismatch" errors
        $this->app->singleton(RegisterResponse::class, \App\Http\Responses\RegisterResponse::class);

        // FIXED: Better LoginResponse with proper user type handling
        $this->app->instance(LoginResponse::class, new class implements LoginResponse {
            public function toResponse($request)
            {
                $user = auth()->user();

                // Enhanced logging for debugging
                Log::info('🔍 LOGIN RESPONSE - User Authentication', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'is_admin_raw' => $user->is_admin ?? 'null',
                    'request_redirect' => $request->get('redirect'),
                ]);

                // Check if there's a redirect parameter (from guest checkout flow)
                $redirectTo = $request->get('redirect');
                if ($redirectTo === 'checkout') {
                    Log::info('🔍 Redirecting to checkout');
                    return Inertia::location('/checkout');
                }

                // Check if user is admin
                $isAdmin = $this->checkIfUserIsAdmin($user);

                Log::info('🔍 User Redirect Decision', [
                    'is_admin_result' => $isAdmin,
                    'redirect_destination' => $isAdmin ? '/admin' : '/'
                ]);

                // FIXED: Admins go to admin, regular users go to home
                if ($isAdmin) {
                    return Inertia::location('/admin');
                }

                // FIXED: Regular users go to home page ("/")
                return Inertia::location('/');
            }

            /**
             * Check if user is admin
             */
            private function checkIfUserIsAdmin($user): bool
            {
                // Method 1: Boolean true
                if (isset($user->is_admin) && $user->is_admin === true) {
                    return true;
                }

                // Method 2: Integer 1
                if (isset($user->is_admin) && $user->is_admin === 1) {
                    return true;
                }

                // Method 3: String "1"
                if (isset($user->is_admin) && $user->is_admin === "1") {
                    return true;
                }

                // Method 4: Role-based (if you use roles)
                if (isset($user->role) && in_array($user->role, ['admin', 'administrator'])) {
                    return true;
                }

                // Method 5: Email fallback (for development)
                $adminEmails = ['admin@admin.com', 'admin@example.com'];
                if (in_array($user->email, $adminEmails)) {
                    return true;
                }

                // Method 6: User ID 1 fallback
                if ($user->id === 1) {
                    return true;
                }

                return false;
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ignore default Fortify routes - we'll register them with locale prefix
        Fortify::ignoreRoutes();

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Rate limiting
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());
            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // Cart merging and login events
        $this->app->resolved('Illuminate\Auth\AuthManager', function ($auth) {
            $auth->listen('Illuminate\Auth\Events\Login', function ($event) {
                Log::info('🔍 LOGIN EVENT', [
                    'user_id' => $event->user->id,
                    'user_email' => $event->user->email,
                    'is_admin' => $event->user->is_admin ?? 'null',
                ]);
            });
        });

        // Custom authentication
        Fortify::authenticateUsing(function (Request $request) {
            $user = \App\Models\User::where('email', $request->email)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }

            return null;
        });

        // Inertia Views - Fixed paths
        Fortify::loginView(function () {
            return $this->renderAuthView('auth/Login', [
                'canResetPassword' => Features::enabled(Features::resetPasswords()),
                'canRegister' => Features::enabled(Features::registration()),
            ]);
        });

        Fortify::registerView(function () {
            return $this->renderAuthView('auth/Register');
        });

        Fortify::requestPasswordResetLinkView(function () {
            return $this->renderAuthView('auth/ForgotPassword');
        });

        Fortify::resetPasswordView(function (Request $request) {
            return $this->renderAuthView('auth/ResetPassword', [
                'email' => $request->input('email'),
                'token' => $request->route('token'),
            ]);
        });

        Fortify::verifyEmailView(function () {
            return $this->renderAuthView('auth/VerifyEmail');
        });

        Fortify::confirmPasswordView(function () {
            return $this->renderAuthView('auth/ConfirmPassword');
        });

        Fortify::twoFactorChallengeView(function () {
            return $this->renderAuthView('auth/TwoFactorChallenge');
        });
    }

    /**
     * Helper method to render auth views
     */
    private function renderAuthView(string $view, array $data = []): \Inertia\Response
    {
        try {
            $settings = cache()->remember('public_settings', 300, function () {
                return Setting::public()->get()->pluck('value', 'key');
            });

            $logoUrl = null;
            if (isset($settings['logo']) && $settings['logo']) {
                $logoUrl = asset('storage/' . $settings['logo']);
            }

            $authData = array_merge([
                'canResetPassword' => Route::has('password.request'),
                'canRegister' => Features::enabled(Features::registration()),
                'logoUrl' => $logoUrl,
                'siteName' => $settings['site_name'] ?? config('app.name', 'Laravel'),
                'siteTagline' => $settings['site_tagline'] ?? '',
                'status' => session('status'),
                'locale' => app()->getLocale(), // Add locale for i18n
            ], $data);

            return Inertia::render($view, $authData);

        } catch (\Exception $e) {
            Log::error('Failed to load settings for auth view: ' . $e->getMessage());

            $fallbackData = array_merge([
                'canResetPassword' => Route::has('password.request'),
                'canRegister' => Features::enabled(Features::registration()),
                'logoUrl' => null,
                'siteName' => config('app.name', 'Laravel'),
                'siteTagline' => '',
                'status' => session('status'),
                'locale' => app()->getLocale(), // Add locale for i18n
            ], $data);

            return Inertia::render($view, $fallbackData);
        }
    }
}
