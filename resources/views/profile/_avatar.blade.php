{{-- Photo de profil ou initiales, à placer dans un conteneur rond existant. Variable : $user --}}
@if($url = $user->avatarUrl())
  <img src="{{ $url }}" alt="Photo de {{ $user->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block">
@else
  {{ $user->initials() }}
@endif
