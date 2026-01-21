<?php
// Configuration de l'application
define('APP_NAME', 'TaskFlow');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // production, development, testing

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'taskflow');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// Configuration de sécurité
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes en secondes
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('CSRF_TOKEN_LIFETIME', 3600); // 1 heure

// Configuration des chemins
define('BASE_URL', 'http://localhost/taskflow/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Niveaux de logs
define('LOG_LEVEL', APP_ENV === 'production' ? 2 : 4); // 1: Error, 2: Warning, 3: Info, 4: Debug

// Démarrer la session sécurisée
require_once __DIR__ . '/security.php';
secure_session_start();
?>