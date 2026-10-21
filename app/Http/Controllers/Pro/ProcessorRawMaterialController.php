<?php

namespace App\Http\Controllers\Pro;

use App\Http\Controllers\Controller;
use App\Models\Harvest;
use App\Models\RawMaterialReception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProcessorRawMaterialController extends Controller
{
    use AuthorizesRequests;
    /**
     * Show list of available harvests for reception
     */
    public function index(): View
    {
        $user = auth()->user();

        // Get all available harvests (status: in_stock) with pagination
        $availableHarvests = Harvest::where('status', 'in_stock')
            ->with('farm.user', 'category')
            ->paginate(4);

        // Get already registered harvest IDs for this processor
        $registeredHarvestIds = RawMaterialReception::where('processor_id', $user->id)
            ->pluck('harvest_id')
            ->toArray();

        // Get all receptions for this processor
        $receptions = RawMaterialReception::where('processor_id', $user->id)
            ->with('harvest.farm.user', 'harvest.category')
            ->orderBy('reception_date', 'desc')
            ->paginate(4);

        // Get summary stats
        $stats = [
            'total_pending' => RawMaterialReception::where('processor_id', $user->id)
                ->where('status', 'pending')
                ->count(),
            'total_received' => RawMaterialReception::where('processor_id', $user->id)
                ->where('status', 'received')
                ->count(),
            'total_rejected' => RawMaterialReception::where('processor_id', $user->id)
                ->where('status', 'rejected')
                ->count(),
            'total_kg_received' => RawMaterialReception::where('processor_id', $user->id)
                ->where('status', 'received')
                ->sum('quantity_received'),
        ];

        return view('pro.processor.raw-materials.index', [
            'availableHarvests' => $availableHarvests,
            'registeredHarvestIds' => $registeredHarvestIds,
            'receptions' => $receptions,
            'stats' => $stats,
        ]);
    }

    /**
     * Show form to register new reception
     */
    public function create(Harvest $harvest): View
    {
        return view('pro.processor.raw-materials.create', [
            'harvest' => $harvest->load('farm.user', 'category'),
        ]);
    }

    /**
     * Store new raw material reception
     */
    public function store(Request $request, Harvest $harvest): RedirectResponse
    {
        $validated = $request->validate([
            'quantity_received' => 'required|numeric|min:0.01',
            'reception_date' => 'required|date|before_or_equal:today',
            'quality_status' => 'required|in:good,acceptable,damaged',
            'quality_notes' => 'nullable|string|max:500',
            'storage_location' => 'nullable|string|max:255',
            'temperature' => 'nullable|numeric|between:-50,50',
            'internal_notes' => 'nullable|string|max:500',
        ], [
            'quantity_received.required' => 'La quantité reçue est obligatoire',
            'quantity_received.numeric' => 'La quantité doit être un nombre',
            'reception_date.required' => 'La date de réception est obligatoire',
            'quality_status.required' => 'Le statut de qualité est obligatoire',
        ]);

        // Create reception record
        $reception = RawMaterialReception::create([
            'harvest_id' => $harvest->id,
            'processor_id' => auth()->id(),
            'quantity_received' => $validated['quantity_received'],
            'unit' => 'kg',
            'reception_date' => $validated['reception_date'],
            'status' => 'pending', // Initially pending for verification
            'quality_status' => $validated['quality_status'],
            'quality_notes' => $validated['quality_notes'],
            'storage_location' => $validated['storage_location'],
            'temperature' => $validated['temperature'],
            'internal_notes' => $validated['internal_notes'],
        ]);

        return redirect()
            ->route('pro.processor.raw-materials.show', $reception)
            ->with('success', 'Réception de matière brute enregistrée avec succès!');
    }

    /**
     * Show reception details
     */
    public function show(RawMaterialReception $reception): View
    {
        $this->authorize('view', $reception);

        return view('pro.processor.raw-materials.show', [
            'reception' => $reception->load('harvest.farm.user', 'harvest.category', 'processor'),
        ]);
    }

    /**
     * Show form to edit reception
     */
    public function edit(RawMaterialReception $reception): View
    {
        $this->authorize('update', $reception);

        return view('pro.processor.raw-materials.edit', [
            'reception' => $reception->load('harvest.farm.user', 'harvest.category'),
        ]);
    }

    /**
     * Update reception details
     */
    public function update(Request $request, RawMaterialReception $reception): RedirectResponse
    {
        $this->authorize('update', $reception);

        $validated = $request->validate([
            'quantity_received' => 'required|numeric|min:0.01',
            'quality_status' => 'required|in:good,acceptable,damaged',
            'quality_notes' => 'nullable|string|max:500',
            'storage_location' => 'nullable|string|max:255',
            'temperature' => 'nullable|numeric|between:-50,50',
            'internal_notes' => 'nullable|string|max:500',
        ]);

        $reception->update($validated);

        return redirect()
            ->route('pro.processor.raw-materials.show', $reception)
            ->with('success', 'Réception mise à jour avec succès!');
    }

    /**
     * Mark reception as received (accept)
     */
    public function approve(RawMaterialReception $reception): RedirectResponse
    {
        try {
            $this->authorize('update', $reception);
            $reception->markAsReceived();

            return redirect()
                ->route('pro.processor.raw-materials.show', $reception)
                ->with('success', 'Réception marquée comme reçue! ✅');
        } catch (\Exception $e) {
            \Log::error('Approve error: ' . $e->getMessage());
            return redirect()
                ->route('pro.processor.raw-materials.show', $reception)
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Mark reception as rejected
     */
    public function reject(Request $request, RawMaterialReception $reception): RedirectResponse
    {
        $this->authorize('update', $reception);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10|max:500',
        ], [
            'rejection_reason.required' => 'La raison du rejet est obligatoire',
            'rejection_reason.min' => 'La raison doit contenir au moins 10 caractères',
        ]);

        $reception->markAsRejected($validated['rejection_reason']);

        return redirect()
            ->route('pro.processor.raw-materials.show', $reception)
            ->with('warning', 'Réception rejetée. ❌');
    }

    /**
     * Delete a reception record
     */
    public function destroy(RawMaterialReception $reception): RedirectResponse
    {
        $this->authorize('delete', $reception);

        $reception->delete();

        return redirect()
            ->route('pro.processor.raw-materials.index')
            ->with('success', 'Réception supprimée.');
    }
}
