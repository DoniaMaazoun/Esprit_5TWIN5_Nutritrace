<?php

namespace Tests\Feature;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Gestion user : page « Mon profil » (tous les rôles)
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function consumer(array $attributes = []): User
    {
        return User::factory()->create(['role' => 'consumer', 'status' => 'active'] + $attributes);
    }

    private function pro(string $type = 'distributor', string $status = 'approved'): User
    {
        return User::factory()->create([
            'role' => 'pro', 'pro_type' => $type, 'company_name' => 'Ancienne Société',
            'tax_id' => '1234567A', 'status' => $status,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    // ---------- Accès ----------

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->patch(route('profile.update'), ['name' => 'X'])->assertRedirect(route('login'));
        $this->delete(route('profile.destroy'))->assertRedirect(route('login'));
    }

    public function test_every_role_can_open_its_profile_page(): void
    {
        foreach ([$this->consumer(), $this->pro('producer'), $this->pro('processor'), $this->pro('distributor'), $this->pro('distributor', 'pending')] as $user) {
            $this->actingAs($user)->get(route('profile.edit'))
                ->assertOk()->assertViewIs('profile.front')->assertSee('Mon profil')->assertSee($user->email);
        }

        $this->actingAs($this->admin())->get(route('profile.edit'))
            ->assertOk()->assertViewIs('profile.admin')->assertSee('Back-office');
    }

    public function test_profile_link_is_in_the_user_menus(): void
    {
        $this->actingAs($this->consumer())->get(route('frontend.home'))->assertSee(route('profile.edit'));
        $this->actingAs($this->admin())->get(route('backend.dashboard'))->assertSee(route('profile.edit'));
    }

    // ---------- Informations ----------

    public function test_user_can_update_name_and_email_and_email_becomes_unverified(): void
    {
        $user = $this->consumer();

        $this->actingAs($user)->patch(route('profile.update'), ['name' => '  Sami Ben Ali ', 'email' => 'NOUVEAU@Test.tn'])
            ->assertRedirect(route('profile.edit'))->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Sami Ben Ali', $user->name);
        $this->assertSame('nouveau@test.tn', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_is_kept_when_email_is_unchanged(): void
    {
        $user = $this->consumer();

        $this->actingAs($user)->patch(route('profile.update'), ['name' => 'Nouveau Nom', 'email' => $user->email])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_profile_validation_errors_are_in_french(): void
    {
        $other = $this->consumer(['email' => 'pris@test.tn']);
        $user = $this->consumer();

        $this->actingAs($user)->from(route('profile.edit'))
            ->patch(route('profile.update'), ['name' => '', 'email' => 'pris@test.tn'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrorsIn('profile', [
                'name' => 'Le nom est obligatoire.',
                'email' => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
            ]);

        $this->actingAs($user)->patch(route('profile.update'), ['name' => 'Ok', 'email' => 'pas-un-email'])
            ->assertSessionHasErrorsIn('profile', ['email' => 'L\'adresse e-mail doit être valide.']);

        $this->assertNotSame('pris@test.tn', $user->refresh()->email);
        $this->assertSame('pris@test.tn', $other->refresh()->email);
    }

    public function test_pro_can_update_company_name_but_not_tax_id_or_role(): void
    {
        $pro = $this->pro();

        $this->actingAs($pro)->patch(route('profile.update'), [
            'name' => $pro->name, 'email' => $pro->email, 'company_name' => 'Nouvelle Société',
            'tax_id' => 'PIRATE99', 'role' => 'admin', 'status' => 'approved', 'pro_type' => 'producer',
        ])->assertSessionHasNoErrors();

        $pro->refresh();
        $this->assertSame('Nouvelle Société', $pro->company_name);
        $this->assertSame('1234567A', $pro->tax_id);
        $this->assertSame('pro', $pro->role);
        $this->assertSame('distributor', $pro->pro_type);
    }

    public function test_pro_company_name_is_required_and_ignored_for_consumers(): void
    {
        $pro = $this->pro();
        $this->actingAs($pro)->patch(route('profile.update'), ['name' => $pro->name, 'email' => $pro->email, 'company_name' => ''])
            ->assertSessionHasErrorsIn('profile', ['company_name' => 'Le nom de l\'établissement est obligatoire pour un compte professionnel.']);

        $consumer = $this->consumer();
        $this->actingAs($consumer)->patch(route('profile.update'), ['name' => $consumer->name, 'email' => $consumer->email, 'company_name' => 'Hack SARL'])
            ->assertSessionHasNoErrors();
        $this->assertNull($consumer->refresh()->company_name);
    }

    // ---------- Mot de passe ----------

    public function test_user_can_change_password_with_current_password(): void
    {
        $user = $this->consumer();

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'password', 'password' => 'NouveauMdp2026', 'password_confirmation' => 'NouveauMdp2026',
        ])->assertRedirect(route('profile.edit'))->assertSessionHas('success');

        $this->assertTrue(Hash::check('NouveauMdp2026', $user->refresh()->password));
    }

    public function test_password_change_requires_correct_current_password_and_confirmation(): void
    {
        $user = $this->consumer();

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'mauvais', 'password' => 'NouveauMdp2026', 'password_confirmation' => 'NouveauMdp2026',
        ])->assertSessionHasErrorsIn('password', ['current_password' => 'Le mot de passe actuel est incorrect.']);

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'password', 'password' => 'NouveauMdp2026', 'password_confirmation' => 'Different2026',
        ])->assertSessionHasErrorsIn('password', ['password' => 'La confirmation ne correspond pas au nouveau mot de passe.']);

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'password', 'password' => 'court', 'password_confirmation' => 'court',
        ])->assertSessionHasErrorsIn('password', ['password' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.']);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    // ---------- Photo de profil ----------

    public function test_user_can_upload_replace_and_remove_avatar(): void
    {
        $user = $this->pro('producer');

        $this->actingAs($user)->post(route('profile.avatar'), ['avatar' => UploadedFile::fake()->image('moi.jpg', 300, 300)])
            ->assertRedirect(route('profile.edit'))->assertSessionHas('success');
        $first = $user->refresh()->avatar_path;
        $this->assertStringStartsWith('avatars/', $first);
        Storage::disk('public')->assertExists($first);
        $this->assertSame(asset('storage/' . $first), $user->avatarUrl());

        // La photo remplace les initiales dans le menu
        $this->actingAs($user)->get(route('frontend.home'))->assertSee('storage/' . $first);

        // Remplacement : l'ancien fichier est supprimé
        $this->actingAs($user)->post(route('profile.avatar'), ['avatar' => UploadedFile::fake()->image('moi.webp', 300, 300)]);
        $second = $user->refresh()->avatar_path;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->actingAs($user)->delete(route('profile.avatar.destroy'))->assertRedirect(route('profile.edit'));
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($user->refresh()->avatar_path);
        $this->assertNull($user->avatarUrl());
    }

    public function test_admin_avatar_is_shown_in_back_office(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('profile.avatar'), ['avatar' => UploadedFile::fake()->image('admin.png', 200, 200)]);

        $this->actingAs($admin)->get(route('backend.dashboard'))->assertOk()->assertSee('storage/' . $admin->refresh()->avatar_path);
    }

    public function test_avatar_must_be_an_image_of_allowed_type_and_size(): void
    {
        $user = $this->consumer();

        $this->actingAs($user)->post(route('profile.avatar'), ['avatar' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrorsIn('avatar', 'avatar');
        $this->actingAs($user)->post(route('profile.avatar'), ['avatar' => UploadedFile::fake()->image('anim.gif')])
            ->assertSessionHasErrorsIn('avatar', ['avatar' => 'Formats acceptés : JPG, PNG ou WebP.']);
        $this->actingAs($user)->post(route('profile.avatar'), ['avatar' => UploadedFile::fake()->image('lourd.jpg')->size(3000)])
            ->assertSessionHasErrorsIn('avatar', ['avatar' => 'La photo ne doit pas dépasser 2 Mo.']);
        $this->actingAs($user)->post(route('profile.avatar'), [])
            ->assertSessionHasErrorsIn('avatar', ['avatar' => 'Choisissez une photo à envoyer.']);

        $this->assertNull($user->refresh()->avatar_path);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    // ---------- Suppression du compte ----------

    public function test_user_can_delete_account_with_password(): void
    {
        $user = $this->pro('producer');
        $farm = Farm::forceCreate([
            'user_id' => $user->id, 'name' => 'Ferme du Profil', 'governorate' => 'Sousse', 'city' => 'Sousse',
            'total_area' => 10, 'farming_type' => 'organic', 'irrigation_type' => 'drip',
        ]);
        $this->actingAs($user)->post(route('profile.avatar'), ['avatar' => UploadedFile::fake()->image('moi.jpg')]);
        $avatar = $user->refresh()->avatar_path;

        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertGuest();
        $this->assertModelMissing($user);
        $this->assertModelMissing($farm);
        Storage::disk('public')->assertMissing($avatar);
    }

    public function test_account_deletion_requires_correct_password(): void
    {
        $user = $this->consumer();

        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'mauvais'])
            ->assertSessionHasErrorsIn('deletion', ['password' => 'Le mot de passe est incorrect.']);
        $this->actingAs($user)->delete(route('profile.destroy'), [])
            ->assertSessionHasErrorsIn('deletion', ['password' => 'Saisissez votre mot de passe pour confirmer la suppression.']);

        $this->assertModelExists($user);
        $this->assertAuthenticatedAs($user);
    }

    public function test_last_admin_cannot_delete_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasErrorsIn('deletion', ['account' => 'Impossible de supprimer le dernier compte administrateur. Nommez d\'abord un autre administrateur.']);
        $this->assertModelExists($admin);

        // Avec un second administrateur, la suppression est possible
        $this->admin();
        $this->actingAs($admin)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect(route('login'));
        $this->assertModelMissing($admin);
    }

    // ---------- Sécurité ----------

    public function test_a_user_only_modifies_their_own_profile(): void
    {
        $victim = $this->consumer(['name' => 'Victime']);
        $attacker = $this->consumer();

        $this->actingAs($attacker)->patch(route('profile.update'), [
            'id' => $victim->id, 'name' => 'Pirate', 'email' => $attacker->email,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Victime', $victim->refresh()->name);
        $this->assertSame('Pirate', $attacker->refresh()->name);
    }

    public function test_profile_forms_are_protected_by_csrf(): void
    {
        $user = $this->consumer();
        $html = $this->actingAs($user)->get(route('profile.edit'))->getContent();

        // Infos, mot de passe, photo, suppression + déconnexion portent un jeton CSRF
        $this->assertGreaterThanOrEqual(5, substr_count($html, 'name="_token"'));
        $this->assertStringContainsString('name="_method" value="DELETE"', $html);
    }
}
