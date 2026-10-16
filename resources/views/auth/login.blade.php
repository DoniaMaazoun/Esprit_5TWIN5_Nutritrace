@extends('frontend.layouts.app')

@section('title', 'Connexion - Farm to Fork')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Logo / Header -->
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Farm to Fork</h1>
            <p class="text-gray-600">De la ferme à l'assiette en toute transparence</p>
        </div>

        <!-- Login Form -->
        <div class="bg-white py-8 px-6 shadow rounded-lg sm:px-10">
            <h2 class="text-center text-2xl font-extrabold text-gray-900 mb-6">Connexion</h2>

            <!-- Error Messages -->
            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                @if ($errors->has('email') && $errors->first('email') === 'Ces identifiants ne correspondent pas à nos enregistrements.')
                                    Identifiants invalides
                                @elseif ($errors->has('pending_account'))
                                    {{ $errors->first('pending_account') }}
                                @else
                                    Erreur de connexion
                                @endif
                            </h3>
                            @if ($errors->has('pending_account'))
                                <div class="mt-2 text-sm text-red-700">
                                    {{ $errors->first('pending_account') }}
                                </div>
                            @else
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <form class="space-y-6" method="POST" action="{{ route('login') }}">
                @csrf

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
                        autofocus
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
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input
                        id="remember_me"
                        type="checkbox"
                        name="remember"
                        class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                    />
                    <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                        Se souvenir de moi
                    </label>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-green-700 to-green-600 hover:from-green-800 hover:to-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200"
                >
                    Connexion
                </button>

                <!-- Forgot Password Link -->
                <div class="text-center">
                    <a href="{{ route('password.request') }}" class="text-sm text-green-600 hover:text-green-700 font-medium">
                        Mot de passe oublié ?
                    </a>
                </div>
            </form>
        </div>

        <!-- Sign Up Link -->
        <div class="text-center">
            <p class="text-sm text-gray-600">
                Vous n'avez pas de compte ?
                <a href="{{ route('register') }}" class="font-medium text-green-600 hover:text-green-700">
                    S'inscrire
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
