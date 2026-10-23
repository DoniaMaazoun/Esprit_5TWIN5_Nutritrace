<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\AccountDeletionRequest;
use App\Http\Requests\Profile\AvatarUpdateRequest;
use App\Http\Requests\Profile\PasswordUpdateRequest;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Gestion user : « Mon profil » pour tous les rôles.
 * L'admin voit la page dans le back-office (Metis), les autres dans le front-office.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view($user->isAdmin() ? 'profile.admin' : 'profile.front', compact('user'));
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->name = $data['name'];
        if ($user->isPro()) {
            $user->company_name = $data['company_name'];
        }

        // Nouvel e-mail : il n'est plus considéré comme vérifié
        $emailChanged = $data['email'] !== $user->email;
        if ($emailChanged) {
            $user->email = $data['email'];
            $user->email_verified_at = null;
        }

        $user->save();

        return $this->backToProfile($emailChanged
            ? 'Vos informations ont été mises à jour. Votre nouvelle adresse e-mail devra être vérifiée.'
            : 'Vos informations ont été mises à jour.');
    }

    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        // Le cast « hashed » du modèle User chiffre le mot de passe
        $request->user()->update(['password' => $request->validated('password')]);

        return $this->backToProfile('Votre mot de passe a été modifié.');
    }

    public function updateAvatar(AvatarUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $old = $user->avatar_path;

        $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        $user->save();

        if ($old) {
            Storage::disk('public')->delete($old);
        }

        return $this->backToProfile('Votre photo de profil a été mise à jour.');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }

        return $this->backToProfile('Votre photo de profil a été supprimée.');
    }

    public function destroy(AccountDeletionRequest $request): RedirectResponse
    {
        $user = $request->user();
        $avatar = $user->avatar_path;

        // Déconnexion puis suppression (les données liées partent en cascade)
        Auth::logout();
        $user->delete();

        if ($avatar) {
            Storage::disk('public')->delete($avatar);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Votre compte a été supprimé définitivement.');
    }

    private function backToProfile(string $message): RedirectResponse
    {
        return redirect()->route('profile.edit')->with('success', $message);
    }
}
