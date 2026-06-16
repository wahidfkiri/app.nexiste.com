param(
    [ValidateSet('dev', 'build', 'build-no-bundle')]
    [string]$Action = 'dev'
)

$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent (Split-Path -Parent (Split-Path -Parent $PSScriptRoot))
$tauriRoot = Join-Path $repoRoot 'desktop\tauri'
$cargoHome = 'D:\Rust\.cargo'
$rustupHome = 'D:\Rust\.rustup'
$cargoBin = Join-Path $env:USERPROFILE '.cargo\bin'
$vsDevCmdCandidates = @(
    'D:\VS\BuildTools\Common7\Tools\VsDevCmd.bat',
    'C:\Program Files\Microsoft Visual Studio\2022\BuildTools\Common7\Tools\VsDevCmd.bat',
    'C:\Program Files\Microsoft Visual Studio\2022\Community\Common7\Tools\VsDevCmd.bat',
    'C:\Program Files\Microsoft Visual Studio\2022\Professional\Common7\Tools\VsDevCmd.bat',
    'C:\Program Files\Microsoft Visual Studio\2022\Enterprise\Common7\Tools\VsDevCmd.bat'
)

New-Item -ItemType Directory -Force -Path $cargoHome, $rustupHome | Out-Null

$env:CARGO_HOME = $cargoHome
$env:RUSTUP_HOME = $rustupHome
$env:Path = "$cargoBin;$($cargoHome)\bin;$env:Path"

$npmArgs = switch ($Action) {
    'dev' { 'run tauri dev' }
    'build' { 'run tauri build' }
    'build-no-bundle' { 'run tauri build -- --no-bundle' }
}

if (-not (Get-Command rustup.exe -ErrorAction SilentlyContinue)) {
    throw "rustup.exe introuvable dans $cargoBin. Relancez l'installation de Rust."
}

if (-not (Get-Command cargo.exe -ErrorAction SilentlyContinue)) {
    throw "cargo.exe introuvable. Verifiez CARGO_HOME/RUSTUP_HOME."
}

$vsDevCmd = $vsDevCmdCandidates | Where-Object { Test-Path $_ } | Select-Object -First 1

Push-Location $tauriRoot
try {
    if ($vsDevCmd) {
        & cmd /c "`"$vsDevCmd`" -no_logo -arch=x64 -host_arch=x64 && npm.cmd $npmArgs"
    } else {
        if (-not (Get-Command link.exe -ErrorAction SilentlyContinue)) {
            Write-Warning 'link.exe est introuvable. Installez Visual Studio Build Tools (Desktop development with C++) pour compiler Tauri sous Windows.'
        }

        & cmd /c "npm.cmd $npmArgs"
    }
} finally {
    Pop-Location
}
