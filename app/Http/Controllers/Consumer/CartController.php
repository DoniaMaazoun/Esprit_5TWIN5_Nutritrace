<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\DistributorStock;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gestion du panier consommateur
 * Protégé par le middleware ConsumerMiddleware (role=consumer)
 */
class CartController extends Controller
{
    /**
     * Afficher le panier de l'utilisateur connecté
     */
    public function index(): View
    {
        $user = auth()->user();

        \Log::info('[CART INDEX] Start', [
            'user_id' => $user->id,
            'user_email' => $user->email,
        ]);

        if (!$user->isConsumer()) {
            abort(403, 'Accès réservé aux consommateurs');
        }

        // Charger les articles du panier avec les relations nécessaires
        $cartItems = CartItem::query()
            ->forUser($user)
            ->with(['distributorStock.productionLot.recipe', 'distributorStock.pointOfSale'])
            ->get();

        \Log::info('[CART INDEX] Cart items loaded', [
            'user_id' => $user->id,
            'count' => $cartItems->count(),
            'items' => $cartItems->map(fn($item) => ['id' => $item->id, 'stock_id' => $item->distributor_stock_id, 'qty' => $item->quantity])->toArray(),
        ]);

        // Calculer le total du panier (recalculé depuis la base)
        $total = $cartItems->sum(fn (CartItem $item) => $item->getSubtotal());

        // Vérifier les articles qui ne sont plus disponibles en stock
        $unavailableItems = $cartItems->filter(fn (CartItem $item) => !$item->isQuantityAvailable());

        return view('consumer.cart.index', compact('cartItems', 'total', 'unavailableItems'));
    }

    /**
     * Ajouter un produit au panier
     */
    public function add(Request $request, DistributorStock $stock): RedirectResponse
    {
        $user = auth()->user();

        \Log::info('[CART ADD] Start', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'stock_id' => $stock->id,
        ]);

        if (!$user->isConsumer()) {
            abort(403, 'Accès réservé aux consommateurs');
        }

        // Vérifier que le stock est disponible
        if (!$stock->isAvailable()) {
            \Log::warning('[CART ADD] Stock not available', ['stock_id' => $stock->id]);
            return back()->with('error', 'Ce produit n\'est plus disponible.');
        }

        // Valider la quantité demandée
        $quantity = $request->input('quantity', 1);
        if ($quantity <= 0) {
            return back()->with('error', 'La quantité doit être supérieure à 0.');
        }

        if ($quantity > $stock->quantity_available) {
            return back()->with('error', "Quantité demandée ({$quantity}) supérieure au stock disponible ({$stock->quantity_available}).");
        }

        // Vérifier si l'article est déjà dans le panier
        $existingItem = CartItem::query()
            ->forUser($user)
            ->where('distributor_stock_id', $stock->id)
            ->first();

        if ($existingItem) {
            // Mettre à jour la quantité si déjà présent
            $newQuantity = $existingItem->quantity + $quantity;

            if ($newQuantity > $stock->quantity_available) {
                return back()->with('error', "La quantité totale ({$newQuantity}) dépasse le stock disponible ({$stock->quantity_available}).");
            }

            $existingItem->update([
                'quantity' => $newQuantity,
                'unit_price_at_addition' => $stock->unit_price,
            ]);

            \Log::info('[CART ADD] Updated existing item', [
                'cart_item_id' => $existingItem->id,
                'new_quantity' => $newQuantity,
            ]);

            return back()->with('success', 'Quantité mise à jour dans le panier.');
        }

        // Ajouter le nouvel article
        $cartItem = CartItem::create([
            'user_id' => $user->id,
            'distributor_stock_id' => $stock->id,
            'quantity' => $quantity,
            'unit_price_at_addition' => $stock->unit_price,
        ]);

        \Log::info('[CART ADD] Created new item', [
            'cart_item_id' => $cartItem->id,
            'user_id' => $user->id,
            'stock_id' => $stock->id,
            'quantity' => $quantity,
        ]);

        return back()->with('success', 'Produit ajouté au panier.');
    }

    /**
     * Mettre à jour la quantité d'un article
     */
    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isConsumer()) {
            abort(403, 'Accès réservé aux consommateurs');
        }

        // Vérifier que l'article appartient à l'utilisateur (isolation)
        if ($cartItem->user_id !== $user->id) {
            abort(403, 'Accès non autorisé à cet article.');
        }

        $quantity = $request->input('quantity', 1);

        if ($quantity <= 0) {
            // Si quantité <= 0, supprimer l'article
            $cartItem->delete();
            return back()->with('success', 'Article supprimé du panier.');
        }

        $stock = $cartItem->distributorStock;

        if ($quantity > $stock->quantity_available) {
            return back()->with('error', "Quantité demandée ({$quantity}) supérieure au stock disponible ({$stock->quantity_available}).");
        }

        $cartItem->update([
            'quantity' => $quantity,
        ]);

        return back()->with('success', 'Quantité mise à jour.');
    }

    /**
     * Supprimer un article du panier
     */
    public function remove(CartItem $cartItem): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isConsumer()) {
            abort(403, 'Accès réservé aux consommateurs');
        }

        // Vérifier que l'article appartient à l'utilisateur (isolation)
        if ($cartItem->user_id !== $user->id) {
            abort(403, 'Accès non autorisé à cet article.');
        }

        $cartItem->delete();

        return back()->with('success', 'Article supprimé du panier.');
    }

    /**
     * Vider le panier
     */
    public function clear(): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isConsumer()) {
            abort(403, 'Accès réservé aux consommateurs');
        }

        CartItem::query()
            ->forUser($user)
            ->delete();

        return back()->with('success', 'Panier vidé.');
    }
}
