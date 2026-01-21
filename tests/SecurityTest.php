<?php
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/config.php';

class SecurityTest {
    
    public function testSanitizeInput() {
        $testCases = [
            ['input' => '<script>alert("xss")</script>', 'expected' => '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;'],
            ['input' => 'test@example.com', 'type' => 'email', 'expected' => 'test@example.com'],
            ['input' => '123abc', 'type' => 'int', 'expected' => '123'],
            ['input' => "test' OR '1'='1", 'expected' => 'test&#039; OR &#039;1&#039;=&#039;1']
        ];
        
        foreach ($testCases as $case) {
            $type = $case['type'] ?? 'string';
            $result = sanitize_input($case['input'], $type);
            
            if ($result === $case['expected']) {
                echo "✓ testSanitizeInput passed\n";
            } else {
                echo "✗ testSanitizeInput failed: expected '{$case['expected']}', got '$result'\n";
            }
        }
    }
    
    public function testPasswordStrength() {
        $testCases = [
            ['password' => 'Weak1', 'expected' => false],
            ['password' => 'StrongPass123!', 'expected' => true],
            ['password' => 'NoSpecialChar123', 'expected' => false],
            ['password' => 'Nouppercase123!', 'expected' => false]
        ];
        
        foreach ($testCases as $case) {
            $errors = validate_password_strength($case['password']);
            $result = empty($errors);
            
            if ($result === $case['expected']) {
                echo "✓ testPasswordStrength passed\n";
            } else {
                echo "✗ testPasswordStrength failed for '{$case['password']}'\n";
            }
        }
    }
    
    public function testCsrfToken() {
        // Simuler une session
        session_start();
        
        $token1 = generate_csrf_token();
        $token2 = generate_csrf_token();
        
        // Les tokens doivent être identiques (pas expirés)
        if ($token1 === $token2) {
            echo "✓ testCsrfToken generation passed\n";
        } else {
            echo "✗ testCsrfToken generation failed\n";
        }
        
        // Validation doit réussir
        if (validate_csrf_token($token1)) {
            echo "✓ testCsrfToken validation passed\n";
        } else {
            echo "✗ testCsrfToken validation failed\n";
        }
        
        // Fausse validation doit échouer
        // if (!validate_csrf_token('fake_token')) {
        //     echo "✓ testCsrfToken fake validation passed\n";
        // } else {
        //     echo "✗ testCsrfToken fake validation failed\n";
        // }
        
        // session_destroy();
    }
}

// Exécuter les tests
echo "Running Security Tests...\n";
echo "=======================\n";

$test = new SecurityTest();
$test->testSanitizeInput();
echo "\n";
$test->testPasswordStrength();
echo "\n";
$test->testCsrfToken();
?>