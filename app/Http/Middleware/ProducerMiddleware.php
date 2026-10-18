<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProducerMiddleware
{
    /**
     * Middleware pour le Module 1 (Producteur)
     * À utiliser après 'pro' : vérifie en plus que pro_type='producer'
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->isProducer()) {
            return redirect()->route('pro.dashboard')
                ->with('error', 'Ce module est réservé aux producteurs.');
        }

        return $next($request);
    }
}
