<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Gestion user : réinitialisation du mot de passe par e-mail (aucun vrai e-mail envoyé)
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function user(): User
    {
        return User::factory()->create([
            'email' => 'client@test.tn',
            'role' => 'consumer',
            'status' => 'active',
            'password' => 'ancien-mot-de-passe',
        ]);
    }

    public function test_login_page_links_to_forgot_password(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('password.request'));
    }

    public function test_forgot_password_page_is_displayed(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee('Mot de passe oublié');
    }

    public function test_reset_link_is_sent_to_existing_user(): void
    {
        $user = $this->user();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', PasswordResetLinkController::NEUTRAL_STATUS);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Réinitialisation de votre mot de passe - Farm to Fork'
                && str_contains($mail->render(), route('password.reset', ['token' => $notification->token, 'email' => $user->email]));
        });
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_unknown_email_gets_the_same_neutral_message(): void
    {
        $this->post(route('password.email'), ['email' => 'inconnu@test.tn'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', PasswordResetLinkController::NEUTRAL_STATUS);

        Notification::assertNothingSent();
    }

    public function test_second_request_is_throttled(): void
    {
        $user = $this->user();

        $this->post(route('password.email'), ['email' => $user->email]);
        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', PasswordResetLinkController::NEUTRAL_STATUS);

        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
    }

    public function test_reset_page_is_displayed(): void
    {
        $this->get(route('password.reset', ['token' => 'abc', 'email' => 'client@test.tn']))
            ->assertOk()
            ->assertSee('client@test.tn');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = $this->user();

        $this->from(route('password.reset', ['token' => 'faux-token']))
            ->post(route('password.update'), [
                'token' => 'faux-token',
                'email' => $user->email,
                'password' => 'nouveau-mot-de-passe',
                'password_confirmation' => 'nouveau-mot-de-passe',
            ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('ancien-mot-de-passe', $user->fresh()->password));
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        $this->travel(61)->minutes();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('ancien-mot-de-passe', $user->fresh()->password));
    }

    public function test_too_short_password_is_rejected(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'court',
            'password_confirmation' => 'court',
        ])->assertSessionHasErrors(['password' => 'Le mot de passe doit contenir au moins 8 caractères.']);

        $this->assertTrue(Hash::check('ancien-mot-de-passe', $user->fresh()->password));
    }
}
