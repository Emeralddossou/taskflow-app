<?php
require_once __DIR__ . '/database.php';

class TaskManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance();
    }
    
    /**
     * Créer une nouvelle tâche
     */
    public function createTask($user_id, $title, $description = '', $priority = 'medium', $due_date = null) {
        // Validation
        $title = sanitize_input($title);
        $description = sanitize_input($description);
        $priority = in_array($priority, ['low', 'medium', 'high']) ? $priority : 'medium';
        
        // Validation de la date
        if ($due_date) {
            $due_date = DateTime::createFromFormat('Y-m-d', $due_date);
            if (!$due_date) {
                return ['success' => false, 'error' => 'Date invalide'];
            }
            $due_date = $due_date->format('Y-m-d');
        }
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO tasks (user_id, title, description, priority, due_date) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$user_id, $title, $description, $priority, $due_date]);
            
            $taskId = $this->pdo->lastInsertId();
            
            return [
                'success' => true, 
                'task_id' => $taskId,
                'task' => $this->getTask($taskId, $user_id)
            ];
            
        } catch (PDOException $e) {
            error_log("Task creation failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur lors de la création de la tâche'];
        }
    }
    
    /**
     * Récupérer une tâche par ID
     */
    public function getTask($task_id, $user_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM tasks 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$task_id, $user_id]);
        return $stmt->fetch();
    }
    
    /**
     * Récupérer toutes les tâches d'un utilisateur avec filtres
     */
    public function getUserTasks($user_id, $filters = []) {
        $query = "SELECT * FROM tasks WHERE user_id = ?";
        $params = [$user_id];
        
        // Appliquer les filtres
        if (!empty($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $query .= " AND priority = ?";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['search'])) {
            $query .= " AND (title LIKE ? OR description LIKE ?)";
            $searchTerm = '%' . sanitize_input($filters['search']) . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Tri
        $orderBy = 'created_at DESC';
        if (!empty($filters['sort'])) {
            $allowedSorts = ['title', 'priority', 'due_date', 'created_at', 'updated_at'];
            $sortField = in_array($filters['sort'], $allowedSorts) ? $filters['sort'] : 'created_at';
            $orderBy = $sortField . ' ' . (!empty($filters['order']) && strtoupper($filters['order']) === 'ASC' ? 'ASC' : 'DESC');
        }
        
        $query .= " ORDER BY " . $orderBy;
        
        // Pagination
        if (!empty($filters['limit'])) {
            $query .= " LIMIT ?";
            $params[] = (int)$filters['limit'];
            
            if (!empty($filters['offset'])) {
                $query .= " OFFSET ?";
                $params[] = (int)$filters['offset'];
            }
        }
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Mettre à jour une tâche
     */
    public function updateTask($task_id, $user_id, $data) {
        // Vérifier que la tâche appartient à l'utilisateur
        $task = $this->getTask($task_id, $user_id);
        if (!$task) {
            return ['success' => false, 'error' => 'Tâche non trouvée'];
        }
        
        // Construire la requête dynamiquement
        $fields = [];
        $params = [];
        
        $allowedFields = ['title', 'description', 'status', 'priority', 'due_date'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = ?";
                
                if ($key === 'status' && $value === 'completed') {
                    $params[] = $value;
                    // Ajouter la date de complétion
                    $fields[] = "completed_at = NOW()";
                } elseif ($key === 'due_date' && $value) {
                    $date = DateTime::createFromFormat('Y-m-d', $value);
                    $params[] = $date ? $date->format('Y-m-d') : null;
                } else {
                    $params[] = sanitize_input($value);
                }
            }
        }
        
        if (empty($fields)) {
            return ['success' => false, 'error' => 'Aucune donnée à mettre à jour'];
        }
        
        $query = "UPDATE tasks SET " . implode(', ', $fields) . " WHERE id = ? AND user_id = ?";
        $params[] = $task_id;
        $params[] = $user_id;
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'task' => $this->getTask($task_id, $user_id)
            ];
            
        } catch (PDOException $e) {
            error_log("Task update failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur lors de la mise à jour'];
        }
    }
    
    /**
     * Supprimer une tâche
     */
    public function deleteTask($task_id, $user_id) {
        // Vérifier que la tâche appartient à l'utilisateur
        $task = $this->getTask($task_id, $user_id);
        if (!$task) {
            return ['success' => false, 'error' => 'Tâche non trouvée'];
        }
        
        try {
            $stmt = $this->pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$task_id, $user_id]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            error_log("Task deletion failed: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur lors de la suppression'];
        }
    }
    
    /**
     * Récupérer les statistiques des tâches
     */
    public function getTaskStats($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as `total`,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as `completed`,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as `in_progress`,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as `pending`,
                SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as `high_priority`,
                SUM(CASE WHEN due_date < CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as `overdue`
            FROM tasks 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        
        return $stmt->fetch();
    }
}
?>