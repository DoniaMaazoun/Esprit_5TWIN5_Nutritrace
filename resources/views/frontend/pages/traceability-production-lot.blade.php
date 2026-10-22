@extends('frontend.layouts.app')

@section('title', 'Traçabilité - ' . $lot->getProductName() . ' | Farm to Fork')

@section('content')

<div class="container" style="padding: 40px 0;">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" style="margin-bottom: 32px;">
    <ol class="breadcrumb" style="background: none; padding: 0; gap: 6px;">
      <li class="breadcrumb-item"><a href="{{ route('frontend.home') }}" style="color: #2D6A4F;">Farm to Fork</a></li>
      <li class="breadcrumb-item active">Traçabilité</li>
    </ol>
  </nav>

  <!-- Main Content -->
  <div class="row g-4">
    <!-- Left Column: Main Content -->
    <div class="col-lg-8">
      <!-- Header -->
      <div style="background: linear-gradient(135deg, #2D6A4F, #1B4332); color: #fff; padding: 32px; border-radius: 12px; margin-bottom: 32px;">
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
          <i class="fa-solid fa-cube" style="font-size: 2.5rem; opacity: 0.8;"></i>
          <div>
            <h1 style="margin: 0; font-size: 2rem; color: #fff;">{{ $lot->getProductName() }}</h1>
            <p style="margin: 4px 0 0; opacity: 0.9; font-size: 0.95rem;">Lot de production transformé</p>
          </div>
        </div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 12px;">
          <span class="tag" style="background: rgba(255,255,255,.2); color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem;">
            <i class="fa-solid fa-cube"></i> {{ $lot->getStatusLabel() }}
          </span>
          <span class="tag" style="background: rgba(255,255,255,.2); color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem;">
            <i class="fa-solid fa-calendar"></i> {{ $lot->production_date->format('d/m/Y') }}
          </span>
          <span class="tag" style="background: rgba(255,255,255,.2); color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem;">
            <i class="fa-solid fa-weight"></i> {{ $lot->getQuantityDisplay() }}
          </span>
        </div>
      </div>

      <!-- Lot Details Card -->
      <div style="background: #fff; border: 1px solid #e4e4e4; border-radius: 12px; padding: 28px; margin-bottom: 28px;">
        <h2 style="margin: 0 0 20px; font-size: 1.4rem; color: #1B4332; display: flex; align-items: center; gap: 10px;">
          <i class="fa-solid fa-info-circle"></i> Informations du Lot
        </h2>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
          <div>
            <p style="margin: 0 0 4px; font-size: 0.9rem; color: #999; font-weight: 600; text-transform: uppercase;">Recette</p>
            <p style="margin: 0; font-size: 1.1rem; color: #333; font-weight: 600;">{{ $lot->recipe->name }}</p>
          </div>
          <div>
            <p style="margin: 0 0 4px; font-size: 0.9rem; color: #999; font-weight: 600; text-transform: uppercase;">Processeur</p>
            <p style="margin: 0; font-size: 1.1rem; color: #333; font-weight: 600;">{{ $lot->processor->name }}</p>
          </div>
          <div>
            <p style="margin: 0 0 4px; font-size: 0.9rem; color: #999; font-weight: 600; text-transform: uppercase;">Date de Production</p>
            <p style="margin: 0; font-size: 1.1rem; color: #333; font-weight: 600;">{{ $lot->production_date->format('d/m/Y') }}</p>
          </div>
          <div>
            <p style="margin: 0 0 4px; font-size: 0.9rem; color: #999; font-weight: 600; text-transform: uppercase;">Date de Péremption</p>
            <p style="margin: 0; font-size: 1.1rem; color: #333; font-weight: 600;">
              {{ $lot->expiry_date->format('d/m/Y') }}
              @if($lot->isExpired())
                <span class="tag" style="background: #f8d7da; color: #721c24; margin-left: 8px;"><i class="fa-solid fa-exclamation-triangle"></i> Périmé</span>
              @elseif($lot->isCloseToExpiry())
                <span class="tag" style="background: #fff3cd; color: #856404; margin-left: 8px;"><i class="fa-solid fa-hourglass-half"></i> À consommer</span>
              @endif
            </p>
          </div>
        </div>

        @if($lot->production_notes)
          <div style="background: #f8f9fa; padding: 16px; border-radius: 8px; margin-top: 16px; border-left: 4px solid #2D6A4F;">
            <p style="margin: 0 0 8px; font-weight: 600; color: #1B4332;">Notes de Production</p>
            <p style="margin: 0; color: #555; white-space: pre-wrap; line-height: 1.6;">{{ $lot->production_notes }}</p>
          </div>
        @endif
      </div>

      <!-- Recipe & Ingredients -->
      <div style="background: #fff; border: 1px solid #e4e4e4; border-radius: 12px; padding: 28px; margin-bottom: 28px;">
        <h2 style="margin: 0 0 20px; font-size: 1.4rem; color: #1B4332; display: flex; align-items: center; gap: 10px;">
          <i class="fa-solid fa-list"></i> Ingrédients Utilisés ({{ $lot->getRecipeItems()->count() }})
        </h2>

        @if($lot->getRecipeItems()->isEmpty())
          <p style="color: #999; text-align: center; padding: 20px;">Aucun ingrédient</p>
        @else
          <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
              <thead>
                <tr style="background: #f8f9fa; border-bottom: 2px solid #e4e4e4;">
                  <th style="padding: 12px; text-align: left; color: #1B4332; font-weight: 600; font-size: 0.9rem;">Ingrédient</th>
                  <th style="padding: 12px; text-align: right; color: #1B4332; font-weight: 600; font-size: 0.9rem;">Quantité</th>
                  <th style="padding: 12px; text-align: left; color: #1B4332; font-weight: 600; font-size: 0.9rem;">Notes</th>
                </tr>
              </thead>
              <tbody>
                @foreach($lot->getRecipeItems() as $item)
                  <tr style="border-bottom: 1px solid #e4e4e4;">
                    <td style="padding: 14px 12px; color: #333; font-weight: 600;">{{ $item->raw_material_name }}</td>
                    <td style="padding: 14px 12px; text-align: right; color: #666;">
                      <span style="background: #f0f9f4; color: #1B4332; padding: 4px 10px; border-radius: 4px; display: inline-block; font-size: 0.9rem;">
                        {{ $item->quantity_required }} {{ $item->unit }}
                      </span>
                    </td>
                    <td style="padding: 14px 12px; color: #999; font-size: 0.9rem;">{{ $item->notes ?? '—' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <!-- Recipe Summary -->
          <div style="background: linear-gradient(135deg, #f0f9f4, #e8f5e9); border-left: 4px solid #2D6A4F; padding: 16px; border-radius: 8px; margin-top: 16px;">
            <p style="margin: 0 0 8px; font-weight: 600; color: #1B4332;">
              <i class="fa-solid fa-flask-vial"></i> Recette: {{ $lot->recipe->name }}
            </p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 0.9rem; color: #555;">
              <p style="margin: 0;"><strong>Ingrédients:</strong> {{ $lot->getRecipeItems()->count() }}</p>
              <p style="margin: 0;"><strong>Rendement:</strong> {{ $lot->recipe->getYieldDisplay() }}</p>
            </div>
          </div>
        @endif
      </div>

      <!-- Expiry Status -->
      @if($lot->isExpired())
        <div style="background: #f8d7da; border-left: 4px solid #dc3545; padding: 16px; border-radius: 8px; margin-bottom: 28px;">
          <p style="margin: 0; color: #721c24; font-weight: 600;">
            <i class="fa-solid fa-exclamation-triangle"></i> <strong>Ce lot est périmé</strong>
          </p>
          <p style="margin: 8px 0 0; color: #721c24; font-size: 0.95rem;">
            Ce lot ne doit pas être consommé. Date limite dépassée le {{ $lot->expiry_date->format('d/m/Y') }}.
          </p>
        </div>
      @elseif($lot->isCloseToExpiry())
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 16px; border-radius: 8px; margin-bottom: 28px;">
          <p style="margin: 0; color: #856404; font-weight: 600;">
            <i class="fa-solid fa-hourglass-half"></i> <strong>Péremption proche</strong>
          </p>
          <p style="margin: 8px 0 0; color: #856404; font-size: 0.95rem;">
            Ce lot expire le {{ $lot->expiry_date->format('d/m/Y') }}. À consommer en priorité.
          </p>
        </div>
      @else
        <div style="background: #e8f5e9; border-left: 4px solid #2e7d32; padding: 16px; border-radius: 8px; margin-bottom: 28px;">
          <p style="margin: 0; color: #1b5e20; font-weight: 600;">
            <i class="fa-solid fa-check-circle"></i> <strong>Lot valide</strong>
          </p>
          <p style="margin: 8px 0 0; color: #1b5e20; font-size: 0.95rem;">
            Ce lot est en bon état. Valide jusqu'au {{ $lot->expiry_date->format('d/m/Y') }}.
          </p>
        </div>
      @endif
    </div>

    <!-- Right Column: QR Code & Info -->
    <div class="col-lg-4">
      <!-- QR Code Card -->
      <div style="background: linear-gradient(135deg, #2D6A4F, #1B4332); color: #fff; border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 20px;">
        <h3 style="margin: 0 0 12px; font-size: 1.1rem;">Code de Traçabilité</h3>
        <div style="background: rgba(255,255,255,.1); padding: 16px; border-radius: 8px; margin: 0 -4px;">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode(route('traceability.show', ['code' => $lot->qr_code_data])) }}" 
               alt="QR Code {{ $lot->qr_code_data }}" 
               style="max-width: 100%; border-radius: 4px;">
        </div>
        <p style="margin: 12px 0 0; font-size: 0.8rem; opacity: 0.85; word-break: break-all;">{{ $lot->qr_code_data }}</p>
      </div>

      <!-- Processor Info -->
      <div style="background: #fff; border: 1px solid #e4e4e4; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
        <h3 style="margin: 0 0 12px; font-size: 1rem; color: #1B4332; display: flex; align-items: center; gap: 8px; font-weight: 600;">
          <i class="fa-solid fa-industry"></i> Processeur
        </h3>
        <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; border-left: 3px solid #2D6A4F;">
          <p style="margin: 0 0 4px; font-weight: 600; color: #333;">{{ $lot->processor->name }}</p>
          <p style="margin: 0; font-size: 0.9rem; color: #666;">{{ $lot->processor->email ?? '—' }}</p>
          @if($lot->processor->phone)
            <p style="margin: 4px 0 0; font-size: 0.9rem; color: #666;">{{ $lot->processor->phone }}</p>
          @endif
        </div>
      </div>

      <!-- Timeline Info -->
      <div style="background: #fff; border: 1px solid #e4e4e4; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
        <h3 style="margin: 0 0 12px; font-size: 1rem; color: #1B4332; display: flex; align-items: center; gap: 8px; font-weight: 600;">
          <i class="fa-solid fa-timeline"></i> Chronologie
        </h3>
        <div style="position: relative; padding-left: 20px;">
          <!-- Timeline Item 1 -->
          <div style="margin-bottom: 16px; position: relative;">
            <div style="position: absolute; left: -20px; top: 3px; width: 12px; height: 12px; background: #2D6A4F; border-radius: 50%;"></div>
            <p style="margin: 0 0 2px; font-size: 0.9rem; color: #999;">Production</p>
            <p style="margin: 0; font-weight: 600; color: #333;">{{ $lot->production_date->format('d M Y') }}</p>
          </div>

          <!-- Timeline Item 2 -->
          <div style="margin-bottom: 16px; position: relative;">
            <div style="position: absolute; left: -20px; top: 3px; width: 12px; height: 12px; background: #40B5AD; border-radius: 50%;"></div>
            <p style="margin: 0 0 2px; font-size: 0.9rem; color: #999;">Péremption</p>
            <p style="margin: 0; font-weight: 600; color: #333;">{{ $lot->expiry_date->format('d M Y') }}</p>
          </div>

          <!-- Timeline Item 3 -->
          <div style="position: relative;">
            <div style="position: absolute; left: -20px; top: 3px; width: 12px; height: 12px; background: #1B4332; border-radius: 50%;"></div>
            <p style="margin: 0 0 2px; font-size: 0.9rem; color: #999;">Créé le</p>
            <p style="margin: 0; font-weight: 600; color: #333;">{{ $lot->created_at->format('d M Y H:i') }}</p>
          </div>
        </div>
      </div>

      <!-- Share Info -->
      <div style="background: linear-gradient(135deg, #f0f9f4, #e8f5e9); border: 1px solid rgba(45,106,79,.2); border-radius: 12px; padding: 16px; text-align: center;">
        <i class="fa-solid fa-share-nodes" style="font-size: 1.5rem; color: #2D6A4F; margin-bottom: 8px; display: block;"></i>
        <p style="margin: 0 0 12px; font-size: 0.9rem; color: #1B4332;">
          <strong>Traçabilité Vérifiée</strong><br>
          Lot authentifié via Farm to Fork
        </p>
        <p style="margin: 0; font-size: 0.85rem; color: #666;">
          Cliquez sur le QR code pour vérifier l'authenticité
        </p>
      </div>
    </div>
  </div>
</div>

@endsection
