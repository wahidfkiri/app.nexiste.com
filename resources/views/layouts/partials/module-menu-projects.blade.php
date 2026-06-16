<div class="module-toolbar">
  <div class="module-toolbar-title">Projets</div>
  <div class="module-toolbar-links">
    <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.index') ? 'active' : '' }}">Liste</a>
    <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.show') ? 'active' : '' }}">Kanban</a>
  </div>
</div>
