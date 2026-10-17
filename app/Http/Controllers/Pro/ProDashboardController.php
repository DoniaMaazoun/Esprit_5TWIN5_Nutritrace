<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProDashboardController extends Controller
{
    /**
     * Affiche le tableau de bord des professionnels
     * Accessible uniquement pour role='pro' et status='approved'
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Vérifications de sécurité (redondant mais utile)
        if ($user->role !== 'pro' || $user->status !== 'approved') {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Accès refusé. Votre compte n\'est pas approuvé.');
        }

        // Récupérer les données de l'utilisateur
        $proType = $user->pro_type;
        $companyName = $user->company_name;
        $taxId = $user->tax_id;

        // Préparer les données selon le type de professionnel
        $dashboardData = match($proType) {
            'producer' => [
                'title' => 'Tableau de Bord Producteur',
                'icon' => 'fa-leaf',
                'color' => '#28a745',
                'modules' => [
                    [
                        'title' => 'Gestion des Fermes/Parcelles',
                        'icon' => 'fa-tree',
                        'description' => 'Créez et gérez vos fermes et parcelles. Enregistrez les surfaces cultivées et les types de culture.',
                        'route' => '#',
                        'badge' => 'À venir'
                    ],
                    [
                        'title' => 'Création de Lots de Récolte',
                        'icon' => 'fa-basket-shopping',
                        'description' => 'Créez des lots de récolte avec dates, quantités et certifications. Préparez les données de traçabilité.',
                        'route' => '#',
                        'badge' => 'À venir'
                    ],
                    [
                        'title' => 'Génération de QR Codes',
                        'icon' => 'fa-qrcode',
                        'description' => 'Générez automatiquement des QR codes pour chaque lot. Incluez les infos traçabilité et certificats.',
                        'route' => '#',
                        'badge' => 'À venir'
                    ],
                ]
            ],
            'processor' => [
                'title' => 'Tableau de Bord Transformateur',
                'icon' => 'fa-industry',
                'color' => '#fd7e14',
                'modules' => [
                    [
                        'title' => 'Réception de Lots',
                        'icon' => 'fa-inbox',
                        'description' => 'Recevez et enregistrez les lots provenant de producteurs. Vérifiez les quantités et certificats.',
                        'route' => '#',
                        'badge' => 'À venir'
                    ],
                    [
                        'title' => 'Transformations & Assemblages',
                        'icon' => 'fa-gears',
                        'description' => 'Enregistrez les processus de transformation. Tracez les ingrédients et ajouts. Créez de nouveaux lots.',
                        'route' => '#',
                        'badge' => 'À venir'
                    ],
                    [
                        'title' => 'Gestion des Emballages & Éco-Score',
                        'icon' => 'fa-box',
                        'description' => 'Gérez les emballages et calculez l\'éco-score. Optimisez l\'impact environnemental de vos produits.',
                        'route' => '#',
                        'badge' => 'À venir'
                    ],
                ]
            ],
            'distributor' => [
                'title' => 'Tableau de Bord Distributeur',
                'icon' => 'fa-truck',
                'color' => '#0d6efd',
                'modules' => [
                    [
                        'title' => 'Suivi des Transports & Kilométrage',
                        'icon' => 'fa-route',
                        'description' => 'Suivez les trajets de livraison. Enregistrez les kilométrages et calcul de l\'empreinte carbone.',
                        'route' => route('pro.distributor.shipments.index'),
                        'badge' => 'Disponible'
                    ],
                    [
                        'title' => 'Points de Vente',
                        'icon' => 'fa-store',
                        'description' => 'Gérez vos points de vente. Assignez des stocks et visualisez les disponibilités.',
                        'route' => route('pro.distributor.points-of-sale.index'),
                        'badge' => 'Disponible'
                    ],
                    [
                        'title' => 'Statuts des Stocks',
                        'icon' => 'fa-boxes-stacked',
                        'description' => 'Consultez les stocks en temps réel. Recevez des alertes sur les ruptures de stock prévues.',
                        'route' => '#',
                        'badge' => 'À venir'
                    ],
                ]
            ],
            default => [
                'title' => 'Tableau de Bord',
                'icon' => 'fa-chart-line',
                'color' => '#6c757d',
                'modules' => []
            ]
        };

        return view('pro.dashboard', [
            'user' => $user,
            'companyName' => $companyName,
            'taxId' => $taxId,
            'proType' => $proType,
            'dashboardData' => $dashboardData,
        ]);
    }
}
