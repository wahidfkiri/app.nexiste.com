<div class="module-toolbar">
  <div class="module-toolbar-title">{{ __('common.nav.projects') }}</div>
  <div class="module-toolbar-links">
    <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.index') ? 'active' : '' }}">{{ __('common.nav.list') }}</a>
    <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.show') ? 'active' : '' }}">{{ __('common.nav.kanban') }}</a>
  </div>
</div>
