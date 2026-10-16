<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Nunito', Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #1e3a2b 0%, #2d5a27 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px;
            color: #333;
            line-height: 1.6;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1e3a2b;
            margin-bottom: 20px;
        }
        .message {
            margin: 20px 0;
            font-size: 14px;
        }
        .success-box {
            background-color: #f0fdf4;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
        }
        .success-box strong {
            color: #1e7e34;
            display: block;
            margin-bottom: 10px;
        }
        .success-box p {
            margin: 0;
            color: #2d6a4f;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            margin: 30px 0;
            border: none;
            cursor: pointer;
        }
        .button:hover {
            background: linear-gradient(135deg, #1e7e34 0%, #145a28 100%);
        }
        .details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin: 30px 0;
            font-size: 13px;
        }
        .details-row {
            margin: 10px 0;
        }
        .details-label {
            font-weight: 600;
            color: #1e3a2b;
        }
        .details-value {
            color: #666;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e9ecef;
        }
        .footer-logo {
            margin-bottom: 10px;
            font-weight: 700;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">✅</div>
            <h1>Compte Approuvé!</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Bonjour {{ $user->name }},
            </div>

            <div class="message">
                Nous sommes ravis de vous annoncer que <strong>votre demande d'inscription en tant que professionnel</strong> a été examinée et <strong>approuvée avec succès</strong> ! 🎉
            </div>

            <div class="success-box">
                <strong>🌟 Votre compte est maintenant actif!</strong>
                <p>Vous pouvez dès à présent vous connecter à votre compte et accéder à toutes les fonctionnalités de Farm to Fork.</p>
            </div>

            <!-- Activation Instructions -->
            <div style="margin: 30px 0;">
                <p style="font-weight: 600; color: #1e3a2b;">Comment se connecter:</p>
                <ol style="color: #555;">
                    <li>Visitez <a href="{{ $loginUrl }}" style="color: #28a745; text-decoration: none;">{{ $loginUrl }}</a></li>
                    <li>Entrez votre adresse e-mail: <strong>{{ $user->email }}</strong></li>
                    <li>Entrez votre mot de passe</li>
                    <li>Cliquez sur "Se connecter"</li>
                </ol>
            </div>

            <!-- Details -->
            <div class="details">
                <div class="details-row">
                    <span class="details-label">Entreprise:</span>
                    <span class="details-value">{{ $user->company_name }}</span>
                </div>
                <div class="details-row">
                    <span class="details-label">Type d'activité:</span>
                    <span class="details-value">{{ $user->getProTypeLabel() }}</span>
                </div>
                <div class="details-row">
                    <span class="details-label">Statut:</span>
                    <span class="details-value" style="color: #28a745; font-weight: 600;">Approuvé ✓</span>
                </div>
            </div>

            <div class="message" style="background-color: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; border-radius: 4px;">
                <strong style="color: #1565c0;">💡 À savoir:</strong><br>
                Vous avez accès à tous les outils de Farm to Fork pour:
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Tracer vos produits de la ferme à l'assiette</li>
                    <li>Afficher votre impact écologique (Éco-Score)</li>
                    <li>Gérer vos certifications et labels</li>
                    <li>Combattre le greenwashing</li>
                </ul>
            </div>

            <div class="message">
                Si vous avez des questions ou besoin d'assistance, n'hésitez pas à nous contacter à <strong>support@farmtofork.tn</strong>
            </div>

            <div style="text-align: center;">
                <a href="{{ $loginUrl }}" class="button">Se Connecter Maintenant</a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-logo">🌾 Farm to Fork</div>
            <p>De la ferme à l'assiette en toute transparence</p>
            <p style="margin-top: 15px; border-top: 1px solid #ddd; padding-top: 15px;">
                © 2026 Farm to Fork. Tous droits réservés.
            </p>
        </div>
    </div>
</body>
</html>
