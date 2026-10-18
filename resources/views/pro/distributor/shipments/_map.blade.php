{{-- Carte Leaflet du trajet : départ (ferme du lot), étapes colorées par mode, destination --}}
@php($map = $shipment->mapData())

<div class="card distributor-card mb-4">
  <div class="card-header py-3"><i class="fa-solid fa-map-location-dot"></i> Carte du trajet</div>
  <div class="card-body">
    @if(count($map['markers']) === 0)
      <div class="alert alert-info mb-0">
        Aucune position connue pour ce trajet : ajoutez les coordonnées d'arrivée des étapes, ou géolocalisez la ferme du lot et le point de vente.
      </div>
    @else
      <div id="shipment-map" style="height: 380px; border-radius: 0.75rem;"></div>

      @if(count($map['segments']) > 0)
        <div class="d-flex flex-wrap gap-3 small mt-2">
          @foreach(collect($shipment->legs)->pluck('transportMode')->unique('id') as $mode)
            <span><span style="display:inline-block;width:18px;height:4px;vertical-align:middle;background:{{ \App\Models\Shipment::MODE_COLORS[$mode->code] ?? '#6c757d' }}"></span> {{ $mode->name }}</span>
          @endforeach
        </div>
      @endif
    @endif

    @if(count($map['missing']) > 0)
      <div class="alert alert-warning small mt-3 mb-0">
        <i class="fa-solid fa-circle-info"></i> Non placé(s) sur la carte, faute de coordonnées :
        {{ implode(' · ', $map['missing']) }}. Renseignez la latitude et la longitude d'arrivée en modifiant l'étape.
      </div>
    @endif
  </div>
</div>

@if(count($map['markers']) > 0)
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
  <script>
    (() => {
      const data = @json($map);
      const map = L.map('shipment-map');
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap',
      }).addTo(map);

      const colors = { start: '#2d5a27', step: '#6c757d', end: '#28a745' };
      const bounds = [];

      data.segments.forEach((segment) => {
        L.polyline([segment.from, segment.to], { color: segment.color, weight: 5, opacity: 0.85 })
          .bindTooltip(segment.label, { sticky: true })
          .addTo(map);
      });

      data.markers.forEach((marker) => {
        L.circleMarker(marker.position, {
          radius: marker.type === 'step' ? 6 : 9,
          color: '#fff',
          weight: 2,
          fillColor: colors[marker.type],
          fillOpacity: 1,
        }).bindPopup(marker.label).addTo(map);
        bounds.push(marker.position);
      });

      if (bounds.length === 1) {
        map.setView(bounds[0], 10);
      } else {
        map.fitBounds(bounds, { padding: [30, 30] });
      }
    })();
  </script>
@endif
