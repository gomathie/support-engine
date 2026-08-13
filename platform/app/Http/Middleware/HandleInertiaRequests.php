<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props every page receives.
     *
     * Kept deliberately small. The temptation with Inertia is to share the
     * whole user object; instead only what the chrome actually renders is sent,
     * so an employee's payload never carries fields they should not see.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'job_title' => $user->job_title,
                    'department' => $user->department?->name,
                    'theme_preference' => $user->theme_preference,
                    'initials' => $this->initials($user->name),

                    // Drives nav visibility only. Every one of these is
                    // re-checked server-side by a policy before anything
                    // privileged happens.
                    'is_admin' => $user->hasRole(Role::Admin->value),
                    'is_manager' => $user->hasRole(Role::Manager->value),
                ] : null,
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],

            'app' => [
                'name' => config('app.name'),
            ],
        ];
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return strtoupper(
            collect($parts)->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')
        );
    }
}
