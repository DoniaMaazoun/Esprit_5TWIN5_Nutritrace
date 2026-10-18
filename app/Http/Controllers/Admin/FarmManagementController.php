<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FarmRequest;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Back-office : contrôle de toutes les exploitations de tous les producteurs.
 */
class FarmManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'user_id', 'governorate', 'farming_type', 'status']);

        $farms = Farm::query()
            ->filter($filters)
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->with('user')
            ->withCount('harvests')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'farms' => Farm::count(),
            'producers' => Farm::distinct('user_id')->count('user_id'),
            'area' => Farm::sum('total_area'),
            'inactive' => Farm::where('status', '!=', 'active')->count(),
            'organic' => Farm::where('farming_type', 'organic')->count(),
            'located' => Farm::whereNotNull('latitude')->count(),
        ];
        $byStatus = collect(Farm::STATUSES)->map(fn ($label, $key) => Farm::where('status', $key)->count());

        $producers = User::where('role', 'pro')->where('pro_type', 'producer')->orderBy('company_name')->get(['id', 'name', 'company_name']);

        return view('backend.pages.farms.index', compact('farms', 'filters', 'stats', 'byStatus', 'producers'));
    }

    public function edit(Farm $farm): View
    {
        $farm->load('user');

        return view('backend.pages.farms.edit', compact('farm'));
    }

    public function update(FarmRequest $request, Farm $farm): RedirectResponse
    {
        $farm->update($request->validated());

        return redirect()->route('backend.farms.index')
            ->with('success', "L'exploitation « {$farm->name} » ({$farm->user->company_name}) a été mise à jour.");
    }

    /**
     * Changement rapide du statut depuis la liste (ex. suspendre une exploitation).
     */
    public function updateStatus(Request $request, Farm $farm): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Farm::STATUSES))]]);
        $farm->update($data);

        return back()->with('success', "Statut de « {$farm->name} » : {$farm->statusLabel()}.");
    }

    public function destroy(Farm $farm): RedirectResponse
    {
        $name = $farm->name;
        $count = $farm->harvests()->count();
        $farm->delete();

        return back()->with('success', "L'exploitation « {$name} » a été supprimée" . ($count ? " avec ses {$count} lot(s)." : '.'));
    }
}
