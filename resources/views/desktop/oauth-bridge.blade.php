<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $title ?? 'Retour application desktop' }}</title>
  <style>
    :root{
      color-scheme:light;
      font-family: "DM Sans", sans-serif;
      background:linear-gradient(160deg,#f8fbff 0%,#e9f2ff 100%);
      color:#102033;
    }
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px}
    .card{
      width:min(720px,100%);
      background:#fff;
      border:1px solid rgba(15,23,42,.08);
      border-radius:24px;
      box-shadow:0 22px 56px rgba(15,23,42,.14);
      padding:30px;
    }
    .badge{
      display:inline-flex;
      align-items:center;
      gap:8px;
      border-radius:999px;
      padding:8px 14px;
      font-size:12px;
      font-weight:800;
      text-transform:uppercase;
      letter-spacing:.08em;
      background:{{ ($status ?? 'success') === 'error' ? 'rgba(220,38,38,.12)' : 'rgba(37,99,235,.12)' }};
      color:{{ ($status ?? 'success') === 'error' ? '#b91c1c' : '#1d4ed8' }};
    }
    h1{margin:18px 0 8px;font-size:32px;line-height:1.08}
    p{margin:0;color:#475569;font-size:15px;line-height:1.7}
    .meta{
      margin-top:20px;
      padding:14px 16px;
      border-radius:16px;
      background:#f8fafc;
      border:1px solid rgba(15,23,42,.06);
      font-family: "DM Sans", sans-serif;
      font-size:12px;
      color:#334155;
      word-break:break-all;
    }
    .actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:22px}
    .btn{
      appearance:none;
      border:1px solid transparent;
      border-radius:999px;
      padding:12px 18px;
      text-decoration:none;
      font-weight:700;
      cursor:pointer;
    }
    .btn-primary{background:#2563eb;color:#fff}
    .btn-secondary{background:#fff;border-color:rgba(15,23,42,.12);color:#0f172a}
  </style>
</head>
<body>
  <main class="card">
    <span class="badge">{{ ($status ?? 'success') === 'error' ? 'Erreur OAuth' : 'Retour vers l application' }}</span>
    <h1>{{ $title ?? 'Retour vers Nexus CRM Desktop' }}</h1>
    <p>{{ $message ?? 'Connexion terminee. Retour en cours dans l application desktop.' }}</p>

    <div class="meta">{{ $deepLinkUrl ?? '' }}</div>

    <div class="actions">
      <a class="btn btn-primary" href="{{ $deepLinkUrl ?? '#' }}">Ouvrir l application desktop</a>
      <a class="btn btn-secondary" href="{{ $targetUrl ?? url('/') }}">Continuer dans le navigateur</a>
    </div>
  </main>

  <script>
    (function () {
      var deepLink = @json($deepLinkUrl ?? '');
      var fallback = @json($targetUrl ?? url('/'));

      if (deepLink) {
        window.location.replace(deepLink);
      }

      window.setTimeout(function () {
        if (fallback) {
          window.location.replace(fallback);
        }
      }, 1800);
    })();
  </script>
</body>
</html>
