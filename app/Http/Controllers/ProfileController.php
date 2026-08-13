<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'certificate_name' => $user->certificate_name,
                'job_title' => $user->job_title,
                'employee_number' => $user->employee_number,
                'department' => $user->department?->name,
                'theme_preference' => $user->theme_preference,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'certificate_name' => ['nullable', 'string', 'max:255'],
        ]);

        // Deliberately absent: department, employee number, job title and role.
        // Those are administrative facts about somebody's employment, not
        // profile preferences, and are set in the admin panel.
        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password updated.');
    }

    /**
     * The prototype kept the theme in localStorage, so it was per-browser.
     * Persisting it on the account means it follows the employee between
     * their desk and their phone; the browser copy is kept in step by the Vue
     * side so the choice still applies before the next page load.
     */
    public function updateTheme(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['required', Rule::in(['light', 'dark'])],
        ]);

        $request->user()->update(['theme_preference' => $validated['theme']]);

        return back();
    }
}
