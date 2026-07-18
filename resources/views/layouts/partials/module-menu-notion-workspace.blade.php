<div class="module-toolbar">
  <div class="module-toolbar-title">{{ __('common.nav.notion_workspace') }}</div>
  <div class="module-toolbar-links">
    <a href="{{ route('notion-workspace.index') }}" class="{{ request()->routeIs('notion-workspace.*') ? 'active' : '' }}">{{ __('common.nav.pages') }}</a>
  </div>
</div>
