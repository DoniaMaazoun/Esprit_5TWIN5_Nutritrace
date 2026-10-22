<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Déploiement (Docker / Render) : charge les données de démo uniquement si la base est vide,
// pour que le conteneur puisse redémarrer sans dupliquer les comptes et produits.
Artisan::command('app:seed-if-empty', function () {
    if (User::query()->exists()) {
        $this->info('Base déjà initialisée : données de démo ignorées.');

        return;
    }

    $this->call('db:seed', ['--force' => true]);
    $this->info('Données de démo chargées.');
})->purpose('Charge les données de démo si aucun utilisateur n\'existe');
