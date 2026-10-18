@php
  $colors = ['planned' => 'secondary', 'in_transit' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger'];
@endphp
<span class="badge bg-{{ $colors[$shipment->status] ?? 'secondary' }}">{{ $shipment->getStatusLabel() }}</span>
