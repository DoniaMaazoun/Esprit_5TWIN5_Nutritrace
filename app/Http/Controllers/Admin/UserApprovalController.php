<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
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

        // TODO: Envoyer un email de confirmation

        return back()->with('success', "Le compte de {$user->name} a été approuvé avec succès.");
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

        // TODO: Envoyer un email de rejet

        return back()->with('warning', "Le compte de {$user->name} a été rejeté.");
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

