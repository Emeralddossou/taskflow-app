<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();

// Rediriger vers le dashboard si déjà connecté
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Rediriger vers la page de login
header('Location: login.php');
exit;
?>