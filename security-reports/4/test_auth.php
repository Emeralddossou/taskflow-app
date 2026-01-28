<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();

// Test registration
$username = 'testuser' . time();
$email = 'test' . time() . '@example.com';
$password = 'Test1234!'; // 5 criteria: length, upper, lower, digit, special

echo "Registering $username...\n";
$regResult = $auth->register($username, $email, $password);
if ($regResult['success']) {
    echo "Registration successful!\n";
} else {
    echo "Registration failed: " . $regResult['error'] . "\n";
}

// Test login
echo "Logging in with $username...\n";
$loginResult = $auth->login($username, $password);
if ($loginResult['success']) {
    echo "Login successful!\n";
} else {
    echo "Login failed: " . $loginResult['error'] . "\n";
}

// Test login with email
echo "Logging in with $email...\n";
$loginResultEmail = $auth->login($email, $password);
if ($loginResultEmail['success']) {
    echo "Login with email successful!\n";
} else {
    echo "Login with email failed: " . $loginResultEmail['error'] . "\n";
}
