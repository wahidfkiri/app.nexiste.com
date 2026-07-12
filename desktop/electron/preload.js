'use strict';

const { contextBridge, ipcRenderer } = require('electron');

// API minimale et sécurisée exposée aux pages locales (offline.html).
// contextIsolation activé + pas de nodeIntegration : le web distant n'a aucun
// accès à Node, seulement à cette petite surface.
contextBridge.exposeInMainWorld('nexisteDesktop', {
  retry: () => ipcRenderer.send('nexiste:retry'),
  isDesktop: true,
});
