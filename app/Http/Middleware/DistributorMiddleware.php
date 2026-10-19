<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DistributorMiddleware
{
    /**
     * Module 3 - Protège l'espace distributeur
     * Vérifie role='pro', status='approved' et pro_type='distributor'
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isApproved() || ! $user->isDistributor()) {
            return redirect()->route('pro.dashboard')->with('error', 'Accès réservé aux distributeurs approuvés.');
        }

        return $next($request);
    }
}
