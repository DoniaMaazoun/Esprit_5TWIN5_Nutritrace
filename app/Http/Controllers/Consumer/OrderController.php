<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\DistributorStock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Gestion des commandes consommateur
 * Protégé par le middleware ConsumerMiddleware (role=consumer)
 */
class OrderController extends Controller
{
    /**
     * Afficher le formulaire de checkout (récapitulatif + livraison)
     */
    public function checkout(): View|RedirectResponse
    {
        $user = auth()->user();

        \Log::info('[CHECKOUT] Start', [
            'user_id' => $user->id,
            'user_email' => $user->email,
        ]);

        if (!$user->isConsumer()) {
            abort(403, 'Accès réservé aux consommateurs');
        }

        // Charger les articles du panier avec les relations
        $cartItems = CartItem::query()
            ->forUser($user)
            ->with(['distributorStock.productionLot.recipe', 'distributorStock.pointOfSale'])
            ->get();

        \Log::info('[CHECKOUT] Cart items loaded', [
            'user_id' => $user->id,
            'count' => $cartItems->count(),
            'items' => $cartItems->map(fn($item) => ['id' => $item->id, 'stock_id' => $item->distributor_stock_id, 'qty' => $item->quantity])->toArray(),
        ]);

        if ($cartItems->isEmpty()) {
            \Log::warning('[CHECKOUT] Cart is empty', ['user_id' => $user->id]);
            return redirect()->route('consumer.cart')->with('error', 'Votre panier est vide.');
        }

        // Vérifier les stocks disponibles
        $unavailableItems = $cartItems->filter(fn (CartItem $item) => !$item->isQuantityAvailable());

        if ($unavailableItems->isNotEmpty()) {
            \Log::warning('[CHECKOUT] Some items unavailable', [
                'user_id' => $user->id,
                'unavailable_count' => $unavailableItems->count(),
            ]);
            return redirect()->route('consumer.cart')->with('error', 'Certains articles ne sont plus disponibles en stock. Veuillez ajuster votre panier.');
        }

        // Calculer le total
        $total = $cartItems->sum(fn (CartItem $item) => $item->getSubtotal());

        // Pré-remplir les informations de livraison avec les données utilisateur si disponibles
        $deliveryData = [
            'delivery_name' => $user->name,
            'delivery_phone' => null,
            'delivery_address' => null,
            'delivery_city' => null,
            'delivery_governorate' => null,
            'delivery_notes' => null,
        ];

        \Log::info('[CHECKOUT] Rendering checkout view', [
            'user_id' => $user->id,
            'total' => $total,
        ]);

        return view('consumer.orders.checkout', compact('cartItems', 'total', 'deliveryData'));
    }

    /**
     * Créer la commande (traitement du formulaire)
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        \Log::info('[ORDER STORE] Start', [
            'user_id' => $user->id,
            'user_email' => $user->email,
        ]);

        if (!$user->isConsumer()) {
            abort(403, 'Accès réservé aux consommateurs');
        }

        // Valider les données de livraison et mode de paiement
        $validated = $request->validate([
            'delivery_name' => 'required|string|max:255',
            'delivery_phone' => 'required|string|max:20',
            'delivery_address' => 'required|string|max:500',
            'delivery_city' => 'required|string|max:100',
            'delivery_governorate' => 'required|string|max:100',
            'delivery_notes' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:cash_on_delivery,stripe',
        ]);

        // Charger les articles du panier
        $cartItems = CartItem::query()
            ->forUser($user)
            ->with('distributorStock')
            ->get();

        \Log::info('[ORDER STORE] Cart items before order creation', [
            'user_id' => $user->id,
            'count' => $cartItems->count(),
            'items' => $cartItems->map(fn($item) => ['id' => $item->id, 'stock_id' => $item->distributor_stock_id, 'qty' => $item->quantity])->toArray(),
        ]);

        if ($cartItems->isEmpty()) {
            \Log::warning('[ORDER STORE] Cart is empty', ['user_id' => $user->id]);
            return redirect()->route('consumer.cart')->with('error', 'Votre panier est vide.');
        }

        // Déterminer la devise selon le mode de paiement
        $currency = $validated['payment_method'] === 'stripe' 
            ? config('services.stripe.currency', 'usd') 
            : 'TND';

        // Transaction de base de données pour éviter les incohérences
        return DB::transaction(function () use ($user, $validated, $cartItems, $currency) {
            // Vérifier à nouveau les stocks (double-check)
            foreach ($cartItems as $item) {
                $stock = $item->distributorStock;
                if (!$stock->isAvailable() || $item->quantity > $stock->quantity_available) {
                    throw new \Exception("Stock insuffisant pour: {$item->getProductName()}");
                }
            }

            // Créer la commande
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user->id,
                'total_amount' => $cartItems->sum(fn (CartItem $item) => $item->getSubtotal()),
                'currency' => $currency,
                'delivery_name' => $validated['delivery_name'],
                'delivery_phone' => $validated['delivery_phone'],
                'delivery_address' => $validated['delivery_address'],
                'delivery_city' => $validated['delivery_city'],
                'delivery_governorate' => $validated['delivery_governorate'],
                'delivery_notes' => $validated['delivery_notes'] ?? null,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
                'status' => 'pending',
            ]);

            \Log::info('[ORDER STORE] Order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_method' => $validated['payment_method'],
            ]);

            // Créer les lignes de commande
            foreach ($cartItems as $item) {
                $stock = $item->distributorStock;

                // Créer la ligne de commande avec prix figé
                OrderItem::create([
                    'order_id' => $order->id,
                    'distributor_stock_id' => $stock->id,
                    'quantity' => $item->quantity,
                    'unit_price' => $stock->unit_price,
                    'subtotal' => $item->quantity * $stock->unit_price,
                    'product_name' => $item->getProductName(),
                    'point_of_sale_name' => $stock->getPointOfSaleName(),
                ]);

                // Pour cash_on_delivery: décrémenter le stock immédiatement
                // Pour Stripe: le stock sera réservé par StripeController avant redirection
                if ($validated['payment_method'] === 'cash_on_delivery') {
                    $stock->decrement('quantity_available', $item->quantity);

                    // Mettre à jour le statut du stock si épuisé
                    if ($stock->quantity_available <= 0) {
                        $stock->update(['status' => 'out_of_stock']);
                    }
                }
            }

            // Vider le panier après création réussie
            // Pour Stripe: le panier est vidé après confirmation du paiement par webhook
            // Pour cash_on_delivery: le panier est vidé immédiatement
            if ($validated['payment_method'] === 'cash_on_delivery') {
                $deletedCount = CartItem::query()
                    ->forUser($user)
                    ->delete();

                \Log::info('[ORDER STORE] Cart cleared (cash_on_delivery)', [
                    'user_id' => $user->id,
                    'deleted_count' => $deletedCount,
                ]);
            } else {
                \Log::info('[ORDER STORE] Cart NOT cleared (stripe - will clear after webhook)', [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                ]);
            }

            // Redirection selon le mode de paiement
            if ($validated['payment_method'] === 'stripe') {
                // Rediriger vers Stripe
                \Log::info('[ORDER STORE] Redirecting to Stripe', ['order_id' => $order->id]);
                return redirect()->route('consumer.stripe.create', $order);
            }

            // Paiement à la livraison: afficher la commande
            return redirect()->route('consumer.orders.show', $order)
                ->with('success', "Commande #{$order->order_number} créée avec succès !");
        });
    }

    /**
     * Afficher les commandes de l'utilisateur
     */
    public function index(): View
    {
        $user = auth()->user();

        if (!$user->isConsumer()) {
            abort(403, 'Accès réservé aux consommateurs');
        }

        $orders = Order::query()
            ->forUser($user)
            ->with('orderItems')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('consumer.orders.index', compact('orders'));
    }

    /**
     * Afficher le détail d'une commande
     */
    public function show(Order $order): View
    {
        $user = auth()->user();

        if (!$user->isConsumer()) {
            abort(403, 'Accès réservé aux consommateurs');
        }

        // Vérifier que la commande appartient à l'utilisateur (isolation)
        if ($order->user_id !== $user->id) {
            abort(403, 'Accès non autorisé à cette commande.');
        }

        $order->load('orderItems.distributorStock.pointOfSale');

        return view('consumer.orders.show', compact('order'));
    }

    /**
     * Annuler une commande
     */
    public function cancel(Order $order): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isConsumer()) {
            abort(403, 'Accès réservé aux consommateurs');
        }

        // Vérifier que la commande appartient à l'utilisateur
        if ($order->user_id !== $user->id) {
            abort(403, 'Accès non autorisé à cette commande.');
        }

        // Vérifier que la commande peut être annulée
        if (!$order->canBeCancelled()) {
            return back()->with('error', 'Cette commande ne peut plus être annulée.');
        }

        // Transaction pour remettre le stock
        return DB::transaction(function () use ($order) {
            // Remettre le stock pour chaque article
            foreach ($order->orderItems as $item) {
                $item->distributorStock->increment('quantity_available', $item->quantity);
                
                // Remettre le statut du stock si nécessaire
                if ($item->distributorStock->quantity_available > 0) {
                    $item->distributorStock->update(['status' => 'available']);
                }
            }

            // Annuler la commande
            $order->update(['status' => 'cancelled']);

            return back()->with('success', 'Commande annulée avec succès.');
        });
    }
}
