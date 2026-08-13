<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Session fixation: the id must change the moment privileges do.
        $request->session()->regenerate();

        $request->user()->forceFill(['last_login_at' => now()])->save();

        // Administrators and managers land in the panel they actually work in;
        // everyone else goes to their training dashboard.
        $intended = $request->user()->canAccessPanel(filament()->getPanel('admin'))
            ? filament()->getPanel('admin')->getUrl()
            : route('dashboard');

        return redirect()->intended($intended);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
