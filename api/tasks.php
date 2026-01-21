<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Gérer les requêtes preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();

// Vérifier l'authentification
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$user_id = $_SESSION['user_id'];
$taskManager = new TaskManager();

// Déterminer la méthode HTTP (support spoofing _method)
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

switch ($method) {
    case 'GET':
        handleGet($taskManager, $user_id);
        break;
        
    case 'POST':
        handlePost($taskManager, $user_id);
        break;
        
    case 'PUT':
        handlePut($taskManager, $user_id);
        break;
        
    case 'DELETE':
        handleDelete($taskManager, $user_id);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
}

function handleGet($taskManager, $user_id) {
    // Récupérer une tâche spécifique
    if (isset($_GET['id'])) {
        $task_id = (int)$_GET['id'];
        $task = $taskManager->getTask($task_id, $user_id);
        
        if ($task) {
            echo json_encode(['success' => true, 'task' => $task]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Tâche non trouvée']);
        }
        return;
    }
    
    // Récupérer toutes les tâches avec filtres
    $filters = [
        'status' => $_GET['status'] ?? '',
        'priority' => $_GET['priority'] ?? '',
        'search' => $_GET['search'] ?? '',
        'sort' => $_GET['sort'] ?? 'created_at',
        'order' => $_GET['order'] ?? 'DESC'
    ];
    
    $tasks = $taskManager->getUserTasks($user_id, $filters);
    
    echo json_encode([
        'success' => true,
        'tasks' => $tasks,
        'count' => count($tasks)
    ]);
}

function handlePost($taskManager, $user_id) {
    // Valider le token CSRF
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
        return;
    }
    
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $priority = $_POST['priority'] ?? 'medium';
    $due_date = $_POST['due_date'] ?? null;
    $status = $_POST['status'] ?? 'pending';
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'error' => 'Le titre est obligatoire']);
        return;
    }
    
    $result = $taskManager->createTask($user_id, $title, $description, $priority, $due_date);
    
    if ($result['success']) {
        // Si un statut différent de pending est spécifié, le mettre à jour
        if ($status !== 'pending') {
            $taskManager->updateTask($result['task_id'], $user_id, ['status' => $status]);
            $result['task'] = $taskManager->getTask($result['task_id'], $user_id);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Tâche créée avec succès',
            'task' => $result['task']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
}

function handlePut($taskManager, $user_id) {
    // Parse le body pour PUT
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    // Si c'est une requête POST (éventuellement avec spoofing), on a déjà les données dans $_POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_PUT = $_POST;
    } else {
        // Vrai PUT (non supporté par FormData PHP nativement, nécessite php://input)
        parse_str(file_get_contents('php://input'), $_PUT);
    }
    
    // Valider le token CSRF
    $csrf_token = $_PUT['csrf_token'] ?? $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
        return;
    }
    
    $task_id = (int)($_PUT['task_id'] ?? 0);
    
    if (!$task_id) {
        echo json_encode(['success' => false, 'error' => 'ID de tâche manquant']);
        return;
    }
    
    // Préparer les données de mise à jour
    $updateData = [];
    
    if (isset($_PUT['title'])) $updateData['title'] = $_PUT['title'];
    if (isset($_PUT['description'])) $updateData['description'] = $_PUT['description'];
    if (isset($_PUT['priority'])) $updateData['priority'] = $_PUT['priority'];
    if (isset($_PUT['status'])) $updateData['status'] = $_PUT['status'];
    if (isset($_PUT['due_date'])) $updateData['due_date'] = $_PUT['due_date'];
    
    if (empty($updateData)) {
        echo json_encode(['success' => false, 'error' => 'Aucune donnée à mettre à jour']);
        return;
    }
    
    $result = $taskManager->updateTask($task_id, $user_id, $updateData);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Tâche mise à jour avec succès',
            'task' => $result['task']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
}

function handleDelete($taskManager, $user_id) {
    // Parse le body pour DELETE
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_DELETE = $_POST;
    } else {
        parse_str(file_get_contents('php://input'), $_DELETE);
    }
    
    // Valider le token CSRF
    $csrf_token = $_DELETE['csrf_token'] ?? $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
        return;
    }
    
    $task_id = (int)($_DELETE['task_id'] ?? 0);
    
    if (!$task_id) {
        echo json_encode(['success' => false, 'error' => 'ID de tâche manquant']);
        return;
    }
    
    $result = $taskManager->deleteTask($task_id, $user_id);
    
    if ($result['success']) {
        echo json_encode(['success' => true, 'message' => 'Tâche supprimée avec succès']);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
}
?>