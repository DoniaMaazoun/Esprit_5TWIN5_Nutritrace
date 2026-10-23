<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Contrôleur Stripe pour les paiements
 * Gère les redirections et les webhooks
 */
class StripeController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Créer une session Checkout et rediriger vers Stripe
     */
    public function createCheckoutSession(Order $order): RedirectResponse
    {
        $user = auth()->user();

        \Log::info('[STRIPE CREATE] Start', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $user->id,
            'payment_method' => $order->payment_method,
        ]);

        if (!$user->isConsumer()) {
            abort(403, 'Accès réservé aux consommateurs');
        }

        // Vérifier que la commande appartient à l'utilisateur
        if ($order->user_id !== $user->id) {
            \Log::warning('[STRIPE CREATE] User mismatch', [
                'order_user_id' => $order->user_id,
                'current_user_id' => $user->id,
            ]);
            abort(403, 'Accès non autorisé à cette commande.');
        }

        // Vérifier que la commande est en attente
        if ($order->payment_status !== 'pending') {
            \Log::warning('[STRIPE CREATE] Order not pending', [
                'order_id' => $order->id,
                'payment_status' => $order->payment_status,
            ]);
            return redirect()->route('consumer.orders.show', $order)
                ->with('error', 'Cette commande a déjà été traitée.');
        }

        try {
            // Réserver le stock
            \Log::info('[STRIPE CREATE] Reserving stock', ['order_id' => $order->id]);
            $this->paymentService->reserveStock($order);

            // Créer la session Stripe
            $successUrl = route('consumer.stripe.success', ['order' => $order]);
            $cancelUrl = route('consumer.stripe.cancel', ['order' => $order]);

            \Log::info('[STRIPE CREATE] Creating session with URLs', [
                'order_id' => $order->id,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]);

            $session = $this->paymentService->createCheckoutSession($order, $successUrl, $cancelUrl);

            \Log::info('[STRIPE CREATE] Session created', [
                'order_id' => $order->id,
                'stripe_session_id' => $session->id,
                'stripe_url' => $session->url,
            ]);

            // Rediriger vers Stripe Checkout
            return redirect()->away($session->url);
        } catch (\Exception $e) {
            \Log::error('[STRIPE CREATE] Error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Libérer les réservations en cas d'erreur
            try {
                $this->paymentService->releaseReservation($order);
                \Log::info('[STRIPE CREATE] Reservations released after error');
            } catch (\Exception $releaseException) {
                \Log::error('[STRIPE CREATE] Error releasing reservations', [
                    'error' => $releaseException->getMessage(),
                ]);
            }
            // Rediriger vers la page de commande avec message d'erreur
            return redirect()->route('consumer.orders.show', $order)
                ->with('error', 'Erreur lors de la création de la session de paiement: ' . $e->getMessage());
        }
    }

    /**
     * Page de succès après paiement
     * Note: ne marque pas la commande comme payée (webhook le fait)
     */
    public function success(Order $order): View
    {
        $user = auth()->user();

        \Log::info('[STRIPE SUCCESS] Start', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $user->id,
            'payment_status' => $order->payment_status,
        ]);

        if (!$user->isConsumer()) {
            abort(403, 'Accès réservé aux consommateurs');
        }

        if ($order->user_id !== $user->id) {
            \Log::warning('[STRIPE SUCCESS] User mismatch');
            abort(403, 'Accès non autorisé à cette commande.');
        }

        return view('consumer.stripe.success', compact('order'));
    }

    /**
     * Page d'annulation après échec ou abandon
     */
    public function cancel(Order $order): View
    {
        $user = auth()->user();

        \Log::info('[STRIPE CANCEL] Start', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $user->id,
            'payment_status' => $order->payment_status,
        ]);

        if (!$user->isConsumer()) {
            abort(403, 'Accès réservé aux consommateurs');
        }

        if ($order->user_id !== $user->id) {
            \Log::warning('[STRIPE CANCEL] User mismatch');
            abort(403, 'Accès non autorisé à cette commande.');
        }

        // Libérer les réservations si la commande n'est pas payée
        if ($order->payment_status !== 'paid') {
            \Log::info('[STRIPE CANCEL] Releasing reservations', ['order_id' => $order->id]);
            $this->paymentService->releaseReservation($order);
        }

        return view('consumer.stripe.cancel', compact('order'));
    }

    /**
     * Webhook Stripe
     * Traite les événements de paiement
     * Exclu du middleware CSRF
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        \Log::info('[WEBHOOK] Received', [
            'event_type' => null, // Will be set after parsing
            'has_signature' => !empty($sigHeader),
            'has_webhook_secret' => !empty($webhookSecret),
        ]);

        if (!$webhookSecret) {
            \Log::error('[WEBHOOK] Webhook secret not configured');
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            \Log::error('[WEBHOOK] Invalid signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            \Log::error('[WEBHOOK] Invalid payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        \Log::info('[WEBHOOK] Event parsed', [
            'event_type' => $event->type,
            'event_id' => $event->id,
        ]);

        // Traiter les événements pertinents
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->paymentService->handleCheckoutSessionCompleted($session->id);
                break;

            case 'checkout.session.expired':
                $session = $event->data->object;
                $this->paymentService->handleCheckoutSessionExpired($session->id);
                break;

            case 'checkout.session.async_payment_succeeded':
                $session = $event->data->object;
                $this->paymentService->handleCheckoutSessionCompleted($session->id);
                break;

            case 'checkout.session.async_payment_failed':
                $session = $event->data->object;
                $this->paymentService->handleCheckoutSessionExpired($session->id);
                break;

            default:
                // Événement non géré
                \Log::info('[WEBHOOK] Event not handled', ['event_type' => $event->type]);
                break;
        }

        return response()->json(['status' => 'success']);
    }
}
