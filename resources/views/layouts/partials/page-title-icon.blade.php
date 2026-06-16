@php
  $iconValue = trim((string) ($icon ?? 'fas fa-puzzle-piece'));
  $iconClass = $iconValue;
  $iconUrl = trim((string) ($iconUrl ?? ''));
  $bg = trim((string) ($bg ?? 'var(--c-accent-lt)'));
  $color = trim((string) ($color ?? 'var(--c-accent)'));
  $alt = trim((string) ($alt ?? 'Icône'));

  if ($iconUrl === '' && preg_match('/\.(png|svg|jpe?g|gif|webp|avif|ico)(\?.*)?$/i', $iconValue) === 1) {
      if (preg_match('/^(data:|https?:\/\/|\/\/)/i', $iconValue) === 1) {
          $iconUrl = $iconValue;
      } elseif (\Illuminate\Support\Str::startsWith($iconValue, ['/storage/', 'storage/', '/'])) {
          $iconUrl = \Illuminate\Support\Str::startsWith($iconValue, 'storage/')
              ? asset($iconValue)
              : asset(ltrim($iconValue, '/'));
      } else {
          $iconUrl = asset('storage/' . ltrim($iconValue, '/'));
      }
  }
@endphp

<span class="page-title-module-icon" style="--pti-bg: {{ $bg }}; --pti-color: {{ $color }};">
  @if($iconUrl !== '')
    <img src="{{ $iconUrl }}" alt="{{ $alt }}">
  @else
    <i class="{{ $iconClass }}"></i>
  @endif
</span>
