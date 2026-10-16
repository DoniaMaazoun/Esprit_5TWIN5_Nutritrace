# ============================================
# Farm to Fork - Setup Automatique (PowerShell)
# ============================================

Write-Host ""
Write-Host "====================================" -ForegroundColor Green
Write-Host " Farm to Fork - Installation" -ForegroundColor Green
Write-Host "====================================" -ForegroundColor Green
Write-Host ""

# 1. Créer la base de données MySQL
Write-Host "[1/5] Création de la base de données MySQL..." -ForegroundColor Cyan
try {
    $mysqlCmd = "CREATE DATABASE IF NOT EXISTS farm_to_fork CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    $null | mysql -u root -e $mysqlCmd 2>$null
    Write-Host "[✓] Base de données créée" -ForegroundColor Green
} catch {
    Write-Host ""
    Write-Host "ERREUR: MySQL n'est pas disponible!" -ForegroundColor Red
    Write-Host "Assurez-vous que:" -ForegroundColor Yellow
    Write-Host "  - XAMPP/WAMP est lancé"
    Write-Host "  - MySQL est démarré dans le Control Panel"
    Write-Host ""
    Read-Host "Appuyez sur Entrée pour quitter"
    exit 1
}

# 2. Installer les dépendances PHP
Write-Host ""
Write-Host "[2/5] Installation des dépendances PHP..." -ForegroundColor Cyan
& composer install --no-interaction
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERREUR: Composer installation échouée" -ForegroundColor Red
    Read-Host "Appuyez sur Entrée pour quitter"
    exit 1
}
Write-Host "[✓] Dépendances installées" -ForegroundColor Green

# 3. Configuration de l'application
Write-Host ""
Write-Host "[3/5] Configuration de l'application..." -ForegroundColor Cyan
& php artisan key:generate --force
Write-Host "[✓] Clé d'application générée" -ForegroundColor Green

# 4. Exécuter les migrations
Write-Host ""
Write-Host "[4/5] Création des tables de la base de données..." -ForegroundColor Cyan
& php artisan migrate --force
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERREUR: Les migrations ont échoué" -ForegroundColor Red
    Read-Host "Appuyez sur Entrée pour quitter"
    exit 1
}
Write-Host "[✓] Tables créées" -ForegroundColor Green

# 5. Remplir la base avec les données de test
Write-Host ""
Write-Host "[5/5] Création des utilisateurs de test..." -ForegroundColor Cyan
& php artisan db:seed --force
Write-Host "[✓] Données de test créées" -ForegroundColor Green

# Succès
Write-Host ""
Write-Host "====================================" -ForegroundColor Green
Write-Host " ✓ Installation Réussie!" -ForegroundColor Green
Write-Host "====================================" -ForegroundColor Green
Write-Host ""

Write-Host "Utilisateurs de test créés:" -ForegroundColor Yellow
Write-Host "  - Admin: admin@test.tn / password123"
Write-Host "  - Consumer: consumer@test.tn / password123"
Write-Host "  - Pro (Pending): pro@test.tn / password123"
Write-Host "  - Pro (Approved): pro-approved@test.tn / password123"
Write-Host ""

Write-Host "Prochaines étapes:" -ForegroundColor Cyan
Write-Host "  1. Lance l'app: php artisan serve"
Write-Host "  2. Ouvre: http://localhost:8000"
Write-Host ""

Read-Host "Appuyez sur Entrée pour terminer"
