<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Mail\ProAccountApproved;
use App\Mail\ProAccountRejected;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class UserApprovalController extends Controller
{
    /**
     * Affiche la liste des comptes professionnels en attente
     */
    public function index(): View
    {
        // Récupérer les comptes Pro en attente
        $pendingUsers = User::where('role', 'pro')
            ->where('status', 'pending')
            ->paginate(10);

        // Récupérer les comptes Pro approuvés
        $approvedUsers = User::where('role', 'pro')
            ->where('status', 'approved')
            ->paginate(10);

        // Récupérer les comptes Pro rejetés
        $rejectedUsers = User::where('role', 'pro')
            ->where('status', 'rejected')
            ->paginate(10);

        return view('backend.pages.user-approvals', [
            'pendingUsers' => $pendingUsers,
            'approvedUsers' => $approvedUsers,
            'rejectedUsers' => $rejectedUsers,
        ]);
    }

    /**
     * Approver un compte professionnel
     */
    public function approve(User $user): RedirectResponse
    {
        // Vérifier que c'est un compte Pro
        if (!$user->isPro()) {
            return back()->with('error', 'Seuls les comptes professionnels peuvent être approuvés.');
        }

        // Mettre à jour le statut
        $user->update(['status' => 'approved']);

        // Envoyer l'email d'approbation
        try {
            Mail::to($user->email)->send(new ProAccountApproved($user));
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'envoi de l\'email d\'approbation: ' . $e->getMessage());
        }

        return back()->with('success', "Le compte de {$user->name} a été approuvé avec succès. Un email de confirmation a été envoyé.");
    }

    /**
     * Rejeter un compte professionnel
     */
    public function reject(User $user): RedirectResponse
    {
        // Vérifier que c'est un compte Pro
        if (!$user->isPro()) {
            return back()->with('error', 'Seuls les comptes professionnels peuvent être rejetés.');
        }

        // Mettre à jour le statut
        $user->update(['status' => 'rejected']);

        // Envoyer l'email de rejet
        try {
            Mail::to($user->email)->send(new ProAccountRejected($user, 'Les informations fournies ne correspondent pas à nos critères de vérification.'));
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'envoi de l\'email de rejet: ' . $e->getMessage());
        }

        return back()->with('warning', "Le compte de {$user->name} a été rejeté. Un email de notification a été envoyé.");
    }

    /**
     * Réinitialiser un compte rejeté en attente
     */
    public function resetToPending(User $user): RedirectResponse
    {
        // Vérifier que c'est un compte Pro rejeté
        if (!$user->isPro() || $user->status !== 'rejected') {
            return back()->with('error', 'Action non autorisée.');
        }

        // Mettre à jour le statut en pending
        $user->update(['status' => 'pending']);

        return back()->with('success', "Le compte de {$user->name} a été réinitialisé en attente.");
    }
}

