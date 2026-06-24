<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" href="{{asset('logo.png')}}" type="image/png">
  <title>@yield('title', 'CRM') - {{ config('app.name') }}</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('vendor/client/css/crm.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/invoice/css/invoice.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/stock/css/stock.css') }}">
  <link rel="stylesheet" href="{{ asset('css/global-font.css') }}">
  <style>
    .global-search-wrap{position:relative;min-width:320px;max-width:520px;width:42vw}
    .global-search-wrap input{padding-left:36px}
    .global-search-wrap .fa-search{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--c-ink-30);font-size:13px}
    .global-search-suggest{position:absolute;top:calc(100% + 8px);left:0;right:0;background:#fff;border:1px solid var(--c-ink-05);border-radius:12px;box-shadow:0 16px 40px rgba(15,23,42,.12);padding:8px;display:none;z-index:90;max-height:380px;overflow:auto}
    .global-search-group{padding:6px 8px 4px;font-size:11px;color:var(--c-ink-40);text-transform:uppercase;letter-spacing:.04em;font-weight:700}
    .global-search-item{display:flex;align-items:center;gap:10px;padding:10px 10px;border-radius:8px;text-decoration:none;color:var(--c-ink)}
    .global-search-item:hover{background:var(--c-accent-xl)}
    .global-search-item.is-active{background:var(--c-accent-xl);outline:1px solid var(--c-accent-lt)}
    .global-search-item small{display:block;color:var(--c-ink-40);font-size:12px}
    .global-search-meta{display:flex;align-items:center;justify-content:space-between;gap:10px}
    .global-search-badge{font-size:10px;padding:2px 7px;border-radius:999px;background:var(--c-accent-xl);color:var(--c-accent);font-weight:700;white-space:nowrap}
    .global-search-empty,.global-search-loading{padding:12px 10px;color:var(--c-ink-50);font-size:13px}
    .crm-header-actions{display:flex;align-items:center;gap:10px}
    .sidebar-compact-toggle.is-active{
      background:rgba(37,99,235,.12);
      color:var(--c-accent);
      border-color:rgba(37,99,235,.18);
      box-shadow:0 8px 24px rgba(37,99,235,.12);
    }
    .crm-layout > .crm-main{padding-top:var(--header-h, 64px)}
    .crm-layout > .crm-main > .crm-header{
      position:fixed !important;
      top:0;
      left:var(--sidebar-w, 260px);
      right:0;
      z-index:80;
    }
    .header-notif-wrap{position:relative}
    .header-notif-wrap .btn-icon{position:relative}
    .header-notif-badge{
      position:absolute;
      top:-3px;
      right:-3px;
      min-width:18px;
      height:18px;
      padding:0 5px;
      border-radius:999px;
      background:#dc2626;
      color:#fff;
      font-size:10px;
      font-weight:800;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      box-shadow:0 6px 16px rgba(220,38,38,.26);
    }
    .header-notif-dropdown{
      position:absolute;
      top:calc(100% + 12px);
      right:0;
      width:min(420px, calc(100vw - 28px));
      background:#fff;
      border:1px solid var(--c-ink-05);
      border-radius:18px;
      box-shadow:0 20px 46px rgba(15,23,42,.16);
      overflow:hidden;
      display:none;
      z-index:120;
    }
    .header-notif-wrap.open .header-notif-dropdown{display:block}
    .header-notif-header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      padding:14px 16px;
      border-bottom:1px solid var(--c-ink-05);
      background:linear-gradient(180deg,rgba(37,99,235,.06),rgba(255,255,255,0));
    }
    .header-notif-header strong{display:block;font-size:14px;color:var(--c-ink)}
    .header-notif-header span{font-size:12px;color:var(--c-ink-45)}
    .header-notif-counter{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-width:28px;
      height:28px;
      padding:0 9px;
      border-radius:999px;
      background:rgba(37,99,235,.12);
      color:#2563eb;
      font-size:12px;
      font-weight:800;
    }
    .header-notif-list{max-height:420px;overflow:auto;padding:8px}
    .header-notif-item{
      display:flex;
      align-items:flex-start;
      gap:12px;
      padding:12px;
      border-radius:14px;
      text-decoration:none;
      color:inherit;
      transition:background .18s ease,border-color .18s ease;
      border:1px solid transparent;
    }
    .header-notif-item:hover{
      background:var(--c-accent-xl);
      border-color:rgba(37,99,235,.12);
    }
    .header-notif-item.is-unread{
      background:rgba(245,158,11,.08);
      border-color:rgba(245,158,11,.14);
    }
    .header-notif-icon{
      width:40px;
      height:40px;
      border-radius:12px;
      background:rgba(37,99,235,.10);
      color:var(--notif-accent, #2563eb);
      display:inline-flex;
      align-items:center;
      justify-content:center;
      flex:0 0 auto;
      box-shadow:inset 0 0 0 1px rgba(37,99,235,.12);
    }
    .header-notif-copy{display:flex;flex-direction:column;gap:4px;min-width:0}
    .header-notif-copy strong{font-size:13px;color:var(--c-ink)}
    .header-notif-copy small{font-size:12px;line-height:1.45;color:var(--c-ink-50)}
    .header-notif-copy em{font-size:11px;font-style:normal;color:var(--c-ink-35)}
    .header-notif-empty{
      padding:22px 16px;
      text-align:center;
      color:var(--c-ink-45);
      font-size:13px;
    }
    .apps-drawer{height:100vh;max-height:100vh;border-radius:0;max-width:420px;margin-left:auto}
    .apps-drawer-list{display:flex;flex-direction:column;gap:10px}
    .apps-drawer-category{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--c-ink-40);font-weight:700;padding:8px 2px 0}
    .apps-drawer-item{display:flex;align-items:center;gap:10px;padding:11px 12px;border:1px solid var(--c-ink-05);border-radius:10px;text-decoration:none;color:var(--c-ink);transition:all .2s ease}
    .apps-drawer-item:hover{border-color:var(--c-accent);background:var(--c-accent-xl)}
    .apps-drawer-icon{width:34px;height:34px;border-radius:8px;background:var(--surface-1);display:flex;align-items:center;justify-content:center;color:var(--c-accent)}
    .apps-drawer-icon img{width:20px;height:20px;object-fit:contain;display:block}
    .apps-drawer-badge{margin-left:auto;font-size:10px;padding:2px 7px;border-radius:999px;background:var(--c-success-lt);color:var(--c-success);font-weight:700}
    .automation-drawer{width:min(760px,calc(100vw - 32px));max-width:760px;max-height:min(86vh,920px);border-radius:24px;margin:0 auto}
    .automation-summary{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;border:1px solid var(--c-ink-05);border-radius:14px;background:linear-gradient(135deg,rgba(37,99,235,.08),rgba(15,118,110,.08))}
    .automation-summary-copy{display:flex;flex-direction:column;gap:4px}
    .automation-summary-copy strong{font-size:14px;color:var(--c-ink)}
    .automation-summary-copy span{font-size:12px;color:var(--c-ink-50)}
    .automation-summary-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
    .automation-list{display:flex;flex-direction:column;gap:12px;margin-top:14px;max-height:calc(min(86vh,920px) - 280px);overflow:auto;padding-right:4px}
    .automation-empty{margin-top:14px;padding:18px 16px;border:1px dashed var(--c-ink-10);border-radius:14px;text-align:center;color:var(--c-ink-50);background:var(--surface-0)}
    .automation-success{
      display:none;
      margin-top:16px;
      min-height:280px;
      padding:36px 24px;
      border:1px solid rgba(15,157,88,.14);
      border-radius:18px;
      background:linear-gradient(180deg,rgba(15,157,88,.08),rgba(255,255,255,0));
      align-items:center;
      justify-content:center;
      text-align:center;
    }
    .automation-success.is-visible{display:flex}
    .automation-success-shell{max-width:380px;display:flex;flex-direction:column;align-items:center;gap:14px}
    .automation-success-icon{
      width:78px;
      height:78px;
      border-radius:999px;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      background:rgba(15,157,88,.12);
      color:#0f9d58;
      box-shadow:0 18px 36px rgba(15,157,88,.16), inset 0 0 0 1px rgba(15,157,88,.12);
      font-size:34px;
    }
    .automation-success-title{font-size:24px;font-weight:800;color:var(--c-ink);letter-spacing:-.02em}
    .automation-success-text{font-size:14px;line-height:1.7;color:var(--c-ink-50)}
    .automation-card{border:1px solid var(--c-ink-05);border-radius:16px;padding:14px;background:#fff;box-shadow:0 10px 24px rgba(15,23,42,.05);transition:height .24s ease,margin .24s ease,padding .24s ease,border-width .24s ease,border-color .18s ease,background .18s ease;overflow:hidden;transform-origin:center top;will-change:transform,opacity,height}
    .automation-card.is-accepted{border-color:rgba(15,157,88,.24);background:rgba(15,157,88,.03)}
    .automation-card.is-rejected{border-color:rgba(148,163,184,.24);background:rgba(148,163,184,.06)}
    .automation-card.is-removing{pointer-events:none;animation:automation-card-dismiss .3s cubic-bezier(.22,.61,.36,1) forwards}
    @keyframes automation-card-dismiss{
      0%{opacity:1;transform:translateX(0) scale(1);filter:blur(0)}
      100%{opacity:0;transform:translateX(22px) scale(.975);filter:blur(2px)}
    }
    .automation-card-head{display:flex;align-items:flex-start;gap:12px}
    .automation-card-icon{width:46px;height:46px;border-radius:14px;display:flex;align-items:center;justify-content:center;flex:0 0 auto;font-size:18px}
    .automation-card-copy{flex:1;min-width:0}
    .automation-card-title-row{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
    .automation-card-title-main{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex:1;min-width:0}
    .automation-card-title-row h4{margin:0;font-size:15px;line-height:1.4;color:var(--c-ink)}
    .automation-card-title-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end}
    .automation-card-inline-actions{display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap}
    .automation-card-inline-actions .btn{padding:6px 10px;font-size:11px;min-height:32px}
    .automation-card-meta{display:flex;flex-wrap:wrap;gap:8px 14px;margin-top:8px;font-size:12px;color:var(--c-ink-45)}
    .automation-card-meta span{display:inline-flex;align-items:center;gap:6px}
    .automation-card-actions{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;margin-top:14px}
    .automation-status-pill{display:inline-flex;align-items:center;justify-content:center;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;background:rgba(37,99,235,.12);color:#2563eb}
    .automation-status-pill.is-accepted{background:rgba(15,157,88,.14);color:#0f9d58}
    .automation-status-pill.is-rejected{background:rgba(100,116,139,.14);color:#475569}
    .automation-status-pill.is-expired{background:rgba(234,88,12,.14);color:#c2410c}
    .modal-header-copy{flex:1;min-width:0}
    .modal-header-actions{display:flex;align-items:center;gap:10px;margin-left:auto}
    .modal-icon-link{width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:10px;border:1px solid var(--c-ink-05);background:var(--surface-1);color:var(--c-ink-50);text-decoration:none;transition:all .2s ease}
    .modal-icon-link:hover{border-color:var(--c-accent-lt);background:var(--c-accent-xl);color:var(--c-accent)}
    .modal-overlay.modal-overlay-right{justify-content:flex-end}
    .modal-overlay.modal-overlay-right .modal{transform:translateX(22px)}
    .modal-overlay.modal-overlay-right.open .modal{transform:translateX(0)}
    @media (max-width: 768px){
      .automation-drawer{width:calc(100vw - 18px);max-height:calc(100vh - 18px);border-radius:20px}
      .automation-summary{flex-direction:column;align-items:stretch}
      .automation-summary-actions{justify-content:stretch}
      .automation-summary-actions .btn{flex:1}
      .automation-list{max-height:calc(100vh - 360px)}
      .automation-card-title-row,
      .automation-card-title-main{
        flex-direction:column;
        align-items:flex-start;
      }
      .automation-card-title-actions{
        width:100%;
        justify-content:flex-start;
      }
    }
    .module-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:14px 20px 0;padding:10px 14px;background:var(--surface-0);border:1px solid var(--c-ink-05);border-radius:12px}
    .module-toolbar-title{font-size:13px;font-weight:700;color:var(--c-ink-60);text-transform:uppercase;letter-spacing:.04em}
    .module-toolbar-links{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .module-toolbar-links a{padding:7px 11px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;color:var(--c-ink-60);border:1px solid var(--c-ink-05)}
    .module-toolbar-links a.active,.module-toolbar-links a:hover{color:var(--c-accent);border-color:var(--c-accent-lt);background:var(--c-accent-xl)}
    .sidebar-nav-subsection{
      padding:8px 20px 4px;
      font-size:11px;
      color:rgba(15,23,42,.46);
      font-weight:700;
      letter-spacing:.03em;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .sidebar-nav-subsection::before{
      content:'';
      width:14px;
      height:1px;
      background:rgba(15,23,42,.12);
    }
    .sidebar-app-link{position:relative}
    .sidebar-app-link .app-icon-badge{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      width:22px;
      height:22px;
      border-radius:0;
      margin-right:0;
      background:transparent;
      color:var(--c-ink);
      box-shadow:none;
      flex:0 0 22px;
    }
    .sidebar-app-link .app-icon-badge.has-image{
      width:30px;
      height:30px;
      padding:5px;
      background:rgba(16,19,26,.86);
      border-radius:8px;
      flex:0 0 30px;
    }
    .sidebar-app-link .app-icon-badge i{
      width:auto;
      font-size:16px;
    }
    .sidebar-app-link .app-icon-badge img{
      width:18px;
      height:18px;
      object-fit:contain;
      display:block;
    }
    .page-title-heading{
      display:flex;
      align-items:center;
      gap:14px;
      margin-bottom:6px;
    }
    .page-title-module-icon{
      width:50px;
      height:50px;
      border-radius:15px;
      background:var(--pti-bg, var(--c-accent-lt));
      color:var(--pti-color, var(--c-accent));
      display:inline-flex;
      align-items:center;
      justify-content:center;
      box-shadow:inset 0 0 0 1px rgba(255,255,255,.45),0 10px 24px rgba(15,23,42,.08);
      flex:0 0 auto;
      overflow:hidden;
    }
    .page-title-module-icon i{
      font-size:22px;
    }
    .page-title-module-icon img{
      width:28px;
      height:28px;
      object-fit:contain;
      display:block;
    }
    .sidebar-market-link .nav-badge{
      background:rgba(15,23,42,.06);
      color:var(--c-ink-60);
    }
    .sidebar-brand-logo{
      width:42px;
      height:42px;
      border-radius:10px;
      object-fit:contain;
      background:transparent;
      padding:0;
      box-shadow:none;
      flex:0 0 auto;
    }
    .sidebar-brand-fallback{
      width:42px;
      height:42px;
      border-radius:10px;
      display:none;
      align-items:center;
      justify-content:center;
      background:rgba(15,23,42,.06);
      color:var(--c-ink);
      flex:0 0 auto;
    }
    .sidebar-brand-copy{
      min-width:0;
      transition:opacity .18s ease, transform .18s ease;
    }
    #sidebarToggle{display:none}
    .sidebar-mobile-backdrop{
      position:fixed;
      inset:0;
      background:rgba(2,6,23,.48);
      backdrop-filter:blur(2px);
      opacity:0;
      pointer-events:none;
      transition:opacity .22s ease;
      z-index:54;
    }

    .ui-tooltip{
      position:fixed;
      z-index:1000;
      max-width:280px;
      padding:8px 10px;
      border-radius:10px;
      background:linear-gradient(180deg,#111827 0%,#0b1220 100%);
      color:#f8fafc;
      border:1px solid rgba(255,255,255,.1);
      font-size:12px;
      line-height:1.35;
      font-weight:600;
      box-shadow:0 14px 40px rgba(2,6,23,.45);
      pointer-events:none;
      opacity:0;
      transform:translateY(6px) scale(.98);
      transition:opacity .14s ease, transform .14s ease;
    }
    .ui-tooltip.show{
      opacity:1;
      transform:translateY(0) scale(1);
    }
    .ui-tooltip::after{
      content:'';
      position:absolute;
      left:50%;
      transform:translateX(-50%);
      bottom:-6px;
      border-width:6px 6px 0 6px;
      border-style:solid;
      border-color:#0b1220 transparent transparent transparent;
    }
    .crm-sidebar,.sidebar-footer,#userDropdown{overflow:visible}
    #userDropdown{
      position:relative;
      width:100%;
  }
    #userDropdown .sidebar-user{position:relative;padding-right:30px;align-items:flex-start}
    #userDropdown .sidebar-user .user-chevron{
      position:absolute;
      top:20px;
      right:8px;
      color:var(--c-accent);
      font-size:11px;
      transition:transform .18s ease,color .18s ease;
    }
    #userDropdown.open .sidebar-user .user-chevron{
      transform:rotate(90deg);
      color:var(--c-accent-dk);
    }
    #userDropdown .dropdown-menu{
      left:calc(100% - 20px);
      right:auto;
      top: -115px;
      bottom:auto;
      min-width:220px;
      z-index:120;
      transform:translate(-8px,0);
    }
    #userDropdown.open .dropdown-menu{
      transform:translate(0,0);
    }
    .crm-layout .crm-sidebar{
      background:#10131a;
      border-right:1px solid rgba(148,163,184,.16);
      color:#e5edf8;
      box-shadow:18px 0 48px rgba(2,6,23,.28);
    }
    .crm-layout .sidebar-brand{
      border-bottom:1px solid rgba(148,163,184,.14);
    }
    .crm-layout .sidebar-brand-name{color:#f8fafc}
    .crm-layout .sidebar-brand-tag{color:#94a3b8}
    .crm-layout .sidebar-brand-fallback{
      background:rgba(37,99,235,.16);
      color:#bfdbfe;
      box-shadow:inset 0 0 0 1px rgba(96,165,250,.25);
    }
    .crm-layout .sidebar-nav-section{
      color:#64748b;
    }
    .crm-layout .sidebar-nav a{
      position:relative;
      color:#cbd5e1;
      border:1px solid transparent;
      overflow:hidden;
    }
    .crm-layout .sidebar-nav a:hover{
      background:rgba(255,255,255,.06);
      border-color:rgba(255,255,255,.08);
      color:#f8fafc;
    }
    .crm-layout .sidebar-nav a.active{
      background:rgba(255,255,255,.08);
      border-color:rgba(255,255,255,.10);
      color:#fff;
      box-shadow:none;
    }
    .crm-layout .sidebar-nav a.active::before{
      content:'';
      position:absolute;
      left:0;
      top:10px;
      bottom:10px;
      width:3px;
      border-radius:0 999px 999px 0;
      background:#2563eb;
    }
    .crm-layout .sidebar-nav a i{color:#93c5fd}
    .crm-layout .sidebar-nav a.active i{color:#60a5fa}
    .crm-layout .sidebar-nav a .nav-badge,
    .crm-layout .sidebar-market-link .nav-badge{
      background:rgba(37,99,235,.18);
      color:#bfdbfe;
      border:1px solid rgba(96,165,250,.22);
    }
    .crm-layout .sidebar-nav-subsection{
      color:#94a3b8;
    }
    .crm-layout .sidebar-nav-subsection::before{
      background:rgba(148,163,184,.24);
    }
    .crm-layout .sidebar-app-link .app-icon-badge{
      background:transparent;
      color:#dbeafe;
      border-radius:8px;
      box-shadow:none;
    }
    .crm-layout .sidebar-app-link .app-icon-badge.has-image{
      background:rgba(255,255,255,.08);
      border-radius:8px;
    }
    .crm-layout .sidebar-footer{
      border-top:1px solid rgba(148,163,184,.14);
      background:linear-gradient(180deg,rgba(15,23,42,0),rgba(15,23,42,.54));
    }
    .crm-layout .sidebar-user{
      background:rgba(255,255,255,.06);
      border:0;
      color:#f8fafc;
    }
    .crm-layout .sidebar-user:hover{
      background:rgba(37,99,235,.14);
    }
    .crm-layout .sidebar-user-avatar{
      background:linear-gradient(135deg,#2563eb,#0ea5e9);
      color:#fff;
      box-shadow:0 10px 22px rgba(37,99,235,.28);
    }
    .crm-layout .sidebar-user-name{color:#f8fafc}
    .crm-layout .sidebar-user-role{color:#94a3b8}
    .crm-layout #userDropdown .sidebar-user .user-chevron{color:#93c5fd}
    .crm-layout #userDropdown.open .sidebar-user .user-chevron{color:#bfdbfe}
    .crm-layout #userDropdown .dropdown-menu{
      background:#0f172a;
      border:1px solid rgba(148,163,184,.18);
      box-shadow:0 24px 56px rgba(2,6,23,.44);
    }
    .crm-layout #userDropdown .dropdown-item{
      color:#cbd5e1;
    }
    .crm-layout #userDropdown .dropdown-item:hover{
      background:rgba(37,99,235,.16);
      color:#fff;
    }
    .crm-layout #userDropdown .dropdown-divider{
      border-color:rgba(148,163,184,.14);
    }
    .crm-layout #userDropdown .dropdown-item.danger{color:#fecaca}
    .crm-layout #userDropdown .dropdown-item.danger:hover{
      background:rgba(239,68,68,.16);
      color:#fff;
    }
    body.sidebar-collapsed .crm-layout{--sidebar-w:92px}
    body.sidebar-collapsed .sidebar-brand{
      justify-content:center;
      padding:18px 12px;
    }
    body.sidebar-collapsed .sidebar-brand-copy,
    body.sidebar-collapsed .sidebar-nav-section,
    body.sidebar-collapsed .sidebar-nav-subsection,
    body.sidebar-collapsed .sidebar-link-label,
    body.sidebar-collapsed .sidebar-market-link .nav-badge,
    body.sidebar-collapsed .sidebar-user-copy,
    body.sidebar-collapsed #userDropdown .sidebar-user .user-chevron{
      display:none !important;
    }
    body.sidebar-collapsed .sidebar-nav a{
      justify-content:center;
      gap:0;
      padding:12px 10px;
      margin:4px 10px;
    }
    body.sidebar-collapsed .sidebar-app-link .app-icon-badge{
      margin-right:0;
    }
    body.sidebar-collapsed .sidebar-footer{
      padding:14px 10px;
    }
    body.sidebar-collapsed .sidebar-user{
      justify-content:center;
      padding:8px;
    }
    body.sidebar-collapsed #userDropdown .dropdown-menu{
      left:calc(100% - 8px);
    }
    @media (max-width: 1024px){
      #sidebarToggle{display:inline-flex !important}
      .sidebar-compact-toggle{display:none !important}
      .crm-layout > .crm-main > .crm-header{left:0}
      .crm-sidebar{
        z-index:60;
        box-shadow:0 20px 60px rgba(2,6,23,.36);
      }
      .crm-sidebar.open + .sidebar-mobile-backdrop{
        opacity:1;
        pointer-events:auto;
      }
    }
    @media (max-width: 992px){
      .automation-drawer,.apps-drawer{max-width:100%;width:min(100vw, 100%)}
      .automation-summary{flex-direction:column;align-items:flex-start}
      .automation-summary-actions{width:100%;justify-content:flex-start}
      #userDropdown .dropdown-menu{
        left:auto;
        right:0;
        top:auto;
        bottom:calc(100% + 6px);
        transform:translateY(-6px);
      }
      #userDropdown.open .dropdown-menu{
        transform:translateY(0);
      }
    }
    @media (max-width: 768px){
      .crm-header-breadcrumb,
      .global-search-wrap{display:none !important}
    }
  </style>
  @stack('styles')
</head>
<body class="@yield('body_class')">
<div class="crm-layout @yield('layout_class')">
  <aside class="crm-sidebar" id="sidebar">
    <div class="sidebar-brand">
      <img
        src="{{ asset('logo.png') }}"
        alt="{{ config('app.name', 'CRM') }} Logo"
        class="sidebar-brand-logo"
        loading="eager"
        decoding="async"
        onerror="this.style.display='none'; var fb=this.nextElementSibling; if(fb){fb.style.display='inline-flex';}"
      >
      <div class="sidebar-brand-fallback"><i class="fas fa-layer-group"></i></div>
      <div class="sidebar-brand-copy">
        <div class="sidebar-brand-name">Nexiste CRM</div>
        <div class="sidebar-brand-tag">SaaS Platform</div>
      </div>
    </div>

    @php
      $layoutUser = auth()->user();
      $layoutIsSuperAdmin = $layoutUser && (
        (method_exists($layoutUser, 'hasAnyRole') && $layoutUser->hasAnyRole(['super_admin', 'super-admin'])) ||
        (method_exists($layoutUser, 'hasRole') && ($layoutUser->hasRole('super_admin') || $layoutUser->hasRole('super-admin')))
      );
      $layoutAccess = $layoutAccess ?? [];
      $layoutCanDashboard = (bool) ($layoutAccess['dashboard'] ?? true);
      $layoutCanUsers = (bool) ($layoutAccess['users'] ?? false);
      $layoutCanMarketplace = (bool) ($layoutAccess['marketplace'] ?? false);
      $layoutCanSettings = (bool) ($layoutAccess['settings'] ?? false);
      $layoutHasVisibleApps = (bool) (($layoutInstalledAppsByCategory ?? collect())->count());
      $layoutMarketplaceUrl = null;
      $layoutMarketplaceSub = 'Découvrir des applications';
      $layoutMarketplaceActivePattern = 'marketplace.*';
      if ($layoutIsSuperAdmin && Route::has('superadmin.extensions.index')) {
        $layoutMarketplaceUrl = route('superadmin.extensions.index');
        $layoutMarketplaceSub = 'Gérer le catalogue global';
        $layoutMarketplaceActivePattern = 'superadmin.extensions.*';
      } elseif ($layoutCanMarketplace && Route::has('marketplace.index')) {
        $layoutMarketplaceUrl = route('marketplace.index');
      }
    @endphp

    <nav class="sidebar-nav">
      @if($layoutCanDashboard)
        <div class="sidebar-nav-section">Principal</div>
        <a href="{{ url('/dashboard') }}" class="{{ request()->is('dashboard') ? 'active' : '' }}">
          <i class="fas fa-home"></i>
          <span class="sidebar-link-label">Tableau de bord</span>
        </a>
      @endif

      @if($layoutCanUsers && Route::has('users.index'))
        <div class="sidebar-nav-section">Utilisateurs</div>
        <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') || request()->routeIs('rbac.*') ? 'active' : '' }}">
          <i class="fa fa-user-cog"></i>
          <span class="sidebar-link-label">Utilisateurs</span>
        </a>
      @endif
      @if($layoutIsSuperAdmin && Route::has('superadmin.tenants.index'))
        <div class="sidebar-nav-section">Tenants</div>
        <a href="{{ route('superadmin.tenants.index') }}" class="{{ request()->routeIs('superadmin.tenants.*') ? 'active' : '' }}">
          <i class="fas fa-building-user"></i>
          <span class="sidebar-link-label">Tenants actifs</span>
        </a>
      @endif
      @if($layoutMarketplaceUrl || $layoutHasVisibleApps)
        <div class="sidebar-nav-section">Applications</div>
        @if($layoutMarketplaceUrl)
          <a href="{{ $layoutMarketplaceUrl }}" class="sidebar-market-link {{ request()->routeIs($layoutMarketplaceActivePattern) || request()->routeIs('marketplace.*') ? 'active' : '' }}">
            <i class="fa fa-store"></i>
            <span class="sidebar-link-label">Marketplace</span>
            <span class="nav-badge">Store</span>
          </a>
        @endif
        @php
          $appRoutePatterns = [
            'clients' => 'clients.*',
            'stock' => 'stock.*',
            'invoice' => 'invoices.*',
            'projects' => 'projects.*',
            'notion-workspace' => 'notion-workspace.*',
            'trello-integration' => 'trello-integration.*',
            'google-drive' => 'google-drive.*',
            'gdrive' => 'google-drive.*',
            'dropbox' => 'dropbox.*',
            'google-calendar' => 'google-calendar.*',
            'google-sheets' => 'google-sheets.*',
            'google-docx' => 'google-docx.*',
            'google-gmail' => 'google-gmail.*',
            'google-meet' => 'google-meet.*',
            'slack' => 'slack.*',
            'chatbot' => 'chatbot.*',
          ];
        @endphp
        @if($layoutHasVisibleApps)
          @foreach(($layoutInstalledAppsByCategory ?? collect()) as $category)
            <div class="sidebar-nav-subsection">
              <i class="{{ $category->icon ?? 'fas fa-puzzle-piece' }}" style="color:{{ $category->color ?? '#64748b' }}"></i>
              {{ $category->label ?? 'Autre' }}
            </div>
            @foreach(($category->apps ?? collect()) as $installedApp)
              @php
                $pattern = $appRoutePatterns[$installedApp->slug] ?? null;
                $isActive = $pattern ? request()->routeIs($pattern) : false;
              @endphp
              <a href="{{ $installedApp->url }}" class="sidebar-app-link {{ $isActive ? 'active' : '' }}">
                <span class="app-icon-badge {{ !empty($installedApp->icon_url) ? 'has-image' : '' }}" style="--app-bg: {{ $installedApp->icon_bg_color ?? '#334155' }};">
                  @if(!empty($installedApp->icon_url))
                    <img src="{{ $installedApp->icon_url }}" alt="{{ $installedApp->name }}">
                  @else
                    <i class="{{ $installedApp->icon }}"></i>
                  @endif
                </span>
                <span class="sidebar-link-label">{{ $installedApp->name }}</span>
              </a>
            @endforeach
          @endforeach
        @endif
      @endif
    </nav>

    <div class="sidebar-footer">
      <div class="dropdown" id="userDropdown" style="margin-top:4px;">
        <div class="sidebar-user" data-dropdown-toggle>
          <div class="sidebar-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}</div>
          <div class="sidebar-user-copy" style="flex:1;min-width:0;">
            <div class="sidebar-user-name">{{ auth()->user()->name ?? 'Utilisateur' }}</div>
            <div class="sidebar-user-role">{{ auth()->user()->role_in_tenant ?? 'Membre' }}</div>
          </div>
          <i class="fas fa-chevron-right user-chevron"></i>
        </div>
        <div class="dropdown-menu">
          <a href="{{ route('profile-settings') }}" class="dropdown-item"><i class="fas fa-user"></i> Mon profil</a>
          @if($layoutCanSettings && Route::has('settings.global'))
            <a href="{{ route('settings.global') }}" class="dropdown-item"><i class="fas fa-gear"></i> Paramètres globaux</a>
          @endif
          <div class="dropdown-divider"></div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dropdown-item danger" style="width:100%;border:none;background:none;cursor:pointer;text-align:left;">
              <i class="fas fa-right-from-bracket"></i> Deconnexion
            </button>
          </form>
        </div>
      </div>
    </div>
  </aside>
  <div class="sidebar-mobile-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

  <div class="crm-main">
    <header class="crm-header">
      <button id="sidebarToggle" class="btn-icon" aria-label="Ouvrir le menu"><i class="fas fa-bars"></i></button>
      <button class="btn-icon sidebar-compact-toggle" id="sidebarCompactToggle" type="button" aria-label="Réduire le menu en mode icônes" aria-pressed="false">
        <i class="fas fa-arrow-left"></i>
      </button>
      <div class="crm-header-breadcrumb">@yield('breadcrumb')</div>
      <div class="crm-header-spacer"></div>

      <div class="global-search-wrap">
        <i class="fas fa-search"></i>
        <input id="globalSearchInput" class="form-control" type="text" placeholder="Recherche globale: clients, users, factures, devis, projets, notion, apps Google, Slack, Chatbot..." autocomplete="off">
        <div id="globalSearchSuggestions" class="global-search-suggest"></div>
      </div>

      <div class="crm-header-actions">
        <button class="btn-icon" data-modal-open="myAppsModal" aria-label="Mes applications"><i class="fas fa-th-large"></i></button>
        <div class="header-notif-wrap" id="globalNotifWrap">
          <button class="btn-icon" id="globalNotifBtn" aria-label="Notifications" aria-expanded="false" type="button">
            <i class="fas fa-bell"></i>
            @if(($layoutNotificationsUnreadCount ?? 0) > 0)
              <span class="header-notif-badge">{{ ($layoutNotificationsUnreadCount ?? 0) > 99 ? '99+' : ($layoutNotificationsUnreadCount ?? 0) }}</span>
            @endif
          </button>
          <div class="header-notif-dropdown" id="globalNotifDropdown" aria-hidden="true">
            <div class="header-notif-header">
              <div>
                <strong>Notifications</strong>
                <span>Vos rappels et actions a reprendre</span>
              </div>
              <span class="header-notif-counter">{{ (int) ($layoutNotificationsUnreadCount ?? 0) }}</span>
            </div>
            <div class="header-notif-list">
              @forelse(($layoutNotifications ?? collect()) as $notification)
                @php
                  $notificationUrl = trim((string) ($notification->action_url ?? ''));
                  $notificationClasses = 'header-notif-item' . (!empty($notification->is_unread) ? ' is-unread' : '');
                @endphp
                @if($notificationUrl !== '')
                  <a href="{{ $notificationUrl }}" class="{{ $notificationClasses }}">
                @else
                  <div class="{{ $notificationClasses }}">
                @endif
                  <span class="header-notif-icon" style="--notif-accent: {{ $notification->accent ?? '#2563eb' }};">
                    <i class="fas {{ $notification->icon ?? 'fa-bell' }}"></i>
                  </span>
                  <span class="header-notif-copy">
                    <strong>{{ $notification->title ?? 'Notification CRM' }}</strong>
                    <small>{{ $notification->message ?? 'Une action est disponible.' }}</small>
                    <em>
                      {{ !empty($notification->created_at) ? \Illuminate\Support\Carbon::parse($notification->created_at)->locale('fr')->diffForHumans() : 'A l instant' }}
                    </em>
                  </span>
                @if($notificationUrl !== '')
                  </a>
                @else
                  </div>
                @endif
              @empty
                <div class="header-notif-empty">Aucune notification pour le moment.</div>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    </header>

    @php
      $moduleMenu = null;
      if (request()->routeIs('clients.*')) {
        $moduleMenu = 'layouts.partials.module-menu-clients';
      } elseif (request()->routeIs('stock.*')) {
        $moduleMenu = 'layouts.partials.module-menu-stock';
      } elseif (request()->routeIs('invoices.*')) {
        $moduleMenu = 'layouts.partials.module-menu-invoice';
      } elseif (request()->routeIs('projects.*')) {
        $moduleMenu = 'layouts.partials.module-menu-projects';
      } elseif (request()->routeIs('notion-workspace.*')) {
        $moduleMenu = 'layouts.partials.module-menu-notion-workspace';
      } elseif (request()->routeIs('users.*') || request()->routeIs('rbac.*')) {
        $moduleMenu = 'layouts.partials.module-menu-users';
      }
    @endphp

    @if($moduleMenu)
      @include($moduleMenu)
    @endif

    <main class="crm-content @yield('content_class')">@yield('content')</main>
  </div>
</div>

<div class="modal-overlay modal-overlay-right" id="myAppsModal">
  <div class="modal apps-drawer">
    <div class="modal-header">
      <div class="modal-header-icon"><i class="fas fa-th-large"></i></div>
      <div>
        <div class="modal-title">Mes applications</div>
        <div class="modal-subtitle">{{ $layoutInstalledAppsCount ?? 0 }} installée(s)</div>
      </div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <div class="modal-body">
      <div class="apps-drawer-list">
        @if($layoutMarketplaceUrl ?? false)
        <a href="{{ $layoutMarketplaceUrl }}" class="apps-drawer-item">
          <span class="apps-drawer-icon"><i class="fas fa-store"></i></span>
          <span>
            <strong>Marketplace</strong><br>
            <small style="color:var(--c-ink-40)">{{ $layoutMarketplaceSub }}</small>
          </span>
        </a>
        @endif

        @forelse(($layoutInstalledAppsByCategory ?? collect()) as $category)
          <div class="apps-drawer-category">
            <i class="{{ $category->icon ?? 'fas fa-puzzle-piece' }}" style="color:{{ $category->color ?? '#64748b' }};margin-right:6px;"></i>
            {{ $category->label ?? 'Autre' }}
          </div>
          @foreach(($category->apps ?? collect()) as $app)
            <a href="{{ $app->url }}" class="apps-drawer-item">
              <span class="apps-drawer-icon" style="background:{{ $app->icon_bg_color ?? '#334155' }};color:#fff;">
                @if(!empty($app->icon_url))
                  <img src="{{ $app->icon_url }}" alt="{{ $app->name }}">
                @else
                  <i class="{{ $app->icon }}"></i>
                @endif
              </span>
              <span>
                <strong>{{ $app->name }}</strong><br>
                <small style="color:var(--c-ink-40)">Ouvrir le module</small>
              </span>
              @if($app->status === 'trial')
                <span class="apps-drawer-badge">Essai</span>
              @endif
            </a>
          @endforeach
        @empty
          <div style="text-align:center;padding:18px 10px;color:var(--c-ink-40);">
            Aucune application active pour ce tenant.
          </div>
        @endforelse
      </div>
    </div>
    <div class="modal-footer" style="justify-content:flex-start">
      @if(($layoutIsSuperAdmin ?? false) && Route::has('superadmin.extensions.index'))
        <a href="{{ route('superadmin.extensions.index') }}" class="btn btn-secondary"><i class="fas fa-sliders-h"></i> Gérer le marketplace</a>
      @elseif(($layoutCanMarketplace ?? false) && Route::has('marketplace.my-apps'))
        <a href="{{ route('marketplace.my-apps') }}" class="btn btn-secondary"><i class="fas fa-th-list"></i> Gérer mes applications</a>
      @endif
    </div>
  </div>
</div>

<div class="modal-overlay" id="automationSuggestionsModal">
  <div class="modal automation-drawer">
    <div class="modal-header">
      <div class="modal-header-icon"><i class="fas fa-wand-magic-sparkles"></i></div>
      <div class="modal-header-copy">
        <div class="modal-title" data-automation-title>Suggestions intelligentes</div>
        <div class="modal-subtitle" data-automation-subtitle>Le CRM vous propose les prochaines actions utiles.</div>
      </div>
      <div class="modal-header-actions">
        @if(($layoutCanSettings ?? false) && Route::has('settings.global'))
          <a href="{{ route('settings.global') }}#automation-suggestions-settings" class="modal-icon-link" aria-label="Parametres des suggestions">
            <i class="fas fa-gear"></i>
          </a>
        @endif
        <button class="modal-close" data-modal-close>&times;</button>
      </div>
    </div>
    <div class="modal-body">
      <div class="automation-summary">
        <div class="automation-summary-copy">
          <strong>Automations apres creation</strong>
          <span data-automation-count>0 suggestion</span>
        </div>
        <div class="automation-summary-actions">
          <button type="button" class="btn btn-secondary btn-sm" data-automation-bulk="reject"><i class="fas fa-ban"></i> Ignorer tout</button>
          <button type="button" class="btn btn-primary btn-sm" data-automation-bulk="accept"><i class="fas fa-bolt"></i> Tout accepter</button>
        </div>
      </div>
      <div class="automation-list" data-automation-list></div>
      <div class="automation-empty" data-automation-empty style="display:none;">
        Aucune suggestion en attente pour cette action.
      </div>
      <div class="automation-success" data-automation-success>
        <div class="automation-success-shell">
          <div class="automation-success-icon"><i class="fas fa-circle-check"></i></div>
          <div class="automation-success-title" data-automation-success-title>Succès</div>
          <div class="automation-success-text" data-automation-success-text>Toutes les suggestions ont été traitées avec succès.</div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-primary" data-automation-close><i class="fas fa-arrow-right"></i> Continuer</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="confirmModal">
  <div class="modal modal-sm">
    <div class="modal-body" style="text-align:center;padding:36px 28px;">
      <div class="modal-confirm-icon danger" data-confirm-icon><i class="fas fa-exclamation-triangle"></i></div>
      <h3 class="modal-confirm-title" data-confirm-title>Confirmer l'action</h3>
      <p class="modal-confirm-text" data-confirm-text style="margin-bottom:24px;"></p>
      <div style="display:flex;gap:10px;justify-content:center;">
        <button class="btn btn-secondary" data-modal-close>Annuler</button>
        <button class="btn btn-danger" data-confirm-ok>Confirmer</button>
      </div>
    </div>
  </div>
</div>

@php
  $clientJsRoutes = [
    'show' => Route::has('clients.show') ? route('clients.show', ['client' => '__CLIENT__']) : null,
    'edit' => Route::has('clients.edit') ? route('clients.edit', ['client' => '__CLIENT__']) : null,
    'destroy' => Route::has('clients.destroy') ? route('clients.destroy', ['client' => '__CLIENT__']) : null,
    'search' => Route::has('clients.search') ? route('clients.search') : null,
  ];
  $clientExtensionRoutes = [
    'googleGmail' => Route::has('google-gmail.index') ? route('google-gmail.index') : null,
    'googleCalendar' => Route::has('google-calendar.index') ? route('google-calendar.index') : null,
    'googleDrive' => Route::has('google-drive.index') ? route('google-drive.index') : null,
    'dropbox' => Route::has('dropbox.index') ? route('dropbox.index') : null,
    'slack' => Route::has('slack.index') ? route('slack.index') : null,
    'googleMeet' => Route::has('google-meet.index') ? route('google-meet.index') : null,
    'googleSheets' => Route::has('google-sheets.index') ? route('google-sheets.index') : null,
    'googleDocx' => Route::has('google-docx.index') ? route('google-docx.index') : null,
    'notionWorkspace' => Route::has('notion-workspace.index') ? route('notion-workspace.index') : null,
  ];
  $invoiceJsRoutes = [
    'show' => Route::has('invoices.show') ? route('invoices.show', ['invoice' => '__INVOICE__']) : null,
    'edit' => Route::has('invoices.edit') ? route('invoices.edit', ['invoice' => '__INVOICE__']) : null,
    'pdf' => Route::has('invoices.pdf') ? route('invoices.pdf', ['invoice' => '__INVOICE__']) : null,
    'destroy' => Route::has('invoices.destroy') ? route('invoices.destroy', ['invoice' => '__INVOICE__']) : null,
    'paymentsDestroy' => Route::has('invoices.payments.destroy') ? route('invoices.payments.destroy', ['payment' => '__PAYMENT__']) : null,
    'quoteShow' => Route::has('invoices.quotes.show') ? route('invoices.quotes.show', ['quote' => '__QUOTE__']) : null,
    'quoteEdit' => Route::has('invoices.quotes.edit') ? route('invoices.quotes.edit', ['quote' => '__QUOTE__']) : null,
    'quotePdf' => Route::has('invoices.quotes.pdf') ? route('invoices.quotes.pdf', ['quote' => '__QUOTE__']) : null,
    'quoteDestroy' => Route::has('invoices.quotes.destroy') ? route('invoices.quotes.destroy', ['quote' => '__QUOTE__']) : null,
    'quoteConvert' => Route::has('invoices.quotes.convert') ? route('invoices.quotes.convert', ['quote' => '__QUOTE__']) : null,
  ];
@endphp
<script>
window.CLIENT_LANG = Object.assign(window.CLIENT_LANG || {}, {
  confirmText: @json(__('client::clients.actions.confirm')),
  cancelText: @json(__('client::clients.actions.cancel')),
  openAction: @json(__('client::clients.actions.open')),
  acceptAction: @json(__('client::clients.actions.accept')),
  deleteAction: @json(__('client::clients.actions.delete')),
  successTitle: @json(__('client::clients.messages.success_title')),
  errorTitle: @json(__('client::clients.messages.error_title')),
  validationTitle: @json(__('client::clients.messages.validation_title')),
  processing: @json(__('client::clients.messages.processing')),
  deleting: @json(__('client::clients.messages.deleting')),
  loadFailed: @json(__('client::clients.messages.load_failed')),
  unexpectedError: @json(__('client::clients.messages.unexpected_error')),
  operationSuccess: @json(__('client::clients.messages.operation_success')),
  deleteUnable: @json(__('client::clients.messages.delete_unable')),
  fixErrors: @json(__('client::clients.messages.fix_errors')),
  deletedTitle: @json(__('client::clients.messages.deleted')),
  draftRestoredStatus: @json(__('client::clients.messages.draft_restored')),
  draftRestoredTitle: @json(__('client::clients.messages.draft_restored_title')),
  draftRestoredHelp: @json(__('client::clients.messages.draft_restored_help')),
  draftDeletedStatus: @json(__('client::clients.messages.draft_deleted')),
  draftDeletedHelp: @json(__('client::clients.messages.draft_deleted_help')),
  draftAvailableTitle: @json(__('client::clients.messages.draft_available_title')),
  draftAvailableMessage: @json(__('client::clients.messages.draft_available_message', ['label' => ':label'])),
  draftResume: @json(__('client::clients.messages.draft_resume')),
  draftCancelDelete: @json(__('client::clients.messages.draft_cancel_delete')),
  draftAvailableLabel: @json(__('client::clients.messages.draft_available_label', ['label' => ':label'])),
  draftAutoSave: @json(__('client::clients.messages.draft_auto_save')),
  draftSaving: @json(__('client::clients.messages.draft_saving')),
  draftUnavailable: @json(__('client::clients.messages.draft_unavailable')),
  draftFailed: @json(__('client::clients.messages.draft_failed')),
  draftSavedAt: @json(__('client::clients.messages.draft_saved_at', ['time' => ':time'])),
  draftResumeButton: @json(__('client::clients.messages.draft_resume_button')),
  draftCurrent: @json(__('client::clients.messages.draft_current')),
  automationSuccessMessage: @json(__('client::clients.messages.automation_success_message')),
  automationRoutesUnavailable: @json(__('client::clients.messages.automation_routes_unavailable')),
  automationActionFailed: @json(__('client::clients.messages.automation_action_failed')),
  automationBulkFailed: @json(__('client::clients.messages.automation_bulk_failed')),
  automationReloadFailed: @json(__('client::clients.messages.automation_reload_failed')),
  automationPartialFailed: @json(__('client::clients.messages.automation_partial_failed', ['count' => ':count'])),
});
window.CLIENT_ROUTES = Object.assign(window.CLIENT_ROUTES || {}, @json($clientJsRoutes));
window.CLIENT_EXTENSION_ROUTES = Object.assign(window.CLIENT_EXTENSION_ROUTES || {}, @json($clientExtensionRoutes));
window.INVOICE_ROUTES = Object.assign(window.INVOICE_ROUTES || {}, @json($invoiceJsRoutes));
</script>
<script src="{{ asset('vendor/client/js/crm.js') }}"></script>
<script src="{{ asset('vendor/client/js/secure-form.js') }}"></script>
<script src="{{ asset('vendor/invoice/js/invoice.js') }}"></script>
<script src="{{ asset('vendor/stock/js/stock.js') }}"></script>
@php
  $globalSearchInstalledAppsMeta = ($layoutInstalledApps ?? collect())
    ->map(function ($app) {
      return [
        'slug' => (string) ($app->slug ?? ''),
        'name' => (string) ($app->name ?? ''),
        'icon' => (string) ($app->icon ?? 'fa-puzzle-piece'),
        'icon_url' => (string) ($app->icon_url ?? ''),
        'url' => (string) ($app->url ?? ''),
      ];
    })
    ->values()
    ->all();

  $globalSearchQuickLinks = array_values(array_filter([
    ($layoutCanDashboard ?? true) ? ['label' => 'Tableau de bord', 'sub' => 'Vue generale', 'icon' => 'fa-home', 'url' => url('/dashboard'), 'keywords' => 'dashboard accueil principal'] : null,
    !($layoutIsSuperAdmin ?? false) && ($layoutCanMarketplace ?? false) && Route::has('applications') ? ['label' => 'Applications', 'sub' => 'Mes applications CRM', 'icon' => 'fa-th-large', 'url' => route('applications'), 'keywords' => 'apps applications modules'] : null,
    ($layoutMarketplaceUrl ?? null) ? ['label' => 'Marketplace', 'sub' => $layoutMarketplaceSub ?? 'Installer de nouvelles applications', 'icon' => 'fa-store', 'url' => $layoutMarketplaceUrl, 'keywords' => 'marketplace store installer extensions'] : null,
    ($layoutIsSuperAdmin ?? false) && Route::has('superadmin.tenants.index') ? ['label' => 'Tenants actifs', 'sub' => 'Gestion super-admin des entreprises', 'icon' => 'fa-building-user', 'url' => route('superadmin.tenants.index'), 'keywords' => 'tenants entreprises organisations clients actifs'] : null,
    ($layoutCanSettings ?? false) && Route::has('settings.global') ? ['label' => 'Parametres globaux', 'sub' => 'Configuration generale', 'icon' => 'fa-sliders', 'url' => route('settings.global'), 'keywords' => 'config parametres reglage'] : null,
  ]));
  $globalSearchRoutes = [
    'clientsData' => Route::has('clients.data') ? route('clients.data') : null,
    'clientsShow' => Route::has('clients.show') ? route('clients.show', ['client' => '__CLIENT__']) : null,
    'stockArticlesData' => Route::has('stock.articles.data') ? route('stock.articles.data') : null,
    'stockArticlesSearch' => Route::has('stock.articles.search') ? route('stock.articles.search') : null,
    'stockArticleShow' => Route::has('stock.articles.show') ? route('stock.articles.show', ['article' => '__ARTICLE__']) : null,
    'stockOrdersData' => Route::has('stock.orders.data') ? route('stock.orders.data') : null,
    'stockOrdersSearch' => Route::has('stock.orders.search') ? route('stock.orders.search') : null,
    'stockOrderDetail' => Route::has('stock.orders.detail') ? route('stock.orders.detail', ['order' => '__ORDER__']) : null,
    'stockOrderShow' => Route::has('stock.orders.show') ? route('stock.orders.show', ['order' => '__ORDER__']) : null,
    'invoicesData' => Route::has('invoices.data') ? route('invoices.data') : null,
    'invoiceShow' => Route::has('invoices.show') ? route('invoices.show', ['invoice' => '__INVOICE__']) : null,
    'quotesData' => Route::has('invoices.quotes.data') ? route('invoices.quotes.data') : null,
    'quoteShow' => Route::has('invoices.quotes.show') ? route('invoices.quotes.show', ['quote' => '__QUOTE__']) : null,
    'usersData' => Route::has('users.data') ? route('users.data') : null,
    'userShow' => Route::has('users.show') ? route('users.show', ['user' => '__USER__']) : null,
    'projectsData' => Route::has('projects.data') ? route('projects.data') : null,
    'projectShow' => Route::has('projects.show') ? route('projects.show', ['project' => '__PROJECT__']) : null,
    'notionPagesSearch' => Route::has('notion-workspace.pages.search') ? route('notion-workspace.pages.search') : null,
    'notionIndex' => Route::has('notion-workspace.index') ? route('notion-workspace.index') : null,
    'googleDriveSearch' => Route::has('google-drive.search') ? route('google-drive.search') : null,
    'googleDriveIndex' => Route::has('google-drive.index') ? route('google-drive.index') : null,
    'googleSheetsData' => Route::has('google-sheets.spreadsheets.data') ? route('google-sheets.spreadsheets.data') : null,
    'googleSheetsIndex' => Route::has('google-sheets.index') ? route('google-sheets.index') : null,
    'googleDocxData' => Route::has('google-docx.documents.data') ? route('google-docx.documents.data') : null,
    'googleDocxIndex' => Route::has('google-docx.index') ? route('google-docx.index') : null,
    'googleCalendarEvents' => Route::has('google-calendar.events.data') ? route('google-calendar.events.data') : null,
    'googleCalendarIndex' => Route::has('google-calendar.index') ? route('google-calendar.index') : null,
    'googleGmailMessages' => Route::has('google-gmail.messages.data') ? route('google-gmail.messages.data') : null,
    'googleGmailIndex' => Route::has('google-gmail.index') ? route('google-gmail.index') : null,
    'googleMeetMeetings' => Route::has('google-meet.meetings.data') ? route('google-meet.meetings.data') : null,
    'googleMeetIndex' => Route::has('google-meet.index') ? route('google-meet.index') : null,
    'slackMessages' => Route::has('slack.messages.data') ? route('slack.messages.data') : null,
    'slackIndex' => Route::has('slack.index') ? route('slack.index') : null,
    'chatbotSearch' => Route::has('chatbot.search.data') ? route('chatbot.search.data') : null,
    'chatbotIndex' => Route::has('chatbot.index') ? route('chatbot.index') : null,
  ];
@endphp
<script>
window.CRM_AUTOMATION_ROUTES = {
  list: @json(route('automation.suggestions.index')),
  bulkAccept: @json(route('automation.suggestions.accept.bulk')),
  bulkReject: @json(route('automation.suggestions.reject.bulk')),
  accept: @json(route('automation.suggestions.accept', ['suggestion' => '__ID__'])),
  reject: @json(route('automation.suggestions.reject', ['suggestion' => '__ID__'])),
};

window.CRM_AUTH_ROUTES = {
  login: @json(Route::has('login') ? route('login') : url('/login')),
};

(function () {
  const layoutInstalledApps = @json(($layoutInstalledApps ?? collect())->pluck('slug')->values()->all());
  const layoutInstalledAppsMeta = @json($globalSearchInstalledAppsMeta);
  const globalQuickLinks = @json($globalSearchQuickLinks);
  const globalSearchRoutes = @json($globalSearchRoutes);

  function initGlobalSearch() {
    const input = document.getElementById('globalSearchInput');
    const box = document.getElementById('globalSearchSuggestions');
    if (!input || !box || typeof Http === 'undefined') return;
    const hasApp = (...slugs) => slugs.some((slug) => layoutInstalledApps.includes(slug));
    const hasClients = hasApp('clients');
    const hasStock = hasApp('stock');
    const hasInvoice = hasApp('invoice');
    const hasProjects = hasApp('projects');
    const hasNotion = hasApp('notion-workspace');
    const hasGoogleDrive = hasApp('google-drive', 'gdrive');
    const hasGoogleSheets = hasApp('google-sheets');
    const hasGoogleDocx = hasApp('google-docx');
    const hasGoogleCalendar = hasApp('google-calendar');
    const hasGoogleGmail = hasApp('google-gmail');
    const hasGoogleMeet = hasApp('google-meet');
    const hasSlack = hasApp('slack');
    const hasChatbot = hasApp('chatbot');

    let timer = null;
    let requestSeq = 0;
    let activeIndex = -1;
    const close = () => {
      box.style.display = 'none';
      box.innerHTML = '';
      activeIndex = -1;
    };

    const esc = (v) => {
      const d = document.createElement('div');
      d.textContent = v || '';
      return d.innerHTML;
    };
    const iconClass = (value, fallback = 'fas fa-link') => {
      const raw = String(value || '').trim();
      if (!raw) return fallback;
      const clean = raw.replace(/[^a-zA-Z0-9_\-\s]/g, '').replace(/\s+/g, ' ').trim();
      if (!clean) return fallback;

      const tokens = clean.split(' ');
      const hasGlyph = tokens.some((t) => /^fa-[a-z0-9-]+$/i.test(t));
      const hasFamily = tokens.some((t) => /^(fa|fas|far|fal|fad|fab|fat|fa-solid|fa-regular|fa-light|fa-thin|fa-brands)$/i.test(t));

      if (!hasGlyph) return fallback;
      if (!hasFamily) return `fas ${clean}`;

      return clean;
    };
    const normalize = (v) => String(v || '').toLowerCase();
    const hasQuery = (haystack, needle) => normalize(haystack).includes(normalize(needle));
    const ensureUrl = (url) => {
      const raw = String(url || '').trim();
      if (!raw) return '#';
      if (raw.startsWith('/')) return raw;
      if (/^https?:\/\//i.test(raw)) return raw;
      return '#';
    };
    const resolveImageIconSource = (value) => {
      const raw = String(value || '').trim();
      if (!raw) return '';
      if (/^(data:|https?:\/\/|\/\/)/i.test(raw)) return raw;
      if (/^(fa|fas|far|fal|fad|fab|fat|fa-solid|fa-regular|fa-light|fa-thin|fa-brands)(\s|$)/i.test(raw)) return '';
      if (/(^|\s)fa-[a-z0-9-]+(\s|$)/i.test(raw)) return '';
      if (raw.startsWith('/storage/')) return raw;
      if (raw.startsWith('storage/')) return `/${raw}`;
      if (raw.startsWith('/')) return raw;
      if (/\.(png|svg|jpe?g|gif|webp|avif|ico)(\?.*)?$/i.test(raw)) return `/storage/${raw.replace(/^\/+/, '')}`;
      return '';
    };
    const safeGet = async (url, params = {}) => {
      if (!url) return { ok: false, data: {} };
      try {
        const res = await Http.get(url, params);
        if (!res || !res.ok) return { ok: false, data: {} };
        return res;
      } catch (_) {
        return { ok: false, data: {} };
      }
    };
    const routeTo = (name, replacements = {}) => {
      let template = globalSearchRoutes[name] || '#';
      Object.entries(replacements).forEach(([key, value]) => {
        template = template.replace(`__${key.toUpperCase()}__`, encodeURIComponent(String(value)));
      });
      return template;
    };
    const routeWithQuery = (name, params = {}) => {
      const url = routeTo(name);
      if (!url || url === '#') return '#';
      const query = new URLSearchParams(params);
      const qs = query.toString();
      return qs ? `${url}?${qs}` : url;
    };
    const dedupeRows = (rows) => {
      const seen = new Set();
      return rows.filter((row) => {
        const key = `${row.url || ''}|${row.label || ''}|${row.sub || ''}`;
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
      });
    };

    const renderGroup = (title, rows) => {
      if (!rows.length) return '';
      const links = rows.map((r) => `
        <a class="global-search-item" href="${esc(ensureUrl(r.url))}"${r.external ? ' target="_blank" rel="noopener"' : ''}>
          ${resolveImageIconSource(r.icon_url || r.icon)
            ? `<img src="${esc(resolveImageIconSource(r.icon_url || r.icon))}" alt="${esc(r.label || 'App')}" style="width:16px;height:16px;object-fit:contain;display:block;">`
            : `<i class="${iconClass(r.icon || '')}" style="color:${esc(r.color || 'var(--c-accent)')};width:16px;"></i>`
          }
          <div style="min-width:0;flex:1;">
            <div class="global-search-meta">
              <span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${esc(r.label)}</span>
              ${r.badge ? `<span class="global-search-badge">${esc(r.badge)}</span>` : ''}
            </div>
            <small>${esc(r.sub || '')}</small>
          </div>
        </a>
      `).join('');
      return `<div class="global-search-group">${esc(title)}</div>${links}`;
    };
    const renderLoading = () => {
      box.innerHTML = '<div class="global-search-loading">Recherche en cours...</div>';
      box.style.display = 'block';
    };
    const renderNoResults = () => {
      box.innerHTML = '<div class="global-search-empty">Aucun resultat. Essayez un autre mot-cle.</div>';
      box.style.display = 'block';
    };
    const updateKeyboardActive = () => {
      const items = [...box.querySelectorAll('.global-search-item')];
      items.forEach((item, idx) => item.classList.toggle('is-active', idx === activeIndex));
      if (activeIndex >= 0 && items[activeIndex]) {
        items[activeIndex].scrollIntoView({ block: 'nearest' });
      }
    };
    const collectShortcuts = (q) => {
      const rows = (globalQuickLinks || []).filter((row) => {
        if (!q) return true;
        return hasQuery(row.label, q) || hasQuery(row.sub, q) || hasQuery(row.keywords, q);
      }).map((row) => ({
        label: row.label,
        sub: row.sub,
        url: row.url,
        icon: row.icon || 'fa-link',
        badge: 'Raccourci',
      }));
      return rows.slice(0, 6);
    };
    const collectApps = (q) => {
      const rows = (layoutInstalledAppsMeta || []).filter((app) => {
        if (!app?.url) return false;
        if (!q) return true;
        return hasQuery(app.name, q) || hasQuery(app.slug, q);
      }).map((app) => ({
        label: app.name,
        sub: `Application (${app.slug})`,
        url: app.url,
        icon: app.icon || 'fa-puzzle-piece',
        icon_url: app.icon_url || '',
        badge: 'App',
      }));
      return rows.slice(0, 8);
    };
    const renderGroups = (groups) => {
      const html = groups
        .filter((group) => group.rows && group.rows.length > 0)
        .map((group) => renderGroup(group.title, dedupeRows(group.rows)))
        .join('');
      if (!html.trim()) return renderNoResults();
      box.innerHTML = html;
      box.style.display = 'block';
      activeIndex = -1;
    };
    const quickMode = (q = '') => {
      renderGroups([
        { title: 'Raccourcis', rows: collectShortcuts(q) },
        { title: 'Applications', rows: collectApps(q) },
      ]);
    };
    const safeNum = (v) => {
      const n = Number(v);
      return Number.isFinite(n) ? n : 0;
    };

    const runSearch = async (rawQuery) => {
      const q = String(rawQuery || '').trim();
      if (q.length < 2) {
        quickMode(q);
        return;
      }

      const currentReq = ++requestSeq;
      const allowDeep = q.length >= 3;
      renderLoading();

      const [
        clients,
        articles,
        orders,
        invoices,
        quotes,
        users,
        projects,
        notionPages,
        driveFiles,
        spreadsheets,
        documents,
        calendarEvents,
        gmailMessages,
        meetEvents,
        slackMessages,
        chatbotMessages,
      ] = await Promise.all([
        hasClients ? safeGet(globalSearchRoutes.clientsData, { search: q, per_page: 5 }) : Promise.resolve({ ok: false, data: {} }),
        hasStock ? safeGet(globalSearchRoutes.stockArticlesSearch, { q }) : Promise.resolve({ ok: false, data: {} }),
        hasStock ? safeGet(globalSearchRoutes.stockOrdersSearch, { q }) : Promise.resolve({ ok: false, data: {} }),
        hasInvoice ? safeGet(globalSearchRoutes.invoicesData, { search: q, per_page: 5 }) : Promise.resolve({ ok: false, data: {} }),
        hasInvoice ? safeGet(globalSearchRoutes.quotesData, { search: q, per_page: 5 }) : Promise.resolve({ ok: false, data: {} }),
        safeGet(globalSearchRoutes.usersData, { search: q, per_page: 5 }),
        hasProjects ? safeGet(globalSearchRoutes.projectsData, { search: q, per_page: 5 }) : Promise.resolve({ ok: false, data: {} }),
        hasNotion ? safeGet(globalSearchRoutes.notionPagesSearch, { query: q, page_size: 5 }) : Promise.resolve({ ok: false, data: {} }),
        hasGoogleDrive && allowDeep ? safeGet(globalSearchRoutes.googleDriveSearch, { q }) : Promise.resolve({ ok: false, data: {} }),
        hasGoogleSheets && allowDeep ? safeGet(globalSearchRoutes.googleSheetsData, { search: q }) : Promise.resolve({ ok: false, data: {} }),
        hasGoogleDocx && allowDeep ? safeGet(globalSearchRoutes.googleDocxData, { search: q }) : Promise.resolve({ ok: false, data: {} }),
        hasGoogleCalendar && allowDeep ? safeGet(globalSearchRoutes.googleCalendarEvents, { search: q, per_page: 5, include_holidays: 1 }) : Promise.resolve({ ok: false, data: {} }),
        hasGoogleGmail && allowDeep ? safeGet(globalSearchRoutes.googleGmailMessages, { q, max_results: 5, label_id: 'ALL' }) : Promise.resolve({ ok: false, data: {} }),
        hasGoogleMeet && allowDeep ? safeGet(globalSearchRoutes.googleMeetMeetings, { search: q, per_page: 5 }) : Promise.resolve({ ok: false, data: {} }),
        hasSlack && allowDeep ? safeGet(globalSearchRoutes.slackMessages, { search: q, per_page: 5 }) : Promise.resolve({ ok: false, data: {} }),
        hasChatbot && allowDeep ? safeGet(globalSearchRoutes.chatbotSearch, { q, per_page: 5 }) : Promise.resolve({ ok: false, data: {} }),
      ]);

      if (currentReq !== requestSeq) return;

      const clientRows = (clients?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.company_name || 'Client',
        sub: row.email || row.phone || '',
        url: routeTo('clientsShow', { client: row.id }),
        icon: 'fa-users',
      }));
      const articleRows = (articles?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.name || 'Article',
        sub: `SKU: ${row.sku || '-'} | Stock: ${safeNum(row.current_stock ?? row.stock_quantity)}`,
        url: routeTo('stockArticleShow', { article: row.id }),
        icon: 'fa-box',
      }));
      const orderRows = (orders?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.number || `Commande #${row.id}`,
        sub: `Statut: ${row.status || '-'}`,
        url: routeTo('stockOrderShow', { order: row.id }),
        icon: 'fa-clipboard-list',
      }));
      const invoiceRows = (invoices?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.number || `Facture #${row.id}`,
        sub: row.client?.company_name || row.reference || '',
        url: routeTo('invoiceShow', { invoice: row.id }),
        icon: 'fa-file-invoice',
      }));
      const quoteRows = (quotes?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.number || `Devis #${row.id}`,
        sub: row.client?.company_name || row.reference || row.status || '',
        url: routeTo('quoteShow', { quote: row.id }),
        icon: 'fa-file-signature',
      }));
      const userRows = (users?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.name || row.email || `Utilisateur #${row.id}`,
        sub: [row.email, row.role_in_tenant].filter(Boolean).join(' | '),
        url: routeTo('userShow', { user: row.id }),
        icon: 'fa-user',
      }));
      const projectRows = (projects?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.name || `Projet #${row.id}`,
        sub: [row.client_name, row.status, row.priority].filter(Boolean).join(' | '),
        url: routeTo('projectShow', { project: row.id }),
        icon: 'fa-diagram-project',
      }));
      const notionRows = (notionPages?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.title || `Page #${row.id}`,
        sub: [row.client_name, row.owner_name].filter(Boolean).join(' | '),
        url: globalSearchRoutes.notionIndex || '#',
        icon: row.icon || 'fa-book-open',
        badge: 'Notion',
      }));
      const driveRows = (driveFiles?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.name || 'Fichier Google Drive',
        sub: [row.mime_type, row.size_formatted].filter(Boolean).join(' | '),
        url: row.web_view_link || globalSearchRoutes.googleDriveIndex || '#',
        icon: row.icon || 'fa-google-drive',
        color: row.color || '#4285F4',
        external: Boolean(row.web_view_link),
      }));
      const sheetsRows = (spreadsheets?.data?.data?.spreadsheets || []).slice(0, 5).map((row) => ({
        label: row.title || 'Google Sheet',
        sub: row.spreadsheet_id || '',
        url: row.spreadsheet_url || globalSearchRoutes.googleSheetsIndex || '#',
        icon: 'fa-file-excel',
        color: '#0f9d58',
        external: Boolean(row.spreadsheet_url),
      }));
      const docsRows = (documents?.data?.data?.documents || []).slice(0, 5).map((row) => ({
        label: row.title || 'Google Doc',
        sub: row.document_id || '',
        url: row.document_url || globalSearchRoutes.googleDocxIndex || '#',
        icon: 'fa-file-word',
        color: '#1a73e8',
        external: Boolean(row.document_url),
      }));
      const calendarRows = (calendarEvents?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.summary || 'Evenement',
        sub: [row.start_display, row.location].filter(Boolean).join(' | '),
        url: routeWithQuery('googleCalendarIndex', { event_id: row.event_id || '' }),
        icon: 'fa-calendar-days',
        color: '#4285F4',
        badge: 'Calendrier',
      }));
      const gmailRows = (gmailMessages?.data?.data?.messages || []).slice(0, 5).map((row) => ({
        label: row.subject || '(Sans objet)',
        sub: [row.from, row.snippet].filter(Boolean).join(' | '),
        url: routeWithQuery('googleGmailIndex', { message_id: row.message_id || '' }),
        icon: 'fa-envelope',
        color: '#ea4335',
        badge: row.is_read ? 'Lu' : 'Non lu',
      }));
      const meetRows = (meetEvents?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.summary || 'Reunion Meet',
        sub: [row.start_display, row.organizer_email].filter(Boolean).join(' | '),
        url: routeWithQuery('googleMeetIndex', { event_id: row.event_id || '' }),
        icon: 'fa-video',
        color: '#34a853',
        badge: 'Meet',
      }));
      const slackRows = (slackMessages?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.username || 'Slack',
        sub: row.text || '',
        url: globalSearchRoutes.slackIndex || '#',
        icon: 'fa-slack',
        color: '#4A154B',
        badge: 'Slack',
      }));
      const chatbotRows = (chatbotMessages?.data?.data || []).slice(0, 5).map((row) => ({
        label: row.sender_name || 'Chatbot',
        sub: [row.room_name, row.text].filter(Boolean).join(' | '),
        url: globalSearchRoutes.chatbotIndex || '#',
        icon: 'fa-comments',
        color: '#0ea5e9',
        badge: 'Chat',
      }));

      renderGroups([
        { title: 'Raccourcis', rows: collectShortcuts(q) },
        { title: 'Applications', rows: collectApps(q) },
        { title: 'Clients', rows: clientRows },
        { title: 'Utilisateurs', rows: userRows },
        { title: 'Articles', rows: articleRows },
        { title: 'Commandes', rows: orderRows },
        { title: 'Factures', rows: invoiceRows },
        { title: 'Devis', rows: quoteRows },
        { title: 'Projets', rows: projectRows },
        { title: 'Notion', rows: notionRows },
        { title: 'Google Drive', rows: driveRows },
        { title: 'Google Sheets', rows: sheetsRows },
        { title: 'Google Docs', rows: docsRows },
        { title: 'Google Calendar', rows: calendarRows },
        { title: 'Google Gmail', rows: gmailRows },
        { title: 'Google Meet', rows: meetRows },
        { title: 'Slack', rows: slackRows },
        { title: 'Chatbot', rows: chatbotRows },
      ]);
    };

    input.addEventListener('focus', () => {
      if (!input.value.trim()) quickMode('');
    });
    input.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        runSearch(input.value);
      }, 280);
    });
    input.addEventListener('keydown', (event) => {
      const items = [...box.querySelectorAll('.global-search-item')];
      if (!items.length) return;

      if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeIndex = Math.min(items.length - 1, activeIndex + 1);
        updateKeyboardActive();
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex = Math.max(0, activeIndex - 1);
        updateKeyboardActive();
      } else if (event.key === 'Enter' && activeIndex >= 0) {
        event.preventDefault();
        const target = items[activeIndex];
        if (target) target.click();
      } else if (event.key === 'Escape') {
        close();
      }
    });
    box.addEventListener('mousemove', (event) => {
      const item = event.target.closest('.global-search-item');
      if (!item) return;
      const items = [...box.querySelectorAll('.global-search-item')];
      activeIndex = items.indexOf(item);
      updateKeyboardActive();
    });

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.global-search-wrap')) close();
    });
  }

  function initHeaderNotifications() {
    const wrap = document.getElementById('globalNotifWrap');
    const btn = document.getElementById('globalNotifBtn');
    const panel = document.getElementById('globalNotifDropdown');
    if (!wrap || !btn || !panel) return;

    const open = () => {
      wrap.classList.add('open');
      btn.setAttribute('aria-expanded', 'true');
      panel.setAttribute('aria-hidden', 'false');
    };

    const close = () => {
      wrap.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
      panel.setAttribute('aria-hidden', 'true');
    };

    btn.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      if (wrap.classList.contains('open')) {
        close();
        return;
      }
      open();
    });

    document.addEventListener('click', (event) => {
      if (!wrap.contains(event.target)) {
        close();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        close();
      }
    });
  }

  function initInvoiceStockBridge() {
    const form = document.getElementById('invoiceForm') || document.getElementById('quoteForm');
    const tbody = document.getElementById('lineItemsBody');
    if (!form || !tbody || typeof Http === 'undefined') return;

    if (!form.querySelector('input[name="stock_order_id"]')) {
      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'stock_order_id';
      hidden.id = 'stock_order_id';
      form.appendChild(hidden);
    }

    if (document.getElementById('stockSourceType')) return;

    const block = document.createElement('div');
    block.className = 'form-section';
    block.innerHTML = `
      <h3 class="form-section-title"><i class="fas fa-warehouse"></i> Source stock (optionnel)</h3>
      <div class="row">
        <div class="col-4"><div class="form-group"><label class="form-label">Type</label><select id="stockSourceType" class="form-control"><option value="">Aucune</option><option value="article">Article</option><option value="order">Commande fournisseur</option></select></div></div>
        <div class="col-8"><div class="form-group"><label class="form-label">Recherche</label><input type="text" id="stockSourceSearch" class="form-control" placeholder="Tapez pour rechercher..."><div id="stockSourceSuggestions" class="client-suggestions" style="display:none;"></div></div></div>
      </div>`;

    const target = form.querySelector('.form-section');
    if (target && target.parentNode) target.parentNode.insertBefore(block, target.nextSibling);

    const searchInput = document.getElementById('stockSourceSearch');
    const typeInput = document.getElementById('stockSourceType');
    const suggestions = document.getElementById('stockSourceSuggestions');
    let timer = null;

    const esc = (v) => { const d = document.createElement('div'); d.textContent = v || ''; return d.innerHTML; };

    const appendLine = (line) => {
      if (window.InvLineItems?.addLine) window.InvLineItems.addLine();
      const last = tbody.querySelector('tr:last-child');
      if (!last) return;
      const descInput = last.querySelector('[name*="[description]"]');
      const refInput = last.querySelector('[name*="[reference]"]');
      const qtyInput = last.querySelector('[name*="[quantity]"]');
      const unitInput = last.querySelector('[name*="[unit]"]');
      const priceInput = last.querySelector('[name*="[unit_price]"]');
      const hiddenArticle = document.createElement('input');
      hiddenArticle.type = 'hidden';
      hiddenArticle.name = (descInput?.name || '').replace('[description]', '[article_id]');
      hiddenArticle.value = line.article_id || '';

      if (descInput) descInput.value = line.description || '';
      if (refInput) refInput.value = line.reference || '';
      if (qtyInput) qtyInput.value = line.quantity || 1;
      if (unitInput) unitInput.value = line.unit || '';
      if (priceInput) priceInput.value = line.unit_price || 0;
      if (descInput && hiddenArticle.name) descInput.closest('td')?.appendChild(hiddenArticle);
      if (window.InvLineItems?.recalc) window.InvLineItems.recalc();
    };

    const renderSuggestions = (items, onPick) => {
      if (!items.length) { suggestions.style.display = 'none'; return; }
      suggestions.innerHTML = items.map((item) => `<div class="client-suggestion-item" data-id="${item.id}"><div style="font-weight:600;font-size:13px;">${esc(item.label)}</div><div style="font-size:12px;color:var(--c-ink-40);">${esc(item.sub || '')}</div></div>`).join('');
      suggestions.style.display = 'block';
      suggestions.querySelectorAll('.client-suggestion-item').forEach((el, idx) => el.addEventListener('click', () => onPick(items[idx])));
    };

    searchInput?.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(async () => {
        const q = searchInput.value.trim();
        const type = typeInput.value;
        if (q.length < 2 || !type) { suggestions.style.display = 'none'; return; }

        if (type === 'article') {
          const { ok, data } = await Http.get(globalSearchRoutes.stockArticlesSearch, { q });
          if (!ok || !data.data) return;
          const rows = data.data.map((a) => ({ id: a.id, label: `${a.name}${a.sku ? ' (' + a.sku + ')' : ''}`, sub: `Stock: ${a.current_stock ?? a.stock_quantity ?? 0}`, payload: a }));
          renderSuggestions(rows, (choice) => {
            appendLine({ article_id: choice.payload.id, description: choice.payload.name, reference: choice.payload.sku || '', quantity: 1, unit: choice.payload.unit || 'piece', unit_price: choice.payload.sale_price || 0 });
            searchInput.value = choice.label;
            suggestions.style.display = 'none';
          });
        }

        if (type === 'order') {
          const { ok, data } = await Http.get(globalSearchRoutes.stockOrdersSearch, { q });
          if (!ok || !data.data) return;
          const rows = data.data.map((o) => ({ id: o.id, label: o.number, sub: `Statut: ${o.status}` }));
          renderSuggestions(rows, async (choice) => {
            const detail = await Http.get(routeTo('stockOrderDetail', { order: choice.id }));
            if (!detail.ok || !detail.data?.data) return;
            const order = detail.data.data;
            document.getElementById('stock_order_id').value = order.id;
            if (tbody.children.length === 1 && !tbody.querySelector('[name*="[description]"]')?.value) tbody.innerHTML = '';
            (order.items || []).forEach((it) => appendLine({ article_id: it.article_id || '', description: it.name, reference: it.article?.sku || '', quantity: it.quantity, unit: it.unit, unit_price: it.unit_price }));
            searchInput.value = choice.label;
            suggestions.style.display = 'none';
          });
        }
      }, 250);
    });
  }

  function initProTooltips() {
    const tooltip = document.createElement('div');
    tooltip.className = 'ui-tooltip';
    document.body.appendChild(tooltip);

    let activeEl = null;

    const getText = (el) => {
      if (!el) return '';
      return (el.getAttribute('data-tooltip') || el.getAttribute('title') || '').trim();
    };

    const setPosition = (el, evt) => {
      if (!el || !tooltip.classList.contains('show')) return;
      const margin = 12;
      const rect = el.getBoundingClientRect();
      const tipRect = tooltip.getBoundingClientRect();
      const x = evt?.clientX ?? (rect.left + rect.width / 2);
      let left = x - (tipRect.width / 2);
      left = Math.max(margin, Math.min(left, window.innerWidth - tipRect.width - margin));
      const top = rect.top - tipRect.height - 10;
      tooltip.style.left = `${left}px`;
      tooltip.style.top = `${Math.max(margin, top)}px`;
    };

    const show = (el, evt) => {
      const text = getText(el);
      if (!text) return;
      activeEl = el;
      tooltip.textContent = text;
      tooltip.classList.add('show');
      setPosition(el, evt);
    };

    const hide = () => {
      tooltip.classList.remove('show');
      activeEl = null;
    };

    const bind = (el) => {
      if (!el || el.dataset.tooltipBound === '1') return;
      const text = getText(el);
      if (!text) return;

      if (el.hasAttribute('title')) {
        const legacy = el.getAttribute('title');
        if (legacy && !el.getAttribute('data-tooltip')) {
          el.setAttribute('data-tooltip', legacy);
        }
        el.removeAttribute('title');
      }
      if (!el.getAttribute('aria-label')) {
        el.setAttribute('aria-label', text);
      }

      el.dataset.tooltipBound = '1';
      el.addEventListener('mouseenter', (e) => show(el, e));
      el.addEventListener('mousemove', (e) => setPosition(el, e));
      el.addEventListener('mouseleave', hide);
      el.addEventListener('focus', (e) => show(el, e));
      el.addEventListener('blur', hide);
    };

    const scan = () => {
      document.querySelectorAll('[data-tooltip], [title]').forEach(bind);
    };

    scan();
    new MutationObserver(scan).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['title', 'data-tooltip'] });
    window.addEventListener('scroll', () => activeEl && setPosition(activeEl), true);
    window.addEventListener('resize', () => activeEl && setPosition(activeEl));
  }

  document.addEventListener('DOMContentLoaded', () => {
    initHeaderNotifications();
    initGlobalSearch();
    initInvoiceStockBridge();
    initProTooltips();
  });
})();
</script>
@include('layouts.partials.tauri-bridge')
@stack('scripts')
</body>
</html>
