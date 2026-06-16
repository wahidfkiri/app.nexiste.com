import { invoke } from "@tauri-apps/api/core";
import { getCurrent, onOpenUrl } from "@tauri-apps/plugin-deep-link";
import { openUrl } from "@tauri-apps/plugin-opener";

type DesktopContext = {
  productName: string;
  version: string;
  desktopMode: string;
  desktopScheme: string;
};

const DEFAULT_URL = "http://127.0.0.1:8000/login";
const TARGET_URL = normalizeTargetUrl(import.meta.env.VITE_CRM_DESKTOP_URL || DEFAULT_URL);
const AUTO_REDIRECT_MS = normalizeDelay(import.meta.env.VITE_CRM_AUTO_REDIRECT_MS);
const HOLD_BOOT = new URLSearchParams(window.location.search).get("hold") === "1";

function normalizeTargetUrl(value: string): string {
  const trimmed = String(value || "").trim();

  if (!trimmed) {
    return DEFAULT_URL;
  }

  try {
    return new URL(trimmed).toString();
  } catch (_error) {
    return DEFAULT_URL;
  }
}

function normalizeDelay(value: string | undefined): number {
  const parsed = Number(value || 1200);
  if (!Number.isFinite(parsed)) {
    return 1200;
  }

  return Math.max(350, Math.min(10_000, Math.floor(parsed)));
}

function setText(id: string, value: string) {
  const el = document.getElementById(id);
  if (el) {
    el.textContent = value;
  }
}

function setStatus(value: string) {
  setText("statusValue", value);
}

function setHref(id: string, value: string) {
  const el = document.getElementById(id) as HTMLAnchorElement | null;
  if (el) {
    el.href = value;
  }
}

async function loadDesktopContext() {
  try {
    const context = await invoke<DesktopContext>("desktop_context");
    setText("appName", context.productName || "Nexus CRM");
    setText("desktopMode", context.desktopMode || "tauri");
    setText("desktopVersion", context.version || "0.1.0");
    setText("desktopScheme", context.desktopScheme || "nexuscrm");
  } catch (_error) {
    setText("appName", "Nexus CRM");
    setText("desktopMode", "tauri");
    setText("desktopVersion", "0.1.0");
    setText("desktopScheme", "nexuscrm");
  }
}

function rememberDeepLinks(urls: string[] | null | undefined, source: string) {
  const items = Array.isArray(urls) ? urls.filter(Boolean) : [];
  if (!items.length) {
    return;
  }

  setText("lastDeepLink", items[0]);
  window.dispatchEvent(
    new CustomEvent("nexus-desktop:deep-link", {
      detail: {
        source,
        urls: items,
      },
    }),
  );
}

async function bindDeepLinks() {
  try {
    const current = await getCurrent();
    rememberDeepLinks(current || [], "launch");
  } catch (_error) {
    // no-op: desktop bootstrap still works without deep links
  }

  try {
    await onOpenUrl((urls) => {
      rememberDeepLinks(urls, "runtime");
    });
  } catch (_error) {
    // no-op
  }
}

async function openCrmInBrowser() {
  await openUrl(TARGET_URL);
}

function connectButtons() {
  document.getElementById("openBrowserBtn")?.addEventListener("click", async () => {
    setStatus("Ouverture du CRM dans votre navigateur par defaut...");
    await openCrmInBrowser();
  });

  document.getElementById("retryBtn")?.addEventListener("click", () => {
    setStatus("Nouvelle tentative de connexion au CRM...");
    window.location.replace(TARGET_URL);
  });

  document.getElementById("holdBtn")?.addEventListener("click", () => {
    const holdUrl = new URL(window.location.href);
    holdUrl.searchParams.set("hold", "1");
    window.location.href = holdUrl.toString();
  });
}

function startAutoRedirect() {
  if (HOLD_BOOT) {
    setStatus("Mode pause actif. Utilisez le bouton Reprendre pour ouvrir le CRM.");
    return;
  }

  setStatus("Connexion au CRM Laravel en cours...");

  window.setTimeout(() => {
    window.location.replace(TARGET_URL);
  }, AUTO_REDIRECT_MS);
}

window.addEventListener("DOMContentLoaded", async () => {
  setText("targetUrl", TARGET_URL);
  setText("autoRedirectValue", `${AUTO_REDIRECT_MS} ms`);
  setHref("targetUrlLink", TARGET_URL);

  connectButtons();
  await loadDesktopContext();
  await bindDeepLinks();
  startAutoRedirect();
});
