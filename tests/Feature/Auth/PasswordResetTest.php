<?php

namespace Tests\Feature\Auth;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::resetPasswords());
    }

    public function test_reset_password_link_screen_can_be_rendered()
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
    }

    public function test_reset_password_link_can_be_requested()
    {
        $user = User::factory()->create();

        $response = $this->post(route('password.email'), ['email' => $user->email]);
        $resetRequest = PasswordResetRequest::where('user_id', $user->id)->firstOrFail();

        $response->assertRedirect(route('password-reset-request.waiting', $resetRequest->token));
        $this->assertSame('pending', $resetRequest->status);
    }

    public function test_reset_password_screen_can_be_rendered()
    {
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);
        $resetRequest = PasswordResetRequest::where('user_id', $user->id)->firstOrFail();

        $this->get(route('password-reset-request.waiting', $resetRequest->token))
            ->assertOk();
    }

    public function test_password_can_be_reset_with_valid_token()
    {
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);
        $resetRequest = PasswordResetRequest::where('user_id', $user->id)->firstOrFail();
        $resetRequest->update(['status' => 'approved']);

        $response = $this->post(route('password-reset-request.reset', $resetRequest->token), [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertSame('completed', $resetRequest->fresh()->status);
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $response = $this->post(route('password-reset-request.reset', 'invalid-token'), [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertNotFound();
    }
}
