<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $pdo = Database::getInstance();
    echo "Connection successful!\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(", ", $tables) . "\n";
    
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll();
    echo "Users count: " . count($users) . "\n";
    foreach ($users as $user) {
        echo "- {$user['username']} ({$user['email']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
