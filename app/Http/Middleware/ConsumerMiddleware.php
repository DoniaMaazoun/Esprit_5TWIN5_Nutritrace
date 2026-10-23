<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConsumerMiddleware
{
    /**
     * Middleware pour protéger les routes des consommateurs
     * Vérifie que l'utilisateur a role='consumer'
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Vérifier que l'utilisateur est authentifié
        if (!$user) {
            return redirect()->route('login');
        }

        // Vérifier que c'est un consommateur
        if (!$user->isConsumer()) {
            return redirect()->route('frontend.home')
                ->with('error', 'Cette section est réservée aux consommateurs.');
        }

        return $next($request);
    }
}
