@if(auth()->check())
  @include('errors.404-auth')
@else
  @include('errors.404-guest')
@endif
