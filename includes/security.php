<?php
// Fonctions de sécurité avancées

/**
 * Démarrer une session sécurisée
 */
function secure_session_start() {
    // Configuration des paramètres de session
    ini_set('session.cookie_httponly', 1);
    
    // N'activer Secure que si on est en HTTPS
    $is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
    ini_set('session.cookie_secure', $is_secure ? 1 : 0);
    
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax'); // Change to Lax for better compatibility with redirects
    
    // Régénération d'ID de session
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
    
    session_start();
    
    // Régénérer l'ID de session périodiquement
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    // Vérifier l'adresse IP et l'agent utilisateur
    if (isset($_SESSION['ip_address']) && isset($_SESSION['user_agent'])) {
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR'] ||
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            // Suspicion de vol de session
            log_security_event(null, 'SESSION_HIJACK_ATTEMPT', [
                'expected_ip' => $_SESSION['ip_address'],
                'actual_ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ]);
            session_destroy();
            header('Location: /login.php?error=session_hijack');
            exit;
        }
    } else {
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }
}

/**
 * Générer un token CSRF
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token']) || time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_LIFETIME) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valider un token CSRF
 */
function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        log_security_event($_SESSION['user_id'] ?? null, 'CSRF_VALIDATION_FAILED', [
            'provided_token' => $token,
            'expected_token' => $_SESSION['csrf_token'] ?? 'none'
        ]);
        return false;
    }
    
    // Vérifier l'expiration
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_LIFETIME) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    return true;
}

/**
 * Sanitizer les entrées utilisateur
 */
function sanitize_input($data, $type = 'string') {
    if (is_array($data)) {
        return array_map(function($item) use ($type) {
            return sanitize_input($item, $type);
        }, $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    
    switch ($type) {
        case 'email':
            $data = filter_var($data, FILTER_SANITIZE_EMAIL);
            break;
        case 'int':
            $data = filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            break;
        case 'float':
            $data = filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            break;
        case 'url':
            $data = filter_var($data, FILTER_SANITIZE_URL);
            break;
        case 'string':
        default:
            $data = filter_var($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            break;
    }
    
    return $data;
}

/**
 * Échapper les sorties pour prévenir XSS
 */
function escape_output($data) {
    if (is_array($data)) {
        return array_map('escape_output', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Valider la force du mot de passe
 */
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une majuscule";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une minuscule";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un chiffre";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un caractère spécial";
    }
    
    return $errors;
}

/**
 * Logger les événements de sécurité
 */
function log_security_event($user_id, $action, $details = []) {
    global $pdo;

    $user_id = $user_id ?? null;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO security_logs (user_id, ip_address, action, details) 
            VALUES (:user_id, :ip_address, :action, :details)
        ");
        
        $stmt->execute([
            ':user_id' => $user_id,
            ':ip_address' => $_SERVER['REMOTE_ADDR'],
            ':action' => $action,
            ':details' => json_encode($details)
        ]);
    } catch (Exception $e) {
        // Log dans un fichier si la base de données échoue
        error_log("Security log failed: " . $e->getMessage());
    }
}

/**
 * Vérifier si un utilisateur est verrouillé
 */
function is_user_locked($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT locked_until FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user && $user['locked_until']) {
        if (strtotime($user['locked_until']) > time()) {
            return true;
        } else {
            // Déverrouiller l'utilisateur
            $stmt = $pdo->prepare("UPDATE users SET locked_until = NULL, failed_attempts = 0 WHERE id = ?");
            $stmt->execute([$user_id]);
            return false;
        }
    }
    
    return false;
}

/**
 * Rate limiting par IP
 */
function check_rate_limit($action, $limit = 10, $window = 60) {
    $key = 'rate_limit_' . $action . '_' . $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 1,
            'window_start' => time()
        ];
        return true;
    }
    
    $data = $_SESSION[$key];
    
    if (time() - $data['window_start'] > $window) {
        // Réinitialiser la fenêtre
        $_SESSION[$key] = [
            'count' => 1,
            'window_start' => time()
        ];
        return true;
    }
    
    if ($data['count'] >= $limit) {
        log_security_event(null, 'RATE_LIMIT_EXCEEDED', [
            'action' => $action,
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}
?>