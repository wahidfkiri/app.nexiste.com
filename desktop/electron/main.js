'use strict';

const { app, BrowserWindow, shell, ipcMain } = require('electron');
const path = require('path');

// URL de l'application web (surchargeable via variable d'environnement).
const TARGET_URL = process.env.NEXISTE_CRM_URL || 'https://app.nexiste.com';
const APP_HOST = (() => {
  try { return new URL(TARGET_URL).host; } catch (_) { return ''; }
})();

let mainWindow = null;
let splashWindow = null;

/* --------------------------------------------------------------------------
 *  Splash / écran de chargement
 * ------------------------------------------------------------------------ */
function createSplash() {
  if (splashWindow && !splashWindow.isDestroyed()) return;

  splashWindow = new BrowserWindow({
    width: 460,
    height: 320,
    frame: false,
    resizable: false,
    center: true,
    alwaysOnTop: true,
    show: false,
    backgroundColor: '#0b1120',
    webPreferences: { contextIsolation: true, nodeIntegration: false },
  });

  splashWindow.loadFile(path.join(__dirname, 'renderer', 'splash.html'));
  splashWindow.once('ready-to-show', () => {
    if (splashWindow && !splashWindow.isDestroyed()) splashWindow.show();
  });
}

function closeSplash() {
  if (splashWindow && !splashWindow.isDestroyed()) splashWindow.close();
  splashWindow = null;
}

/* --------------------------------------------------------------------------
 *  Fenêtre principale
 * ------------------------------------------------------------------------ */
function createMainWindow() {
  mainWindow = new BrowserWindow({
    width: 1280,
    height: 820,
    minWidth: 960,
    minHeight: 600,
    show: false,
    backgroundColor: '#f8fafc',
    title: 'Nexiste CRM',
    autoHideMenuBar: true,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      contextIsolation: true,
      nodeIntegration: false,
      spellcheck: true,
    },
  });

  // Ouvrir les liens vers un autre domaine dans le navigateur système.
  mainWindow.webContents.setWindowOpenHandler(({ url }) => {
    try {
      if (new URL(url).host !== APP_HOST) {
        shell.openExternal(url);
        return { action: 'deny' };
      }
    } catch (_) { /* noop */ }
    return { action: 'allow' };
  });

  // Chargement réussi de l'app distante -> masquer le splash, afficher la fenêtre.
  mainWindow.webContents.on('did-finish-load', () => {
    const url = mainWindow.webContents.getURL();
    if (url.startsWith('http')) {
      closeSplash();
      if (!mainWindow.isVisible()) mainWindow.show();
    }
  });

  // Échec de chargement de la page principale (pas de réseau, DNS, etc.) -> écran hors-ligne.
  mainWindow.webContents.on('did-fail-load', (_event, errorCode, _desc, _validatedURL, isMainFrame) => {
    // -3 = ERR_ABORTED (souvent lors des redirections), on l'ignore.
    if (!isMainFrame || errorCode === -3) return;
    showOffline();
  });

  loadRemote();
}

function loadRemote() {
  createSplash();
  if (mainWindow && !mainWindow.isDestroyed()) {
    mainWindow.loadURL(TARGET_URL);
  }
}

function showOffline() {
  closeSplash();
  if (!mainWindow || mainWindow.isDestroyed()) return;
  mainWindow.loadFile(path.join(__dirname, 'renderer', 'offline.html'));
  if (!mainWindow.isVisible()) mainWindow.show();
}

/* --------------------------------------------------------------------------
 *  IPC : bouton « Réessayer » de l'écran hors-ligne
 * ------------------------------------------------------------------------ */
ipcMain.on('nexiste:retry', () => loadRemote());

/* --------------------------------------------------------------------------
 *  Cycle de vie de l'application
 * ------------------------------------------------------------------------ */
// Empêche plusieurs instances simultanées.
const gotLock = app.requestSingleInstanceLock();
if (!gotLock) {
  app.quit();
} else {
  app.on('second-instance', () => {
    if (mainWindow) {
      if (mainWindow.isMinimized()) mainWindow.restore();
      mainWindow.focus();
    }
  });

  app.whenReady().then(() => {
    createMainWindow();

    app.on('activate', () => {
      if (BrowserWindow.getAllWindows().length === 0) createMainWindow();
    });
  });

  app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') app.quit();
  });
}
