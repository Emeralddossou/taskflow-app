<?php

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';

class Auth
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register($username, $email, $password)
    {
        // Validation des entrées
        $username = sanitize_input($username);
        $email = sanitize_input($email, 'email');
// Validation de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email invalide'];
        }

        // Validation du mot de passe
        $passwordErrors = validate_password_strength($password);
        if (!empty($passwordErrors)) {
            return ['success' => false, 'error' => implode(', ', $passwordErrors)];
        }

        // Vérifier si l'utilisateur existe déjà
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Nom d\'utilisateur ou email déjà utilisé'];
        }

        // Hasher le mot de passe
        $passwordHash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password_hash) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$username, $email, $passwordHash]);
        // Logger l'événement
                    log_security_event($this->pdo->lastInsertId(), 'USER_REGISTERED', [
                        'username' => $username,
                        'email' => $email
                    ]);
            return ['success' => true, 'user_id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            error_log("Registration failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur lors de l\'inscription'];
        }
    }

    /**
     * Connexion utilisateur
     */
    public function login($identifier, $password)
    {
        // Rate limiting
        if (!check_rate_limit('login', 5, 300)) {
            return ['success' => false, 'error' => 'Trop de tentatives. Veuillez réessayer plus tard.'];
        }

        // Sanitizer l'entrée
        $type = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'string';
        $identifier = sanitize_input($identifier, $type);
// Chercher l'utilisateur par username ou email
        $stmt = $this->pdo->prepare("
            SELECT id, username, email, password_hash, failed_attempts, locked_until 
            FROM users 
            WHERE username = ? OR email = ?
        ");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();
        if (!$user) {
            log_security_event(null, 'LOGIN_FAILED_UNKNOWN_USER', ['identifier' => $identifier]);
            return ['success' => false, 'error' => 'Identifiants incorrects'];
        }

        // Vérifier si le compte est verrouillé
        if (is_user_locked($user['id'])) {
            log_security_event($user['id'], 'LOGIN_FAILED_LOCKED_ACCOUNT');
            return ['success' => false, 'error' => 'Compte verrouillé. Veuillez réessayer plus tard.'];
        }

        // Vérifier le mot de passe
        if (password_verify($password, $user['password_hash'])) {
// Réinitialiser les tentatives échouées
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET failed_attempts = 0, locked_until = NULL, last_login = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
// Créer la session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
// Générer un nouveau token CSRF
            generate_csrf_token();
// Logger la connexion réussie
            log_security_event($user['id'], 'LOGIN_SUCCESS');
            return ['success' => true, 'user' => $user];
        } else {
        // Incrémenter les tentatives échouées
            $failedAttempts = $user['failed_attempts'] + 1;
            if ($failedAttempts >= MAX_LOGIN_ATTEMPTS) {
        // Verrouiller le compte
                $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
                $stmt = $this->pdo->prepare("
                    UPDATE users 
                    SET failed_attempts = ?, locked_until = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$failedAttempts, $lockUntil, $user['id']]);
                log_security_event($user['id'], 'ACCOUNT_LOCKED', [
                    'failed_attempts' => $failedAttempts,
                    'lock_until' => $lockUntil
                ]);
                return ['success' => false, 'error' => 'Compte verrouillé après trop de tentatives'];
            } else {
    // Mettre à jour le compteur
                $stmt = $this->pdo->prepare("
                    UPDATE users 
                    SET failed_attempts = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$failedAttempts, $user['id']]);
                log_security_event($user['id'], 'LOGIN_FAILED', [
                'failed_attempts' => $failedAttempts
                ]);
                return [
                'success' => false,
                'error' => 'Identifiants incorrects',
                'attempts_remaining' => MAX_LOGIN_ATTEMPTS - $failedAttempts
                ];
            }
        }
    }

    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function isLoggedIn()
    {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return false;
        }

        // Vérifier le timeout de session
        if (
            isset($_SESSION['login_time']) &&
            (time() - $_SESSION['login_time']) > SESSION_TIMEOUT
        ) {
            $this->logout();
            return false;
        }

        // Rafraîchir le temps de session
        $_SESSION['login_time'] = time();
        return true;
    }

    /**
     * Déconnexion
     */
    public function logout()
    {
        if (isset($_SESSION['user_id'])) {
            log_security_event($_SESSION['user_id'], 'LOGOUT');
        }

        // Détruire la session
        $_SESSION = array();
// Supprimer le cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }

    /**
     * Récupérer les infos utilisateur
     */
    public function getUserInfo($user_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT id, username, email, created_at, last_login 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }
}
