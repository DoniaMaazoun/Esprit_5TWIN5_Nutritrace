@extends('frontend.layouts.app')

@section('title', 'Inscription - Farm to Fork')

@section('extra-css')
<style>
    .account-type-card {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .account-type-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
    }
    
    .account-type-card.active {
        border-color: #28a745;
        background-color: rgba(40, 167, 69, 0.05);
    }
    
    .pro-fields {
        display: none;
        animation: slideDown 0.3s ease;
    }
    
    .pro-fields.show {
        display: block;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endsection

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl w-full space-y-8">
        <!-- Logo / Header -->
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Farm to Fork</h1>
            <p class="text-gray-600">De la ferme à l'assiette en toute transparence</p>
        </div>

        <!-- Registration Form -->
        <div class="bg-white py-8 px-6 shadow rounded-lg sm:px-10">
            <h2 class="text-center text-2xl font-extrabold text-gray-900 mb-8">Créer votre compte</h2>

            <!-- Error Messages -->
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Erreur d'inscription</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <form class="space-y-6" method="POST" action="{{ route('register') }}" id="registerForm">
                @csrf

                <!-- Account Type Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-4">
                        Type de compte
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Consumer Account -->
                        <div
                            class="account-type-card border-2 border-gray-200 rounded-lg p-4 active"
                            data-role="consumer"
                        >
                            <div class="flex items-center">
                                <input
                                    type="radio"
                                    name="account_type"
                                    value="consumer"
                                    id="consumer"
                                    checked
                                    class="h-4 w-4 text-green-600"
                                />
                                <label for="consumer" class="ml-3 flex flex-col cursor-pointer">
                                    <span class="block text-sm font-medium text-gray-900">Consommateur</span>
                                    <span class="block text-xs text-gray-500">Grand public</span>
                                </label>
                            </div>
                        </div>

                        <!-- Professional Account -->
                        <div
                            class="account-type-card border-2 border-gray-200 rounded-lg p-4"
                            data-role="pro"
                        >
                            <div class="flex items-center">
                                <input
                                    type="radio"
                                    name="account_type"
                                    value="pro"
                                    id="pro"
                                    class="h-4 w-4 text-green-600"
                                />
                                <label for="pro" class="ml-3 flex flex-col cursor-pointer">
                                    <span class="block text-sm font-medium text-gray-900">Professionnel</span>
                                    <span class="block text-xs text-gray-500">Producteur, Transformateur, Distributeur</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Nom complet
                    </label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('name') border-red-500 @enderror"
                        placeholder="Jean Dupont"
                    />
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Adresse e-mail
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('email') border-red-500 @enderror"
                        placeholder="you@example.com"
                    />
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Mot de passe
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('password') border-red-500 @enderror"
                        placeholder="••••••••"
                    />
                    <p class="mt-1 text-xs text-gray-500">Minimum 8 caractères</p>
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                        Confirmer le mot de passe
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                        placeholder="••••••••"
                    />
                    @error('password_confirmation')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Professional Fields (Hidden by default) -->
                <div class="pro-fields" id="proFields">
                    <div class="border-t pt-6 mt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informations professionnelles</h3>

                        <!-- Professional Type -->
                        <div class="mb-4">
                            <label for="pro_type" class="block text-sm font-medium text-gray-700">
                                Type d'activité <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="pro_type"
                                name="pro_type"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('pro_type') border-red-500 @enderror"
                            >
                                <option value="">-- Sélectionner votre activité --</option>
                                <option value="producer" @selected(old('pro_type') === 'producer')>Producteur / Agriculteur</option>
                                <option value="processor" @selected(old('pro_type') === 'processor')>Transformateur / Usine</option>
                                <option value="distributor" @selected(old('pro_type') === 'distributor')>Distributeur / Logistique</option>
                            </select>
                            @error('pro_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Company Name -->
                        <div class="mb-4">
                            <label for="company_name" class="block text-sm font-medium text-gray-700">
                                Nom de l'établissement / Ferme <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="company_name"
                                type="text"
                                name="company_name"
                                value="{{ old('company_name') }}"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('company_name') border-red-500 @enderror"
                                placeholder="Ferme du Soleil"
                            />
                            @error('company_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Tax ID -->
                        <div>
                            <label for="tax_id" class="block text-sm font-medium text-gray-700">
                                Identifiant fiscal / Matricule <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="tax_id"
                                type="text"
                                name="tax_id"
                                value="{{ old('tax_id') }}"
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 @error('tax_id') border-red-500 @enderror"
                                placeholder="Ex: 12345678"
                            />
                            <p class="mt-1 text-xs text-gray-500">Votre numéro de matricule fiscal ou registre de commerce</p>
                            @error('tax_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Info Message -->
                        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded">
                            <div class="flex">
                                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <strong>Important :</strong> Votre compte professionnel sera soumis à une vérification par notre équipe administrative. Vous recevrez une confirmation par e-mail une fois approuvé.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms & Conditions -->
                <div class="flex items-center">
                    <input
                        id="terms"
                        type="checkbox"
                        name="terms"
                        class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded @error('terms') border-red-500 @enderror"
                    />
                    <label for="terms" class="ml-2 block text-sm text-gray-700">
                        J'accepte les
                        <a href="#" class="text-green-600 hover:text-green-700 font-medium">conditions d'utilisation</a>
                        et la
                        <a href="#" class="text-green-600 hover:text-green-700 font-medium">politique de confidentialité</a>
                    </label>
                </div>
                @error('terms')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-green-700 to-green-600 hover:from-green-800 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200"
                >
                    S'inscrire
                </button>
            </form>
        </div>

        <!-- Login Link -->
        <div class="text-center">
            <p class="text-sm text-gray-600">
                Vous avez déjà un compte ?
                <a href="{{ route('login') }}" class="font-medium text-green-600 hover:text-green-700">
                    Se connecter
                </a>
            </p>
        </div>
    </div>
</div>

@endsection

@section('extra-js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const accountTypeCards = document.querySelectorAll('.account-type-card');
    const accountTypeRadios = document.querySelectorAll('input[name="account_type"]');
    const proFields = document.getElementById('proFields');
    const proTypeSelect = document.getElementById('pro_type');
    const companyNameInput = document.getElementById('company_name');
    const taxIdInput = document.getElementById('tax_id');

    // Handle account type selection
    accountTypeCards.forEach(card => {
        card.addEventListener('click', function() {
            const role = this.dataset.role;
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;

            // Update card styling
            accountTypeCards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');

            // Show/hide professional fields
            toggleProFields(role);
        });
    });

    // Handle radio button change
    accountTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            accountTypeCards.forEach(card => card.classList.remove('active'));
            if (this.value === 'consumer') {
                accountTypeCards[0].classList.add('active');
            } else {
                accountTypeCards[1].classList.add('active');
            }
            toggleProFields(this.value);
        });
    });

    function toggleProFields(role) {
        if (role === 'pro') {
            proFields.classList.add('show');
            proTypeSelect.required = true;
            companyNameInput.required = true;
            taxIdInput.required = true;
        } else {
            proFields.classList.remove('show');
            proTypeSelect.required = false;
            companyNameInput.required = false;
            taxIdInput.required = false;
        }
    }

    // Initial state based on old input
    const selectedRole = document.querySelector('input[name="account_type"]:checked')?.value;
    if (selectedRole === 'pro') {
        toggleProFields('pro');
        accountTypeCards[1].classList.add('active');
        accountTypeCards[0].classList.remove('active');
    }
});
</script>
@endsection
