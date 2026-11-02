<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\AclPolicy;
use App\View\Composers\FrontComposer;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(FrontComposer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super Users (group 1) are allowed everything; everyone else is checked against the acl table.
        Gate::before(fn (?User $user) => $user?->isSuperUser() ? true : null);
        Gate::define('acl', [AclPolicy::class, 'allows']);

        // Password-reset e-mails link to the admin panel's reset page.
        ResetPassword::createUrlUsing(fn ($user, string $token) => route('admin.password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]));

        Paginator::defaultView('pagination::bootstrap-3');
        View::composer(['front.*', 'errors::404'], FrontComposer::class);
        //
    }
}
