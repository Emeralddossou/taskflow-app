# 🚀 TaskFlow - Application de Gestion de Tâches

TaskFlow est une application web de gestion de tâches personnelle, robuste et sécurisée, développée en **PHP 8.2+ natif**. Elle offre une interface fluide pour gérer vos priorités quotidiennes tout en assurant la sécurité de vos données grâce à un pipeline CI/CD automatisé et des contrôles de sécurité avancés.

## 📋 Table des Matières

- [Fonctionnalités Principales](#-fonctionnalités-principales)
- [Architecture](#️-architecture)
- [Pipeline CI/CD & Sécurité](#-pipeline-cicd--sécurité)
- [Installation](#-installation-et-configuration)
- [Utilisation](#-utilisation)
- [Développement](#-développement-et-qualité-du-code)
- [Contribution](#-contribution)

## ✨ Fonctionnalités Principales

- **Gestion Complète de Tâches** : Création, modification, suppression et suivi (En attente / En cours / Terminée)
- **Organisation Intelligente** : Système de priorités (Basse, Moyenne, Haute) et dates d'échéance
- **Tableau de Bord Statistique** : Visualisation en temps réel de vos tâches et progression
- **API RESTful** : Endpoint `/api/tasks.php` pour intégration avec d'autres systèmes
- **Sécurité Avancée** :
  - ✅ Authentification avec hachage BCrypt (coût 12)
  - ✅ Protection contre les attaques XSS et Injections SQL (PDO Prepared Statements)
  - ✅ Protection CSRF avec tokens liés à la session
  - ✅ Verrouillage de compte après 5 tentatives échouées (15 min)
  - ✅ Session sécurisée avec HttpOnly, SameSite cookies
  - ✅ Validation des entrées et sanitization
  - ✅ Logs de sécurité pour audit
- **Interface Moderne** : Design responsive avec Tailwind CSS
- **Haute Disponibilité** : Déploiement Docker + Traefik pour HTTPS automatique

---

## 🏗️ Architecture

### Structure du Projet

```
taskflow-app/
├── includes/                    # Noyau de l'application
│   ├── auth.php                # Classe Auth (login, register, session)
│   ├── config.php              # Configuration (DB, sécurité, sessions)
│   ├── database.php            # Singleton PDO pour la BDD
│   ├── functions.php           # TaskManager (CRUD tâches)
│   └── security.php            # Fonctions de sécurité avancée
├── api/
│   └── tasks.php               # API RESTful JSON
├── databases/
│   └── schema.sql              # Schéma MySQL avec indexes
├── scripts/
│   └── security-audit.php      # Script d'audit de sécurité
├── tests/
│   ├── TaskTest.php            # Tests unitaires (PHPUnit)
│   └── SecurityTest.php        # Tests de sécurité
├── .github/workflows/          # Pipelines GitHub Actions
│   ├── ci.yml                  # Build, test, scan, push Docker
│   ├── security.yml            # SCA, SAST, composition audit
│   └── dast-zap.yml           # Tests de sécurité dynamiques
├── dashboard.php               # Interface utilisateur principale
├── login.php, register.php     # Pages d'authentification
├── logout.php                  # Déconnexion
├── Dockerfile                  # Image PHP 8.2 + Apache
├── docker-compose.yaml         # Orchestration Traefik
└── composer.json              # Dépendances PHP
```

### Schéma de Base de Données

```sql
users
  ├── id (PK)
  ├── username UNIQUE
  ├── email UNIQUE
  ├── password_hash (BCrypt)
  ├── failed_attempts (sécurité)
  ├── locked_until (rate limiting)
  ├── created_at
  └── last_login

tasks
  ├── id (PK)
  ├── user_id (FK)
  ├── title
  ├── description
  ├── status (pending, in_progress, completed)
  ├── priority (low, medium, high)
  ├── due_date
  ├── created_at
  ├── updated_at
  ├── completed_at
  └── [Indexes] user_status, priority, due_date

security_logs
  ├── id (PK)
  ├── user_id (FK nullable)
  ├── ip_address
  ├── action (LOGIN_FAILED, SESSION_HIJACK_ATTEMPT, etc.)
  ├── details (JSON)
  └── created_at
```

### Architecture Logique

```
Request HTTP
    ↓
[index.php] → Redirection login/dashboard
    ↓
[login.php/register.php] → Formulaires
    ↓
[includes/auth.php] → Classe Auth
    ├── sanitize_input()
    ├── validate_password_strength()
    ├── check_rate_limit()
    ├── password_hash/verify (BCrypt)
    └── log_security_event()
    ↓
[includes/database.php] → Singleton PDO
    ├── PDO Prepared Statements
    ├── Gestion des transactions
    └── Connexion pool
    ↓
[includes/security.php] → Fonctions sécurité
    ├── secure_session_start()
    ├── generate_csrf_token()
    ├── validate_csrf_token()
    ├── is_user_locked()
    └── Session hijacking detection
    ↓
[dashboard.php] → Affichage des tâches
    ├── Filtrage (statut, priorité)
    ├── Tri
    └── Pagination
    ↓
[api/tasks.php] → API RESTful (JSON)
    ├── GET /tasks → Récupérer
    ├── POST /tasks → Créer
    ├── PUT /tasks/:id → Mettre à jour
    └── DELETE /tasks/:id → Supprimer
```

---

## 🔐 Pipeline CI/CD & Sécurité

### Vue d'ensemble du Pipeline

```
1. PULL REQUEST OU PUSH
        ↓
┌───────────────────────────────────┐
│    ÉTAPE 1 : TESTS (ci.yml)       │
├───────────────────────────────────┤
│ • Setup PHP 8.3                   │
│ • composer install                │
│ • mysql setup (docker service)    │
│ • PHPUnit tests + coverage        │
│ • PHPStan analyse (level 5)       │
│ • phpcs (PSR-12)                  │
│ • phploc metrics                  │
└───────────────────────────────────┘
        ↓ [SUCCESS] → Merge possible
        ↓
┌───────────────────────────────────┐
│   ÉTAPE 2 : BUILD & SCAN (ci.yml) │
├───────────────────────────────────┤
│ • Docker build                    │
│ • Trivy scan (vulnérabilités)    │
│ • Push GHCR                       │
│ • Genère SBOM                     │
└───────────────────────────────────┘
        ↓
┌───────────────────────────────────┐
│  ÉTAPE 3 : SÉCURITÉ (security.yml)│
├───────────────────────────────────┤
│ • composer audit (SCA)            │
│ • Snyk scan (dépendances)         │
│ • SonarCloud (SAST - qualité)    │
│ • Dependabot (auto-updates)       │
└───────────────────────────────────┘
        ↓
┌───────────────────────────────────┐
│   ÉTAPE 4 : DAST (dast-zap.yml)   │
├───────────────────────────────────┤
│ • Déploiement conteneur           │
│ • OWASP ZAP scanning              │
│ • Rapport XSS, injections, etc.  │
└───────────────────────────────────┘
        ↓
    ✅ DÉPLOIEMENT AUTORISÉ
        ↓
    Traefik + Let's Encrypt (HTTPS)
```

### Outils de Sécurité Intégrés

| Outil | Type | Détection |
|-------|------|-----------|
| **Composer Audit** | SCA | Vulnérabilités connues dans les dépendances |
| **Snyk** | SCA | Packages abandonnés, issues de sécurité |
| **SonarCloud** | SAST | Bugs, code smells, hotspots de sécurité |
| **PHPStan** | SAST | Erreurs de type, logique dangereuse |
| **Trivy** | Container Scan | Vulnérabilités dans l'image Docker |
| **OWASP ZAP** | DAST | XSS, SQLi, CSRF, authentification |

### Protection au Niveau du Code

#### Authentification Sécurisée
```php
// ✅ Hachage BCrypt avec coût 12
$passwordHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);

// ✅ Vérification sûre
password_verify($password, $user['password_hash'])

// ✅ Rate limiting
if (!check_rate_limit('login', 5, 300)) { // 5 tentatives / 5 min
    return ['error' => 'Compte verrouillé'];
}
```

#### Protection XSS & Injections SQL
```php
// ✅ PDO Prepared Statements (protection SQLi)
$stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);

// ✅ Sanitization des entrées
$title = sanitize_input($title); // htmlspecialchars + trim

// ✅ Protection CSRF
validate_csrf_token($_POST['csrf_token']);
```

#### Gestion des Sessions Sécurisée
```php
// ✅ Cookies HttpOnly + SameSite
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

// ✅ Régénération périodique de l'ID
session_regenerate_id(true);

// ✅ Détection du vol de session
if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
    log_security_event(null, 'SESSION_HIJACK_ATTEMPT');
}
```

### Logs d'Audit
Tous les événements de sécurité sont enregistrés :
- Connexions réussies/échouées
- Tentatives de vol de session
- Accès non autorisés
- Modifications de tâches

---

## 🛠️ Prérequis Techniques

- **PHP 8.2+** avec extensions `pdo_mysql`, `mbstring`, `zip`
- **MySQL 5.7+** ou **MariaDB 10.5+**
- **Composer 2.0+** (pour dépendances)
- **Docker & Docker Compose** (optionnel, pour déploiement)
- **Git** (pour le workflow)

## 📥 Installation et Configuration

### 1. Récupérer le projet

```bash
git clone <your-repo-url> taskflow-app
cd taskflow-app
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer la Base de Données

#### Locale (Laragon/WAMP)
```php
// includes/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'taskflow');
define('DB_USER', 'root');
define('DB_PASS', '');
```

#### Créer la base et les tables
```bash
mysql -u root -p taskflow < databases/schema.sql
```

### 4. Configurer l'Application

```php
// includes/config.php
define('APP_ENV', 'development'); // production, development, testing
define('BASE_URL', 'http://localhost/taskflow-app/');
```

### 5. Lancer l'application

```bash
# Avec PHP built-in server
php -S localhost:8000

# OU via Laragon/Apache
# Accédez à http://taskflow-app.test
```

## 🚀 Utilisation

### Connexion Initiale

**Identifiants de test** (créés automatiquement par `schema.sql`) :
- **Utilisateur** : `testuser`
- **Email** : `test@example.com`
- **Mot de passe** : `Test123!`

### Fonctionnalités Principales

1. **Créer une tâche** : Cliquez sur "Nouvelle tâche", remplissez le formulaire
2. **Filtrer** : Par statut (En attente / En cours / Terminée) ou priorité
3. **Modifier** : Cliquez sur une tâche pour l'éditer
4. **Supprimer** : Confirmation avant suppression
5. **Tableau de bord** : Statistiques en temps réel

### API RESTful

#### Récupérer les tâches
```bash
curl -X GET http://localhost/taskflow-app/api/tasks.php \
  -H "Cookie: PHPSESSID=..."
```

#### Créer une tâche
```bash
curl -X POST http://localhost/taskflow-app/api/tasks.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "title=Ma tâche&priority=high&due_date=2026-02-15" \
  -H "Cookie: PHPSESSID=..."
```

#### Mettre à jour
```bash
curl -X PUT http://localhost/taskflow-app/api/tasks.php \
  -d "id=1&status=completed&_method=PUT"
```

#### Supprimer
```bash
curl -X DELETE http://localhost/taskflow-app/api/tasks.php?id=1
```

## 🧪 Développement et Qualité du Code

### Lancer les Tests

```bash
# Tests unitaires + analyse + lint + audit
composer ci

# Ou individuellement :
composer test                 # PHPUnit
composer analyse             # PHPStan
composer lint                # phpcs
composer security-check      # Audit custom
composer metrics              # phploc
```

### Workflow Git

Respectez la convention Conventional Commits :

```bash
git checkout -b feature/ma-fonctionnalite
git add .
git commit -m "feat(tasks): ajout du filtrage par date"
git push origin feature/ma-fonctionnalite
# → Pull Request vers dev
```

**Types de commits** :
- `feat()` : Nouvelle fonctionnalité
- `fix()` : Correction de bug
- `docs()` : Documentation
- `test()` : Tests
- `refactor()` : Refactorisation
- `chore()` : Maintenance

### Branches Git

```
main (production stable)
  └── dev (intégration)
      ├── feature/nom
      ├── fix/bug-name
      └── hotfix/urgence
```

---

## 🐳 Déploiement avec Docker

### Build local
```bash
docker build -t taskflow:latest .
```

### Lancer avec Docker Compose
```bash
docker-compose up -d
# Accessible à http://localhost
```

### Production (Traefik + HTTPS)
```bash
# Modifiez docker-compose.yaml avec votre domaine
docker-compose -f docker-compose.yaml up -d
# Traefik génère automatiquement un certificat Let's Encrypt
```

---

## 📊 Métriques & Performances

### Code Coverage
```bash
composer test-coverage
# Rapport HTML dans coverage/index.html
```

### Analyse des Complexités
```bash
composer metrics
# Affiche NOM, SLOC, CLOC, etc.
```

---

## 🤝 Contribution

1. **Forkez** le projet
2. **Créez une branche** : `git checkout -b feature/...`
3. **Committez** : `git commit -m "feat(...): ..."`
4. **Pushez** : `git push origin feature/...`
5. **Ouvrez une PR** vers `dev`

Les PR doivent passer tous les tests CI/CD pour être mergées.

---

## 📝 Licence

Ce projet est sous licence [MIT](LICENSE).

## 📧 Support

Pour signaler un bug ou proposer une amélioration, ouvrez une [issue](../../issues).

---

## 🎯 Roadmap

- [ ] Intégration avec un service d'emails
- [ ] Partage de tâches (collaboration)
- [ ] Notifications en temps réel (WebSockets)
- [ ] Export en PDF/CSV
- [ ] Mobile app (React Native)

---

**Développé avec ❤️ en PHP 8.2 | Sécurisé par défaut | Pipeline CI/CD automatisé**
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
