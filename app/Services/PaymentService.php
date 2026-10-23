<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session;
use Stripe\Stripe;

/**
 * Service de paiement Stripe
 * Gère la création de sessions Checkout et le traitement des webhooks
 */
class PaymentService
{
    /**
     * Créer une session Stripe Checkout pour une commande
     */
    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): Session
    {
        $stripeSecret = config('services.stripe.secret');

        \Log::info('[PAYMENT SERVICE] Creating checkout session', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'has_stripe_secret' => !empty($stripeSecret),
            'stripe_currency' => config('services.stripe.currency', 'usd'),
        ]);

        if (empty($stripeSecret)) {
            \Log::error('[PAYMENT SERVICE] Stripe secret not configured');
            throw new \Exception('Stripe n\'est pas configuré. Veuillez configurer STRIPE_SECRET dans le fichier .env');
        }

        Stripe::setApiKey($stripeSecret);

        // Convertir le montant en centimes (Stripe utilise les centimes)
        $amountInCents = (int) round($order->total_amount * 100);

        $session = Session::create([
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => config('services.stripe.currency', 'usd'),
                        'product_data' => [
                            'name' => "Commande #{$order->order_number}",
                            'description' => count($order->orderItems) . ' article(s)',
                        ],
                        'unit_amount' => $amountInCents,
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $order->user_id,
            ],
            'expires_at' => now()->addHours(24)->timestamp, // Session expire après 24h
        ]);

        \Log::info('[PAYMENT SERVICE] Session created successfully', [
            'order_id' => $order->id,
            'stripe_session_id' => $session->id,
        ]);

        // Mettre à jour la commande avec l'ID de session Stripe
        $order->update([
            'stripe_session_id' => $session->id,
            'stripe_amount' => $amountInCents,
            'stripe_status' => 'created',
        ]);

        return $session;
    }

    /**
     * Traiter le webhook Stripe checkout.session.completed
     * Idempotent: ne traite pas deux fois le même paiement
     */
    public function handleCheckoutSessionCompleted(string $sessionId): void
    {
        DB::transaction(function () use ($sessionId) {
            // Récupérer la commande par session_id
            $order = Order::where('stripe_session_id', $sessionId)->firstOrFail();

            \Log::info('[WEBHOOK checkout.session.completed] Start', [
                'session_id' => $sessionId,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_status' => $order->payment_status,
            ]);

            // Idempotence: si déjà payé, ne rien faire
            if ($order->payment_status === 'paid') {
                \Log::info('[WEBHOOK checkout.session.completed] Already paid, skipping', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            // Décrémenter le stock pour chaque article
            foreach ($order->orderItems as $item) {
                $stock = $item->distributorStock;

                // Libérer la réservation et décrémenter le stock vendable
                $stock->decrement('quantity_reserved', $item->quantity);
                $stock->decrement('quantity_available', $item->quantity);

                // Mettre à jour le statut si épuisé
                if ($stock->quantity_available <= 0) {
                    $stock->update(['status' => 'out_of_stock']);
                }
            }

            // Marquer la commande comme payée
            $order->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
                'stripe_status' => 'succeeded',
            ]);

            \Log::info('[WEBHOOK checkout.session.completed] Order marked as paid', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            // Vider le panier de l'utilisateur correspondant aux articles de cette commande
            // Supprimer uniquement les articles du panier qui correspondent aux order_items
            $cartItemIdsToDelete = [];
            foreach ($order->orderItems as $item) {
                $cartItem = CartItem::query()
                    ->where('user_id', $order->user_id)
                    ->where('distributor_stock_id', $item->distributor_stock_id)
                    ->first();
                if ($cartItem) {
                    $cartItemIdsToDelete[] = $cartItem->id;
                }
            }

            if (!empty($cartItemIdsToDelete)) {
                $deletedCount = CartItem::whereIn('id', $cartItemIdsToDelete)->delete();
                \Log::info('[WEBHOOK checkout.session.completed] Cart cleared', [
                    'order_id' => $order->id,
                    'deleted_count' => $deletedCount,
                    'cart_item_ids' => $cartItemIdsToDelete,
                ]);
            } else {
                \Log::info('[WEBHOOK checkout.session.completed] No cart items to clear', [
                    'order_id' => $order->id,
                ]);
            }
        });
    }

    /**
     * Traiter le webhook Stripe checkout.session.expired
     * Libère les réservations de stock
     */
    public function handleCheckoutSessionExpired(string $sessionId): void
    {
        DB::transaction(function () use ($sessionId) {
            $order = Order::where('stripe_session_id', $sessionId)->first();

            \Log::info('[WEBHOOK checkout.session.expired] Start', [
                'session_id' => $sessionId,
                'order_id' => $order ? $order->id : null,
            ]);

            if (!$order || $order->payment_status === 'paid') {
                \Log::info('[WEBHOOK checkout.session.expired] No order or already paid, skipping');
                return;
            }

            // Libérer les réservations
            foreach ($order->orderItems as $item) {
                $stock = $item->distributorStock;
                $stock->decrement('quantity_reserved', $item->quantity);
                $stock->increment('quantity_available', $item->quantity);
            }

            // Marquer la commande comme échouée
            $order->update([
                'payment_status' => 'failed',
                'stripe_status' => 'canceled',
            ]);

            \Log::info('[WEBHOOK checkout.session.expired] Order marked as failed, reservations released', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        });
    }

    /**
     * Réserver le stock pour une commande Stripe
     * À appeler avant de rediriger vers Stripe
     */
    public function reserveStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->orderItems as $item) {
                $stock = $item->distributorStock;

                // Verrouiller la ligne pour éviter les concurrents
                $stock = \App\Models\DistributorStock::where('id', $stock->id)
                    ->lockForUpdate()
                    ->first();

                // Vérifier que le stock est disponible
                if ($stock->quantity_available < $item->quantity) {
                    throw new \Exception("Stock insuffisant pour: {$item->product_name}");
                }

                // Réserver la quantité
                $stock->increment('quantity_reserved', $item->quantity);
                $stock->decrement('quantity_available', $item->quantity);
            }
        });
    }

    /**
     * Libérer les réservations de stock pour une commande
     */
    public function releaseReservation(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->orderItems as $item) {
                $stock = $item->distributorStock;
                $stock->decrement('quantity_reserved', $item->quantity);
                $stock->increment('quantity_available', $item->quantity);
            }
        });
    }
}
