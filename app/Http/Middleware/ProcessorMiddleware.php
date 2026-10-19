<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProcessorMiddleware
{
    /**
     * Middleware pour protéger les routes des transformateurs approuvés
     * Vérifie que l'utilisateur a role='pro', pro_type='processor' et status='approved'
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Vérifier que l'utilisateur est authentifié
        if (!$user) {
            return redirect()->route('login');
        }

        // Vérifier que c'est un transformateur approuvé
        if ($user->role !== 'pro' || $user->pro_type !== 'processor' || $user->status !== 'approved') {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Accès refusé. Vous n\'êtes pas un transformateur approuvé.');
        }

        return $next($request);
    }
}
