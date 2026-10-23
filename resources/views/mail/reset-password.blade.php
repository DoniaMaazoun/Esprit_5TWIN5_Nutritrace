<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Nunito', Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); }
        .header { background: linear-gradient(135deg, #1e3a2b 0%, #2d5a27 100%); color: white; padding: 40px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 26px; font-weight: 700; }
        .header-icon { font-size: 48px; margin-bottom: 10px; }
        .content { padding: 40px; color: #333; line-height: 1.6; }
        .greeting { font-size: 18px; font-weight: 600; color: #1e3a2b; margin-bottom: 20px; }
        .message { margin: 20px 0; font-size: 14px; }
        .button { display: inline-block; background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: white !important; padding: 12px 30px; border-radius: 4px; text-decoration: none; font-weight: 600; margin: 20px 0; }
        .warning-box { background-color: #fff8e6; border-left: 4px solid #f0ad4e; padding: 15px 20px; margin: 25px 0; border-radius: 4px; font-size: 13px; color: #7a5300; }
        .subcopy { font-size: 12px; color: #666; border-top: 1px solid #e9ecef; padding-top: 15px; margin-top: 25px; word-break: break-all; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #e9ecef; }
        .footer-logo { margin-bottom: 10px; font-weight: 700; color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">🔑</div>
            <h1>Réinitialisation du mot de passe</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Bonjour {{ $user->name }},
            </div>

            <div class="message">
                Vous recevez cet e-mail car une réinitialisation du mot de passe a été demandée pour votre compte <strong>Farm to Fork</strong> ({{ $user->email }}).
            </div>

            <div style="text-align: center;">
                <a href="{{ $url }}" class="button">Réinitialiser mon mot de passe</a>
            </div>

            <div class="warning-box">
                ⏳ Ce lien expirera dans <strong>{{ $minutes }} minutes</strong> et ne peut être utilisé qu'une seule fois.<br>
                Si vous n'êtes pas à l'origine de cette demande, ignorez cet e-mail : votre mot de passe reste inchangé.
            </div>

            <div class="subcopy">
                Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>
                <a href="{{ $url }}" style="color: #28a745;">{{ $url }}</a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-logo">🌾 Farm to Fork</div>
            <p>De la ferme à l'assiette en toute transparence</p>
            <p style="margin-top: 15px; border-top: 1px solid #ddd; padding-top: 15px;">
                © {{ date('Y') }} Farm to Fork. Tous droits réservés.
            </p>
        </div>
    </div>
</body>
</html>
