<?php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

class TaskTest {
    private $pdo;
    private $testUserId = 1; // ID d'utilisateur de test
    
    public function __construct() {
        $this->pdo = Database::getInstance();
    }
    
    public function testCreateTask() {
        $taskManager = new TaskManager();
        
        $result = $taskManager->createTask(
            $this->testUserId,
            'Test Task',
            'Test Description',
            'high',
            '2024-12-31'
        );
        
        if ($result['success']) {
            echo "✓ testCreateTask passed (ID: {$result['task_id']})\n";
            return $result['task_id'];
        } else {
            echo "✗ testCreateTask failed: {$result['error']}\n";
            return null;
        }
    }
    
    public function testGetTask($taskId) {
        $taskManager = new TaskManager();
        
        $task = $taskManager->getTask($taskId, $this->testUserId);
        
        if ($task && $task['title'] === 'Test Task') {
            echo "✓ testGetTask passed\n";
            return true;
        } else {
            echo "✗ testGetTask failed\n";
            return false;
        }
    }
    
    public function testUpdateTask($taskId) {
        $taskManager = new TaskManager();
        
        $result = $taskManager->updateTask($taskId, $this->testUserId, [
            'title' => 'Updated Task',
            'status' => 'completed'
        ]);
        
        if ($result['success'] && $result['task']['title'] === 'Updated Task') {
            echo "✓ testUpdateTask passed\n";
            return true;
        } else {
            echo "✗ testUpdateTask failed: {$result['error']}\n";
            return false;
        }
    }
    
    public function testGetUserTasks() {
        $taskManager = new TaskManager();
        
        $tasks = $taskManager->getUserTasks($this->testUserId);
        
        if (is_array($tasks)) {
            echo "✓ testGetUserTasks passed (" . count($tasks) . " tasks)\n";
            return true;
        } else {
            echo "✗ testGetUserTasks failed\n";
            return false;
        }
    }
    
    public function testDeleteTask($taskId) {
        $taskManager = new TaskManager();
        
        $result = $taskManager->deleteTask($taskId, $this->testUserId);
        
        if ($result['success']) {
            echo "✓ testDeleteTask passed\n";
            return true;
        } else {
            echo "✗ testDeleteTask failed: {$result['error']}\n";
            return false;
        }
    }
    
    public function runAllTests() {
        echo "Running Task Tests...\n";
        echo "====================\n";
        
        // Test 1: Création
        $taskId = $this->testCreateTask();
        
        if ($taskId) {
            // Test 2: Lecture
            $this->testGetTask($taskId);
            
            // Test 3: Mise à jour
            $this->testUpdateTask($taskId);
            
            // Test 4: Liste des tâches
            $this->testGetUserTasks();
            
            // Test 5: Suppression
            $this->testDeleteTask($taskId);
        }
        
        echo "\nAll tests completed!\n";
    }
}

// Exécuter les tests
if (php_sapi_name() === 'cli') {
    $test = new TaskTest();
    $test->runAllTests();
} else {
    echo "Tests must be run from command line.\n";
}
?>