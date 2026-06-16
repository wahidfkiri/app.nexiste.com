<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Connexion Trello</title>
  <style>
    body{margin:0;min-height:100vh;display:grid;place-items:center;background:linear-gradient(135deg,#dbeafe,#f8fafc);font-family: "DM Sans", sans-serif;color:#0f172a}
    .card{width:min(520px,calc(100vw - 32px));background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:24px;padding:32px;box-shadow:0 30px 80px rgba(15,23,42,.14)}
    .pill{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:700;letter-spacing:.02em}
    h1{margin:18px 0 10px;font-size:28px;line-height:1.1}
    p{margin:0;color:#475569;line-height:1.6}
    .loader{width:46px;height:46px;border-radius:50%;border:4px solid rgba(37,99,235,.14);border-top-color:#2563eb;animation:spin .9s linear infinite;margin-bottom:20px}
    .error{background:#fef2f2;color:#b91c1c;border-radius:14px;padding:12px 14px;margin-top:18px;font-size:14px}
    @keyframes spin{to{transform:rotate(360deg)}}
  </style>
  <link rel="stylesheet" href="{{ asset('css/global-font.css') }}">
</head>
<body>
  <div class="card">
    <div class="loader" aria-hidden="true"></div>
    <div class="pill">Connexion Trello</div>
    <h1>Validation de votre workspace</h1>
    <p id="trelloCallbackMessage">Nous finalisons la connexion et la premiere synchronisation de vos boards.</p>
    <div id="trelloCallbackError" class="error" style="display:none;"></div>
  </div>

  <script>
    (function () {
      const params = new URLSearchParams(window.location.hash.replace(/^#/, ''));
      const token = params.get('token') || '';
      const error = params.get('error') || @json($error ?? '');
      const messageEl = document.getElementById('trelloCallbackMessage');
      const errorEl = document.getElementById('trelloCallbackError');

      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

      fetch(@json(route('trello-integration.oauth.finalize')), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
        },
        body: JSON.stringify({
          state: @json($state ?? ''),
          token,
          error,
        }),
      })
        .then(async (response) => {
          const data = await response.json().catch(() => ({}));
          if (!response.ok || !data.success) {
            throw new Error(data.message || 'Connexion Trello impossible.');
          }
          messageEl.textContent = data.message || 'Trello est maintenant connecte.';
          window.location.replace(data.redirect || @json(route('trello-integration.index')));
        })
        .catch((err) => {
          errorEl.style.display = 'block';
          errorEl.textContent = err?.message || 'Connexion Trello impossible.';
          messageEl.textContent = 'La connexion n a pas pu etre finalisee automatiquement.';
        });
    })();
  </script>
</body>
</html>
