<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Determine account type and set validation rules
        $accountType = $request->input('account_type', 'consumer');
        
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['required', 'accepted'],
        ];

        // Add professional validation rules if account type is 'pro'
        if ($accountType === 'pro') {
            $rules['pro_type'] = ['required', 'in:producer,processor,distributor'];
            $rules['company_name'] = ['required', 'string', 'max:255'];
            $rules['tax_id'] = ['required', 'string', 'regex:/^[A-Za-z0-9\/\-]{7,20}$/', 'unique:users'];
        }

        $validated = $request->validate($rules, [
            'name.required' => 'Le nom est obligatoire.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne doit pas dépasser 255 caractères.',
            'email.required' => 'L\'adresse e-mail est obligatoire.',
            'email.email' => 'L\'adresse e-mail doit être valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'pro_type.required' => 'Le type d\'activité est obligatoire pour un compte professionnel.',
            'company_name.required' => 'Le nom de l\'établissement est obligatoire pour un compte professionnel.',
            'tax_id.required' => 'L\'identifiant fiscal est obligatoire pour un compte professionnel.',
            'tax_id.unique' => 'Cet identifiant fiscal est déjà utilisé.',
            'tax_id.regex' => 'Matricule fiscal invalide (7 à 20 caractères : lettres, chiffres, / ou -).',
            'pro_type.in' => 'Le type d\'activité choisi est invalide.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'terms.required' => 'Vous devez accepter les conditions d\'utilisation.',
            'terms.accepted' => 'Vous devez accepter les conditions d\'utilisation.',
        ]);

        // Determine role and initial status based on account type
        $role = $accountType === 'pro' ? 'pro' : 'consumer';
        $status = $accountType === 'pro' ? 'pending' : 'active';

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'status' => $status,
            'pro_type' => $validated['pro_type'] ?? null,
            'company_name' => $validated['company_name'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
        ]);

        event(new Registered($user));

        // Log the user in only if it's a consumer
        if ($accountType === 'consumer') {
            Auth::login($user);
            return redirect(route('frontend.home', absolute: false));
        }

        // For professionals, show a confirmation message
        return redirect(route('login'))->with('status', 'Votre compte professionnel a été créé. Veuillez patienter pendant que notre équipe valide votre inscription. Vous recevrez un e-mail de confirmation.');
    }
}

