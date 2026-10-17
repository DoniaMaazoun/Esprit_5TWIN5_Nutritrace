<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransportModeController extends Controller
{
    /**
     * Module 3 - Gestion des modes de transport et facteurs d'émission ADEME
     */
    public function index(): View
    {
        $transportModes = TransportMode::withCount('legs')->orderBy('name')->paginate(15);

        return view('backend.pages.logistics.transport-modes.index', compact('transportModes'));
    }

    public function create(): View
    {
        return view('backend.pages.logistics.transport-modes.create', [
            'transportMode' => new TransportMode(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        TransportMode::create($this->validateData($request));

        return redirect()->route('backend.logistics.transport-modes.index')
            ->with('success', 'Mode de transport créé avec succès.');
    }

    public function edit(TransportMode $transportMode): View
    {
        return view('backend.pages.logistics.transport-modes.edit', compact('transportMode'));
    }

    public function update(Request $request, TransportMode $transportMode): RedirectResponse
    {
        // Les étapes déjà enregistrées gardent leur ancien facteur (copié au moment du calcul)
        $transportMode->update($this->validateData($request, $transportMode));

        return redirect()->route('backend.logistics.transport-modes.index')
            ->with('success', 'Mode de transport modifié avec succès.');
    }

    public function destroy(TransportMode $transportMode): RedirectResponse
    {
        if ($transportMode->legs()->exists()) {
            return back()->with('error', 'Ce mode est utilisé par des expéditions : désactivez-le plutôt que de le supprimer.');
        }

        $transportMode->delete();

        return redirect()->route('backend.logistics.transport-modes.index')
            ->with('success', 'Mode de transport supprimé.');
    }

    private function validateData(Request $request, ?TransportMode $transportMode = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50', 'alpha_dash',
                Rule::unique('transport_modes', 'code')->ignore($transportMode),
            ],
            'emission_factor' => ['required', 'numeric', 'min:0'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
