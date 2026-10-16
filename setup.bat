@echo off
REM ============================================
REM Farm to Fork - Setup Automatique
REM ============================================

echo.
echo ====================================
echo  Farm to Fork - Installation
echo ====================================
echo.

REM Créer la base de données MySQL
echo [1/5] Création de la base de données MySQL...
mysql -u root -e "CREATE DATABASE IF NOT EXISTS farm_to_fork CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %errorlevel% neq 0 (
    echo.
    echo ERREUR: MySQL n'est pas disponible!
    echo Assurez-vous que:
    echo - XAMPP/WAMP est lancé
    echo - MySQL est démarré dans le Control Panel
    echo.
    pause
    exit /b 1
)

echo [✓] Base de données créée

REM Installer les dépendances PHP
echo.
echo [2/5] Installation des dépendances PHP...
call composer install --no-interaction
if %errorlevel% neq 0 (
    echo ERREUR: Composer installation échouée
    pause
    exit /b 1
)
echo [✓] Dépendances installées

REM Générer la clé de l'application (déjà faite, mais par sécurité)
echo.
echo [3/5] Configuration de l'application...
php artisan key:generate --force
echo [✓] Clé d'application générée

REM Exécuter les migrations
echo.
echo [4/5] Création des tables de la base de données...
php artisan migrate --force
if %errorlevel% neq 0 (
    echo ERREUR: Les migrations ont échoué
    pause
    exit /b 1
)
echo [✓] Tables créées

REM Remplir la base avec les données de test
echo.
echo [5/5] Création des utilisateurs de test...
php artisan db:seed --force
echo [✓] Données de test créées

REM Succès
echo.
echo ====================================
echo  ✓ Installation Réussie!
echo ====================================
echo.
echo Utilisateurs de test créés:
echo   - Admin: admin@test.tn / password123
echo   - Consumer: consumer@test.tn / password123
echo   - Pro (Pending): pro@test.tn / password123
echo   - Pro (Approved): pro-approved@test.tn / password123
echo.
echo Prochaines étapes:
echo   1. Lance l'app: php artisan serve
echo   2. Ouvre: http://localhost:8000
echo.
pause
