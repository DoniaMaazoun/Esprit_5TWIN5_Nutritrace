<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestion user : Profil utilisateur (tous les rôles : admin, consommateur, pro)
|--------------------------------------------------------------------------
| Chargé depuis routes/web.php. Seule l'authentification est requise : un pro
| en attente de validation peut aussi gérer son profil.
*/

Route::prefix('profil')->name('profile.')->middleware('auth')->controller(ProfileController::class)->group(function () {
    Route::get('/', 'edit')->name('edit');
    Route::patch('/', 'update')->name('update');
    Route::put('/mot-de-passe', 'updatePassword')->name('password');
    Route::post('/photo', 'updateAvatar')->name('avatar');
    Route::delete('/photo', 'destroyAvatar')->name('avatar.destroy');
    Route::delete('/', 'destroy')->name('destroy');
});
