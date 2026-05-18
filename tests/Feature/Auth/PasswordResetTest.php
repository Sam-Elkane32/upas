<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_redirects_guests_to_login(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertRedirect(route('login'));
    }

    public function test_forgot_password_post_redirects_to_login_without_sending_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertRedirect(route('login'));
        Notification::assertNothingSent();
        Notification::assertNotSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_token_route_redirects_to_login(): void
    {
        $response = $this->get('/reset-password/fake-token');

        $response->assertRedirect(route('login'));
    }

    public function test_reset_password_post_redirects_to_login(): void
    {
        $response = $this->post('/reset-password', [
            'token' => 'any',
            'email' => 'any@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('login'));
    }
}
