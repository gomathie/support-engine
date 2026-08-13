<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Auth/Login');
    }

    public function test_employee_can_sign_in(): void
    {
        $user = $this->employee();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_signing_in_records_the_time(): void
    {
        $user = $this->employee();

        $this->assertNull($user->last_login_at);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->employee();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'not-the-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_account_cannot_sign_in(): void
    {
        $user = $this->employee(attributes: ['is_active' => false]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * Deactivation has to bite on the next request, not whenever the session
     * happens to expire — otherwise a leaver keeps working until their cookie
     * lapses.
     */
    public function test_deactivating_an_account_ends_the_existing_session(): void
    {
        $user = $this->employee();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->update(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guests_are_redirected_from_the_portal(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('courses.index'))->assertRedirect(route('login'));
        $this->get(route('certificates.index'))->assertRedirect(route('login'));
        $this->get(route('progress.index'))->assertRedirect(route('login'));
    }

    public function test_sign_out_clears_the_session(): void
    {
        $user = $this->employee();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /**
     * The error message must not differ between "no such account" and "wrong
     * password", or the form becomes a way to discover who works here.
     */
    public function test_unknown_address_and_wrong_password_give_the_same_error(): void
    {
        $user = $this->employee();

        $unknown = $this->post(route('login'), [
            'email' => 'nobody@pilot.test',
            'password' => 'password',
        ]);

        $wrong = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong',
        ]);

        $this->assertSame(
            $unknown->getSession()->get('errors')->first('email'),
            $wrong->getSession()->get('errors')->first('email'),
        );
    }
}
