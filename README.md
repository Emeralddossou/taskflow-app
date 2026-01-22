# 🚀 TaskFlow - Application de Gestion de Tâches

TaskFlow est une application web de gestion de tâches personnelle, robuste et sécurisée, développée en PHP 8.2 natif. Elle offre une interface fluide pour gérer vos priorités quotidiennes tout en assurant la sécurité de vos données.

## ✨ Fonctionnalités Principales

- **Gestion de Tâches** : Création, modification, suppression et suivi (En attente / En cours / Terminée).
- **Organisation** : Système de priorités (Basse, Moyenne, Haute) et dates d'échéance.
- **Sécurité Avancée** :
  - Authentification chiffrée.
  - Protection contre les attaques XSS et Injections SQL.
  - Protection CSRF (Cross-Site Request Forgery).
  - Verrouillage de compte après plusieurs tentatives échouées.
- **Interface Moderne** : Design responsive et intuitif.
- **Suivi** : Tableau de bord avec statistiques.

## 🛠️ Prérequis Techniques

Avant de commencer, assurez-vous d'avoir l'environnement suivant :

- **PHP 8.2** ou supérieur.
- **MySQL 5.7** ou supérieur (ou MariaDB).
- **Composer** (pour la gestion des dépendances de développement).
- Un serveur web (Apache ou Nginx, via Laragon, WAMP, XAMPP, etc.).

## 📥 Installation et Configuration

Suivez ces étapes pour installer l'application sur votre machine locale.

### 1. Récupérer le projet

Clonez le dépôt ou extrayez les fichiers dans un dossier accessible par votre serveur web (ex: `C:\laragon\www\taskflow-app`).

### 2. Installer les dépendances

Ouvrez un terminal dans le dossier du projet et exécutez la commande suivante pour installer les outils de développement (PHPUnit, PHPStan, etc.) :

```bash
composer install
```

### 3. Base de Données

1. Créez une nouvelle base de données MySQL via votre outil préféré (phpMyAdmin, HeidiSQL, etc.). Nommez-la par exemple `taskflow`.
2. Importez la structure et les données initiales :
   - Le fichier SQL se trouve ici : `databases/schema.sql`.
   - Importez ce fichier dans votre base de données `taskflow`.
   *Ce script créera les tables nécessaires et un compte utilisateur de test.*

### 4. Configuration de l'Application

L'application a besoin de connaître vos identifiants de base de données.

1. Ouvrez le fichier `includes/config.php` dans votre éditeur de code.
2. Localisez la section **Configuration de la base de données**.
3. Modifiez les valeurs par défaut (ou les valeurs de repli) pour qu'elles correspondent à votre configuration locale.

*Exemple de modification pour un environnement local standard :*

```php
// Avant modification (exemple)
// define('DB_HOST', $_ENV['DB_HOST'] ?? ... ?? 'sql100.infinityfree.com');

// Après modification (pour Laragon/Localhost)
define('DB_HOST', 'localhost');
define('DB_NAME', 'taskflow');    // Remplacez par le nom de votre base
define('DB_USER', 'root');        // Votre utilisateur (souvent root)
define('DB_PASS', '');            // Votre mot de passe (souvent vide sous Laragon)
```

4. Vérifiez la **Configuration des chemins** dans le même fichier :
   Modifiez `BASE_URL` si nécessaire pour qu'elle corresponde à l'adresse URL de votre projet local.
   
```php
define('BASE_URL', 'http://taskflow-app.test/'); // Si vous utilisez Laragon avec les pretty urls
// OU
define('BASE_URL', 'http://localhost/taskflow-app/'); // Configuration standard
```

## 🚀 Utilisation

1. Lancez votre serveur web.
2. Accédez à l'application via votre navigateur.
3. Connectez-vous avec le compte de démonstration :
   - **Nom d'utilisateur** : `testuser` (ou Email : `test@example.com`)
   - **Mot de passe** : `Test123!`
4. Vous pouvez maintenant gérer vos tâches ! N'oubliez pas de changer le mot de passe ou de créer un nouveau compte pour une utilisation réelle.

## 🧪 Développement et Qualité du Code

Si vous souhaitez contribuer ou modifier le code, utilisez les commandes Composer configurées :

- **Lancer les tests unitaires** :
  ```bash
  composer test
  ```
- **Analyse statique (PHPStan)** :
  ```bash
  composer analyse
  ```
- **Vérification du style (PHPCS)** :
  ```bash
  composer lint
  ```
- **Audit complet (CI)** :
  ```bash
  composer ci
  ```

## 📂 Structure des Dossiers

- `/api` : Endpoints pour les requêtes AJAX.
- `/databases` : Schéma de la base de données.
- `/includes` : Fichiers de configuration, fonctions utilitaires et logique backend.
- `/scripts` : Scripts utilitaires (ex: audit de sécurité).
- `/tests` : Tests automatisés.
- `/vendor` : Dépendances Composer.
