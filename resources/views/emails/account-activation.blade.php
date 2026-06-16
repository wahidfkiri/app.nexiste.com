<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Activation de compte</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fb;font-family: "DM Sans", sans-serif;color:#0f172a;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:30px 14px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;">
          <tr>
            <td style="background:linear-gradient(135deg,#0f172a,#1d4ed8);padding:26px 28px;color:#ffffff;">
              <h1 style="margin:0;font-size:24px;">Activation du compte</h1>
              <p style="margin:10px 0 0;font-size:14px;opacity:.9;">Finalisez la creation de votre espace NexusCRM.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:26px 28px;">
              <p style="margin:0 0 14px;font-size:15px;">Bonjour {{ $user->name ?? $user->email }},</p>
              <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#334155;">
                Merci pour votre inscription. Cliquez sur le bouton ci-dessous pour activer votre compte.
              </p>
              <p style="margin:0 0 24px;">
                <a href="{{ $activationUrl }}" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;padding:11px 20px;border-radius:10px;font-weight:700;font-size:14px;">
                  Activer mon compte
                </a>
              </p>
              <p style="margin:0;font-size:12px;line-height:1.5;color:#64748b;">
                Ce lien expire dans 24 heures. Si vous n'etes pas a l'origine de cette demande, ignorez simplement cet email.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>

