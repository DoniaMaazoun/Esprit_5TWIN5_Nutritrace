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
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
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
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
        }
        .warning-box strong {
            color: #856404;
            display: block;
            margin-bottom: 10px;
        }
        .warning-box p {
            margin: 0;
            color: #856404;
        }
        .reason-box {
            background-color: #ffe8e8;
            border-left: 4px solid #dc3545;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
        }
        .reason-box strong {
            color: #721c24;
            display: block;
            margin-bottom: 10px;
        }
        .reason-box p {
            margin: 0;
            color: #721c24;
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
        .next-steps {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
        }
        .next-steps strong {
            color: #1565c0;
            display: block;
            margin-bottom: 10px;
        }
        .next-steps ul {
            margin: 10px 0;
            padding-left: 20px;
            color: #1565c0;
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
        .contact-support {
            background-color: #f0f9f7;
            border: 1px solid #28a745;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
            text-align: center;
        }
        .contact-support strong {
            color: #1e3a2b;
            display: block;
            margin-bottom: 10px;
        }
        .contact-support a {
            color: #28a745;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">⚠️</div>
            <h1>Demande Examinée</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Bonjour {{ $user->name }},
            </div>

            <div class="message">
                Nous avons examiné votre demande d'inscription en tant que professionnel sur Farm to Fork. Malheureusement, <strong>votre compte n'a pas pu être approuvé à ce stade</strong>.
            </div>

            <!-- Reason if provided -->
            @if($reason)
                <div class="reason-box">
                    <strong>🔍 Raison:</strong>
                    <p>{{ $reason }}</p>
                </div>
            @else
                <div class="warning-box">
                    <strong>ℹ️ Informations:</strong>
                    <p>Les informations fournies lors de votre inscription ne correspondent pas à nos critères de vérification.</p>
                </div>
            @endif

            <!-- Next Steps -->
            <div class="next-steps">
                <strong>📋 Prochaines Étapes:</strong>
                <ul>
                    <li>Vérifiez que tous les documents fournis sont corrects et à jour</li>
                    <li>Assurez-vous que votre identifiant fiscal est valide</li>
                    <li>Contactez notre support pour plus de détails</li>
                    <li>Vous pouvez réessayer de vous inscrire avec des informations mises à jour</li>
                </ul>
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
                    <span class="details-label">Email:</span>
                    <span class="details-value">{{ $user->email }}</span>
                </div>
                <div class="details-row">
                    <span class="details-label">Statut:</span>
                    <span class="details-value" style="color: #dc3545; font-weight: 600;">Rejeté ✗</span>
                </div>
            </div>

            <!-- Contact Support -->
            <div class="contact-support">
                <strong>💬 Besoin d'aide?</strong>
                <p>Contactez notre équipe support pour comprendre pourquoi votre demande a été rejetée et comment la corriger.</p>
                <p>
                    <a href="mailto:support@farmtofork.tn">support@farmtofork.tn</a>
                </p>
            </div>

            <!-- Re-register Option -->
            <div style="text-align: center; margin-top: 30px;">
                <p style="color: #666;">Vous pouvez réessayer de vous inscrire avec des informations à jour</p>
                <a href="{{ $registerUrl }}" class="button">Nouvelle Inscription</a>
            </div>

            <div class="message" style="margin-top: 30px; font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 20px;">
                Nous regrettons de ne pas pouvoir approuver votre compte à cette occasion. Farm to Fork travaille dur pour maintenir la confiance et l'intégrité de notre plateforme.
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
