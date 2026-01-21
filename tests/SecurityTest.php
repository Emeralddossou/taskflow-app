<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/security.php';

class SecurityTest extends TestCase {
    
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
            $this->assertEquals($case['expected'], $result, "Failed for input: " . $case['input']);
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
            $this->assertEquals($case['expected'], $result, "Failed for password: " . $case['password']);
        }
    }
    
    public function testCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token1 = generate_csrf_token();
        $token2 = generate_csrf_token();
        
        // Les tokens doivent être identiques (pas expirés)
        $this->assertEquals($token1, $token2);
        
        // Validation doit réussir
        $this->assertTrue(validate_csrf_token($token1));
        
        // Fausse validation doit échouer
        $this->assertFalse(validate_csrf_token('fake_token'));
    }
}
?>