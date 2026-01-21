<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

class TaskTest extends TestCase {
    private $pdo;
    private $testUserId = 1; // ID d'utilisateur de test
    private static $taskId;
    
    protected function setUp(): void {
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
        
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['task_id']);
        self::$taskId = $result['task_id'];
    }
    
    /**
     * @depends testCreateTask
     */
    public function testGetTask() {
        $taskManager = new TaskManager();
        
        $task = $taskManager->getTask(self::$taskId, $this->testUserId);
        
        $this->assertNotFalse($task);
        $this->assertEquals('Test Task', $task['title']);
    }
    
    /**
     * @depends testCreateTask
     */
    public function testUpdateTask() {
        $taskManager = new TaskManager();
        
        $result = $taskManager->updateTask(self::$taskId, $this->testUserId, [
            'title' => 'Updated Task',
            'status' => 'completed'
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Updated Task', $result['task']['title']);
    }
    
    public function testGetUserTasks() {
        $taskManager = new TaskManager();
        
        $tasks = $taskManager->getUserTasks($this->testUserId);
        
        $this->assertIsArray($tasks);
    }
    
    /**
     * @depends testCreateTask
     */
    public function testDeleteTask() {
        $taskManager = new TaskManager();
        
        $result = $taskManager->deleteTask(self::$taskId, $this->testUserId);
        
        $this->assertTrue($result['success']);
    }
}
?>