<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Mot de passe oublié : demande du lien de réinitialisation par e-mail
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Message neutre : on ne révèle jamais si l'adresse correspond à un compte.
     */
    public const NEUTRAL_STATUS = 'Si un compte existe pour cette adresse, un e-mail de réinitialisation vient d\'être envoyé. Pensez à vérifier vos spams.';

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être valide.',
        ]);

        // Le broker crée un token haché (table password_reset_tokens), applique le délai
        // entre deux envois (auth.passwords.users.throttle) et notifie l'utilisateur.
        // Lien envoyé, adresse inconnue ou envoi trop rapproché : même réponse.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', self::NEUTRAL_STATUS);
    }
}
