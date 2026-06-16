(function () {
  'use strict';

  var tauri = window.__TAURI__ || null;
  var opener = tauri && tauri.opener ? tauri.opener : null;
  var notification = tauri && tauri.notification ? tauri.notification : null;
  var deepLink = tauri && tauri.deepLink ? tauri.deepLink : null;
  var core = tauri && tauri.core ? tauri.core : null;
  var authLinkPatterns = [
    /\/oauth\/connect(?:[/?#]|$)/i,
    /\/auth\/[^/]+\/redirect(?:[/?#]|$)/i
  ];
  var gmailTenantConnectPattern = /\/extensions\/google-gmail\/oauth\/connect(?:[/?#]|$)/i;

  function isDesktop() {
    return !!(window.__TAURI__ && window.isSecureContext);
  }

  function toAbsoluteUrl(value) {
    try {
      return new URL(String(value || ''), window.location.origin).toString();
    } catch (_error) {
      return '';
    }
  }

  function syncDesktopFlags() {
    if (!isDesktop()) return;

    document.documentElement.setAttribute('data-desktop-shell', 'tauri');
    if (document.body) {
      document.body.classList.add('is-tauri-shell');
    } else {
      document.addEventListener('DOMContentLoaded', function () {
        document.body.classList.add('is-tauri-shell');
      }, { once: true });
    }
  }

  function currentReturnPath() {
    return window.location.pathname + window.location.search + window.location.hash;
  }

  function withDesktopParams(url, extraParams) {
    var absoluteUrl;
    try {
      absoluteUrl = new URL(String(url || ''), window.location.origin);
    } catch (_error) {
      return '';
    }

    absoluteUrl.searchParams.set('desktop', 'tauri');
    absoluteUrl.searchParams.set('desktop_return', currentReturnPath());

    if (extraParams && typeof extraParams === 'object') {
      Object.keys(extraParams).forEach(function (key) {
        if (extraParams[key] === undefined || extraParams[key] === null) return;
        absoluteUrl.searchParams.set(key, String(extraParams[key]));
      });
    }

    return absoluteUrl.toString();
  }

  async function openExternal(url) {
    var absoluteUrl = toAbsoluteUrl(url);
    if (!absoluteUrl) return false;

    if (opener && typeof opener.openUrl === 'function') {
      await opener.openUrl(absoluteUrl);
      return true;
    }

    window.open(absoluteUrl, '_blank', 'noopener,noreferrer');
    return true;
  }

  function showToast(type, title, message) {
    if (!window.Toast || typeof window.Toast[type] !== 'function') return false;

    window.Toast[type](title, message);
    return true;
  }

  function consumeDesktopMessages() {
    var parsed;

    try {
      parsed = new URL(window.location.href);
    } catch (_error) {
      return;
    }

    var notice = parsed.searchParams.get('desktop_notice');
    var error = parsed.searchParams.get('desktop_error');
    var changed = false;

    if (notice) {
      showToast('success', 'Desktop', notice);
      parsed.searchParams.delete('desktop_notice');
      changed = true;
    }

    if (error) {
      showToast('error', 'Desktop', error);
      parsed.searchParams.delete('desktop_error');
      changed = true;
    }

    if (changed && window.history && typeof window.history.replaceState === 'function') {
      var next = parsed.pathname + (parsed.search ? parsed.search : '') + (parsed.hash ? parsed.hash : '');
      window.history.replaceState({}, document.title, next);
    }
  }

  async function notify(title, body, options) {
    if (!notification || typeof notification.sendNotification !== 'function') {
      return false;
    }

    var granted = true;
    if (typeof notification.isPermissionGranted === 'function') {
      granted = await notification.isPermissionGranted();
    }

    if (!granted && typeof notification.requestPermission === 'function') {
      granted = (await notification.requestPermission()) === 'granted';
    }

    if (!granted) {
      return false;
    }

    var payload = typeof title === 'object'
      ? title
      : Object.assign({
          title: String(title || 'Nexus CRM'),
          body: String(body || '')
        }, options || {});

    await notification.sendNotification(payload);
    return true;
  }

  function emitDeepLinkEvent(urls, source) {
    var items = Array.isArray(urls) ? urls.filter(Boolean) : [];
    if (!items.length) return items;

    var detail = { urls: items, source: source || 'runtime' };
    window.dispatchEvent(new CustomEvent('nexus-desktop:deep-link', { detail: detail }));
    return items;
  }

  function applyDeepLinkRedirect(urls) {
    if (!Array.isArray(urls) || !urls.length) return false;

    for (var i = 0; i < urls.length; i += 1) {
      try {
        var parsed = new URL(urls[i]);
        if (parsed.protocol !== 'nexuscrm:') continue;

        var redirectUrl = parsed.searchParams.get('url');
        var redirectPath = parsed.searchParams.get('path') || parsed.searchParams.get('target') || parsed.searchParams.get('redirect');

        if (redirectUrl) {
          window.location.href = redirectUrl;
          return true;
        }

        if (redirectPath && redirectPath.charAt(0) === '/') {
          window.location.href = new URL(redirectPath, window.location.origin).toString();
          return true;
        }
      } catch (_error) {
        // no-op
      }
    }

    return false;
  }

  async function getPendingDeepLinks() {
    if (!deepLink || typeof deepLink.getCurrent !== 'function') {
      return [];
    }

    try {
      var urls = await deepLink.getCurrent();
      var items = emitDeepLinkEvent(urls || [], 'launch');
      applyDeepLinkRedirect(items);
      return items;
    } catch (_error) {
      return [];
    }
  }

  async function listenDeepLinks() {
    if (!deepLink || typeof deepLink.onOpenUrl !== 'function') {
      return null;
    }

    try {
      return await deepLink.onOpenUrl(function (urls) {
        var items = emitDeepLinkEvent(urls, 'runtime');
        applyDeepLinkRedirect(items);
      });
    } catch (_error) {
      return null;
    }
  }

  async function getContext() {
    if (!core || typeof core.invoke !== 'function') {
      return null;
    }

    try {
      return await core.invoke('desktop_context');
    } catch (_error) {
      return null;
    }
  }

  function onDeepLink(handler) {
    if (typeof handler !== 'function') return function () {};

    var listener = function (event) {
      handler(event.detail || { urls: [] });
    };

    window.addEventListener('nexus-desktop:deep-link', listener);
    return function () {
      window.removeEventListener('nexus-desktop:deep-link', listener);
    };
  }

  function shouldOpenExternally(anchor) {
    if (!anchor || !anchor.href) return false;

    if (anchor.dataset.tauriExternal === 'true') {
      return true;
    }

    var href = String(anchor.href || '');
    if (/^https?:\/\//i.test(href)) {
      try {
        var target = new URL(href);
        if (target.origin !== window.location.origin) {
          return true;
        }
      } catch (_error) {
        return false;
      }
    }

    return authLinkPatterns.some(function (pattern) {
      return pattern.test(href);
    });
  }

  async function requestAuthenticatedOAuthUrl(url) {
    var preparedUrl = withDesktopParams(url, { desktop_fetch: 1 });
    if (!preparedUrl) {
      throw new Error('URL OAuth desktop invalide.');
    }

    var response = await fetch(preparedUrl, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    var data = {};
    try {
      data = await response.json();
    } catch (_error) {
      data = {};
    }

    if (!response.ok || !data.success || !data.open_url) {
      throw new Error((data && data.message) || 'Impossible d ouvrir OAuth depuis le desktop.');
    }

    return String(data.open_url);
  }

  async function handleDesktopNavigation(anchor) {
    var href = String(anchor && anchor.href ? anchor.href : '');
    if (!href) return false;

    if (gmailTenantConnectPattern.test(href)) {
      var oauthUrl = await requestAuthenticatedOAuthUrl(href);
      return openExternal(oauthUrl);
    }

    var preparedUrl = authLinkPatterns.some(function (pattern) {
      return pattern.test(href);
    }) ? withDesktopParams(href) : href;

    return openExternal(preparedUrl || href);
  }

  function bindExternalNavigation() {
    document.addEventListener('click', function (event) {
      if (!isDesktop()) return;

      var anchor = event.target && event.target.closest ? event.target.closest('a[href]') : null;
      if (!anchor || !shouldOpenExternally(anchor)) return;

      event.preventDefault();

      handleDesktopNavigation(anchor).catch(function (error) {
        if (!showToast('error', 'Desktop', error && error.message ? error.message : 'Impossible d ouvrir le lien.')) {
          console.error(error);
        }
      });
    }, true);
  }

  window.NexusDesktop = {
    isDesktop: isDesktop,
    openExternal: openExternal,
    notify: notify,
    getPendingDeepLinks: getPendingDeepLinks,
    listenDeepLinks: listenDeepLinks,
    getContext: getContext,
    onDeepLink: onDeepLink
  };

  syncDesktopFlags();
  bindExternalNavigation();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      window.setTimeout(consumeDesktopMessages, 80);
    }, { once: true });
  } else {
    window.setTimeout(consumeDesktopMessages, 80);
  }

  if (isDesktop()) {
    getPendingDeepLinks();
    listenDeepLinks();
  }
})();
