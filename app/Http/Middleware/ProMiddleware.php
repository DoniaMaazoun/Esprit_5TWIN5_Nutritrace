<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProMiddleware
{
    /**
     * Middleware pour protéger les routes des professionnels approuvés
     * Vérifie que l'utilisateur a role='pro' et status='approved'
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Vérifier que l'utilisateur est authentifié
        if (!$user) {
            return redirect()->route('login');
        }

        // Vérifier que c'est un professionnel approuvé
        if ($user->role !== 'pro' || $user->status !== 'approved') {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Accès refusé. Vous n\'êtes pas un professionnel approuvé.');
        }

        return $next($request);
    }
}
