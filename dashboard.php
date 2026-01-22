<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();

// Rediriger si non connecté
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Initialiser le gestionnaire de tâches
$taskManager = new TaskManager();

// Récupérer les statistiques
$stats = $taskManager->getTaskStats($user_id);

// Récupérer les tâches avec filtres
$filters = [
    'status' => $_GET['status'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'search' => $_GET['search'] ?? '',
    'sort' => $_GET['sort'] ?? 'created_at',
    'order' => $_GET['order'] ?? 'DESC'
];

$tasks = $taskManager->getUserTasks($user_id, $filters);
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - TaskFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --priority-low: #10b981;
            --priority-medium: #f59e0b;
            --priority-high: #ef4444;
        }
        
        .task-card {
            transition: all 0.3s ease;
            border-left-width: 4px;
        }
        
        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .task-card.low { border-left-color: var(--priority-low); }
        .task-card.medium { border-left-color: var(--priority-medium); }
        .task-card.high { border-left-color: var(--priority-high); }
        
        .priority-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .priority-low { background-color: rgba(16, 185, 129, 0.1); color: var(--priority-low); }
        .priority-medium { background-color: rgba(245, 158, 11, 0.1); color: var(--priority-medium); }
        .priority-high { background-color: rgba(239, 68, 68, 0.1); color: var(--priority-high); }
        
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending { background-color: rgba(156, 163, 175, 0.1); color: #6b7280; }
        .status-in_progress { background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .status-completed { background-color: rgba(16, 185, 129, 0.1); color: var(--priority-low); }
        
        .overdue {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .loading-spinner {
            display: none;
        }
        
        .loading .loading-spinner {
            display: block;
        }
        
        .loading .content {
            opacity: 0.5;
        }

        .notification {
            transform: translateX(120%);
            transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-tasks text-blue-600 text-2xl mr-3"></i>
                        <span class="text-xl font-bold text-gray-800">TaskFlow</span>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="dashboard.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-home mr-2"></i>Tableau de bord
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900"><?php echo escape_output($username); ?></p>
                            <p class="text-xs text-gray-500">Connecté</p>
                        </div>
                        <div class="relative">
                            <button id="user-menu-button" class="flex items-center space-x-2 bg-gray-100 rounded-full p-2 hover:bg-gray-200 transition">
                                <i class="fas fa-user-circle text-gray-600 text-xl"></i>
                                <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                            </button>
                            <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                                <!-- <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i>Mon profil
                                </a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-cog mr-2"></i>Paramètres
                                </a> -->
                                <div class="border-t border-gray-100"></div>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- En-tête et statistiques -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Bonjour, <?php echo escape_output($username); ?> 👋</h1>
            <p class="text-gray-600">Gérez vos tâches et restez productif</p>
            
            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                            <i class="fas fa-tasks text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total des tâches</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Terminées</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['completed'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                            <i class="fas fa-spinner text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">En cours</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['in_progress'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-red-100 rounded-lg p-3">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">En retard</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['overdue'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres et actions -->
        <div class="bg-white rounded-lg shadow mb-6 p-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between space-y-4 md:space-y-0">
                <div class="flex flex-wrap items-center space-x-4">
                    <button id="new-task-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition flex items-center">
                        <i class="fas fa-plus mr-2"></i>Nouvelle tâche
                    </button>
                    
                    <div class="relative">
                        <select id="status-filter" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Tous les statuts</option>
                            <option value="pending" <?php echo ($filters['status'] === 'pending') ? 'selected' : ''; ?>>En attente</option>
                            <option value="in_progress" <?php echo ($filters['status'] === 'in_progress') ? 'selected' : ''; ?>>En cours</option>
                            <option value="completed" <?php echo ($filters['status'] === 'completed') ? 'selected' : ''; ?>>Terminée</option>
                        </select>
                    </div>
                    
                    <div class="relative">
                        <select id="priority-filter" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Toutes les priorités</option>
                            <option value="low" <?php echo ($filters['priority'] === 'low') ? 'selected' : ''; ?>>Basse</option>
                            <option value="medium" <?php echo ($filters['priority'] === 'medium') ? 'selected' : ''; ?>>Moyenne</option>
                            <option value="high" <?php echo ($filters['priority'] === 'high') ? 'selected' : ''; ?>>Haute</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text" 
                               id="search-input" 
                               placeholder="Rechercher une tâche..."
                               value="<?php echo escape_output($filters['search']); ?>"
                               class="border border-gray-300 rounded-lg pl-10 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 w-full md:w-64">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                    
                    <button id="clear-filters" class="text-gray-600 hover:text-gray-900 font-medium py-2 px-4 rounded-lg transition">
                        <i class="fas fa-times mr-2"></i>Effacer
                    </button>
                </div>
            </div>
        </div>

        <!-- Liste des tâches -->
        <div id="tasks-container" class="space-y-4">
            <?php if (empty($tasks)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-clipboard-list text-gray-300 text-6xl mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">Aucune tâche trouvée</h3>
                    <p class="text-gray-600 mb-6">Commencez par créer votre première tâche !</p>
                    <button id="empty-new-task-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition inline-flex items-center">
                        <i class="fas fa-plus mr-2"></i>Créer une tâche
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <?php
                    $isOverdue = $task['due_date'] && strtotime($task['due_date']) < time() && $task['status'] !== 'completed';
                    ?>
                    <div class="task-card bg-white rounded-lg shadow p-6 <?php echo $task['priority']; ?> <?php echo $isOverdue ? 'overdue' : ''; ?>" data-task-id="<?php echo $task['id']; ?>">
                        <div class="flex flex-col md:flex-row md:items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-start space-x-4">
                                    <div class="flex-shrink-0">
                                        <div class="relative">
                                            <input type="checkbox" 
                                                   class="task-checkbox h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                                   data-task-id="<?php echo $task['id']; ?>"
                                                   <?php echo $task['status'] === 'completed' ? 'checked' : ''; ?>>
                                            <?php if ($isOverdue): ?>
                                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full"></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <h3 class="text-lg font-medium text-gray-900 <?php echo $task['status'] === 'completed' ? 'line-through text-gray-500' : ''; ?>">
                                                <?php echo escape_output($task['title']); ?>
                                            </h3>
                                            <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                                                <?php echo ucfirst($task['priority']); ?>
                                            </span>
                                            <span class="status-badge status-<?php echo $task['status']; ?>">
                                                <?php 
                                                $statusLabels = [
                                                    'pending' => 'En attente',
                                                    'in_progress' => 'En cours',
                                                    'completed' => 'Terminée'
                                                ];
                                                echo $statusLabels[$task['status']];
                                                ?>
                                            </span>
                                        </div>
                                        
                                        <?php if (!empty($task['description'])): ?>
                                            <p class="text-gray-600 mb-4 <?php echo $task['status'] === 'completed' ? 'line-through' : ''; ?>">
                                                <?php echo nl2br(escape_output($task['description'])); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="flex flex-wrap items-center space-x-4 text-sm text-gray-500">
                                            <?php if ($task['due_date']): ?>
                                                <div class="flex items-center">
                                                    <i class="fas fa-calendar-alt mr-2"></i>
                                                    <span class="<?php echo $isOverdue ? 'text-red-600 font-medium' : ''; ?>">
                                                        Échéance: <?php echo date('d/m/Y', strtotime($task['due_date'])); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="flex items-center">
                                                <i class="fas fa-clock mr-2"></i>
                                                <span>Créée le: <?php echo date('d/m/Y', strtotime($task['created_at'])); ?></span>
                                            </div>
                                            
                                            <?php if ($task['completed_at']): ?>
                                                <div class="flex items-center">
                                                    <i class="fas fa-check mr-2"></i>
                                                    <span>Terminée le: <?php echo date('d/m/Y', strtotime($task['completed_at'])); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-2 mt-4 md:mt-0">
                                <button class="task-edit-btn text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition"
                                        data-task-id="<?php echo $task['id']; ?>"
                                        title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <button class="task-delete-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition"
                                        data-task-id="<?php echo $task['id']; ?>"
                                        title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                                
                                <?php if ($task['status'] === 'pending'): ?>
                                    <button class="task-start-btn text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 transition"
                                            data-task-id="<?php echo $task['id']; ?>"
                                            title="Commencer">
                                        <i class="fas fa-play"></i>
                                    </button>
                                <?php elseif ($task['status'] === 'in_progress'): ?>
                                    <button class="task-complete-btn text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 transition"
                                            data-task-id="<?php echo $task['id']; ?>"
                                            title="Terminer">
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de création/modification -->
    <div id="task-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-lg bg-white">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modal-title" class="text-xl font-bold text-gray-900">Nouvelle tâche</h3>
                <button id="close-modal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form id="task-form">
                <input type="hidden" id="task-id" name="task_id" value="">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="space-y-6">
                    <div>
                        <label for="task-title" class="block text-sm font-medium text-gray-700 mb-2">
                            Titre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="task-title" 
                               name="title"
                               required
                               maxlength="255"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="Que devez-vous faire ?">
                        <p class="text-xs text-gray-500 mt-1">Maximum 255 caractères</p>
                    </div>
                    
                    <div>
                        <label for="task-description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <textarea id="task-description" 
                                  name="description"
                                  rows="4"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                  placeholder="Détails de la tâche..."></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="task-priority" class="block text-sm font-medium text-gray-700 mb-2">
                                Priorité
                            </label>
                            <select id="task-priority" 
                                    name="priority"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                <option value="low">Basse</option>
                                <option value="medium" selected>Moyenne</option>
                                <option value="high">Haute</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="task-due-date" class="block text-sm font-medium text-gray-700 mb-2">
                                Date d'échéance
                            </label>
                            <input type="text" 
                                   id="task-due-date" 
                                   name="due_date"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                   placeholder="Sélectionner une date">
                        </div>
                    </div>
                    
                    <div>
                        <label for="task-status" class="block text-sm font-medium text-gray-700 mb-2">
                            Statut
                        </label>
                        <select id="task-status" 
                                name="status"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <option value="pending">En attente</option>
                            <option value="in_progress">En cours</option>
                            <option value="completed">Terminée</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                    <button type="button" 
                            id="cancel-task"
                            class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Annuler
                    </button>
                    <button type="submit" 
                            id="save-task"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center h-full">
            <div class="bg-white rounded-lg p-8 shadow-lg">
                <div class="flex items-center space-x-4">
                    <i class="fas fa-spinner fa-spin text-blue-600 text-2xl"></i>
                    <span class="text-lg font-medium text-gray-900">Chargement...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <script>
        // Configuration Flatpickr
        flatpickr("#task-due-date", {
            locale: "fr",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            minDate: "today",
            disableMobile: true
        });

        // Variables globales
        const API_BASE = 'api/tasks.php';
        let currentFilters = {
            status: '<?php echo $filters['status']; ?>',
            priority: '<?php echo $filters['priority']; ?>',
            search: '<?php echo $filters['search']; ?>',
            sort: '<?php echo $filters['sort']; ?>',
            order: '<?php echo $filters['order']; ?>'
        };

        // Gestion du menu utilisateur
        document.getElementById('user-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        });

        // Fermer le menu utilisateur en cliquant ailleurs
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('user-menu');
            const button = document.getElementById('user-menu-button');
            
            if (!menu.contains(event.target) && !button.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });

        // Gestion des filtres
        document.getElementById('status-filter').addEventListener('change', function() {
            currentFilters.status = this.value;
            loadTasks();
        });

        document.getElementById('priority-filter').addEventListener('change', function() {
            currentFilters.priority = this.value;
            loadTasks();
        });

        document.getElementById('search-input').addEventListener('input', debounce(function() {
            currentFilters.search = this.value;
            loadTasks();
        }, 500));

        document.getElementById('clear-filters').addEventListener('click', function() {
            document.getElementById('status-filter').value = '';
            document.getElementById('priority-filter').value = '';
            document.getElementById('search-input').value = '';
            
            currentFilters = {
                status: '',
                priority: '',
                search: '',
                sort: 'created_at',
                order: 'DESC'
            };
            
            loadTasks();
        });

        // Gestion du modal
        document.getElementById('new-task-btn').addEventListener('click', () => openTaskModal());
        
        const emptyBtn = document.getElementById('empty-new-task-btn');
        if (emptyBtn) {
            emptyBtn.addEventListener('click', () => openTaskModal());
        }

        document.getElementById('close-modal').addEventListener('click', closeTaskModal);
        document.getElementById('cancel-task').addEventListener('click', closeTaskModal);

        // Fermer le modal en cliquant en dehors
        document.getElementById('task-modal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeTaskModal();
            }
        });

        // Gestion du formulaire de tâche
        document.getElementById('task-form').addEventListener('submit', function(e) {
            e.preventDefault();
            saveTask();
        });

        // Déléguation des événements pour les boutons de tâche
        document.addEventListener('click', function(event) {
            // Édition de tâche
            if (event.target.closest('.task-edit-btn')) {
                const taskId = event.target.closest('.task-edit-btn').dataset.taskId;
                editTask(taskId);
            }
            
            // Suppression de tâche
            if (event.target.closest('.task-delete-btn')) {
                const taskId = event.target.closest('.task-delete-btn').dataset.taskId;
                deleteTask(taskId);
            }
            
            // Commencer une tâche
            if (event.target.closest('.task-start-btn')) {
                const taskId = event.target.closest('.task-start-btn').dataset.taskId;
                updateTaskStatus(taskId, 'in_progress');
            }

            // Terminer une tâche
            if (event.target.closest('.task-complete-btn')) {
                const taskId = event.target.closest('.task-complete-btn').dataset.taskId;
                updateTaskStatus(taskId, 'completed');
            }
            
            // Checkbox de tâche
            if (event.target.closest('.task-checkbox')) {
                const taskId = event.target.closest('.task-checkbox').dataset.taskId;
                const isChecked = event.target.checked;
                updateTaskStatus(taskId, isChecked ? 'completed' : 'pending');
            }
        });

        // Fonctions utilitaires
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function showLoading() {
            document.getElementById('loading-overlay').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loading-overlay').classList.add('hidden');
        }

        function showNotification(message, type = 'success') {
            // Supprimer les notifications existantes
            const existingNotif = document.querySelector('.notification');
            if (existingNotif) existingNotif.remove();

            // Créer la notification
            const notification = document.createElement('div');
            notification.className = `notification fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transition-all duration-300 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } text-white`;
            
            notification.innerHTML = `
                <div class="flex items-center space-x-3">
                    <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animation d'entrée
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 10);
            
            // Supprimer après 5 secondes
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        // Fonctions API
        async function loadTasks() {
            showLoading();
            
            try {
                const params = new URLSearchParams(currentFilters);
                const response = await fetch(`${API_BASE}?${params.toString()}`);
                const data = await response.json();
                
                if (data.success) {
                    updateTasksList(data.tasks);
                } else {
                    showNotification(data.error || 'Erreur lors du chargement des tâches', 'error');
                }
            } catch (error) {
                console.error('Error loading tasks:', error);
                console.log('Detailed error:', {
                    message: error.message,
                    stack: error.stack,
                    name: error.name
                });
                showNotification('Erreur de connexion au serveur', 'error');
            } finally {
                hideLoading();
            }
        }

        async function saveTask() {
            const form = document.getElementById('task-form');
            const formData = new FormData(form);
            
            // Valider le titre
            const title = formData.get('title').trim();
            if (!title) {
                showNotification('Le titre est obligatoire', 'error');
                return;
            }
            
            showLoading();
            
            try {
                if (formData.get('task_id')) {
                    formData.append('_method', 'PUT');
                }
                
                const response = await fetch(API_BASE, {
                    method: 'POST',
                    body: formData
                });
                
                const responseText = await response.text();
                let data;
                
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    console.error('Server response is not JSON:', responseText);
                    throw new Error('Server returned invalid JSON');
                }
                
                if (data.success) {
                    showNotification(data.message || 'Tâche enregistrée avec succès');
                    closeTaskModal();
                    loadTasks();
                } else {
                    showNotification(data.error || 'Erreur lors de l\'enregistrement', 'error');
                }
            } catch (error) {
                console.error('Error saving task:', error);
                // Log plus d'infos pour le debug
                if (error instanceof TypeError) {
                    console.log('TypeError: Possible CSRF/CORS issue or network failure');
                }
                
                // Show more detailed error if possible
                if (error.message === 'Server returned invalid JSON') {
                    showNotification('Erreur serveur: Réponse invalide', 'error');
                } else {
                    showNotification('Erreur de connexion au serveur: ' + error.message, 'error');
                }
            } finally {
                hideLoading();
            }
        }

        async function editTask(taskId) {
            showLoading();
            
            try {
                const response = await fetch(`${API_BASE}?id=${taskId}`);
                const data = await response.json();
                
                if (data.success && data.task) {
                    openTaskModal(data.task);
                } else {
                    showNotification(data.error || 'Tâche non trouvée', 'error');
                }
            } catch (error) {
                console.error('Error fetching task:', error);
                showNotification('Erreur de connexion au serveur', 'error');
            } finally {
                hideLoading();
            }
        }

        async function updateTaskStatus(taskId, status) {
            showLoading();
            
            try {
                const formData = new FormData();
                formData.append('task_id', taskId);
                formData.append('status', status);
                formData.append('_method', 'PUT'); // Spoofing pour éviter les soucis avec PUT direct
                formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
                
                const response = await fetch(API_BASE, {
                    method: 'POST', // On utilise POST avec spoofing
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Statut mis à jour');
                    loadTasks();
                } else {
                    showNotification(data.error || 'Erreur lors de la mise à jour', 'error');
                }
            } catch (error) {
                console.error('Error updating task:', error);
                showNotification('Erreur de connexion au serveur', 'error');
            } finally {
                hideLoading();
            }
        }

        async function deleteTask(taskId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
                return;
            }
            
            showLoading();
            
            try {
                const formData = new FormData();
                formData.append('task_id', taskId);
                formData.append('_method', 'DELETE'); // Spoofing
                formData.append('csrf_token', '<?php echo generate_csrf_token(); ?>');
                
                const response = await fetch(API_BASE, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Tâche supprimée avec succès');
                    loadTasks();
                } else {
                    showNotification(data.error || 'Erreur lors de la suppression', 'error');
                }
            } catch (error) {
                console.error('Error deleting task:', error);
                showNotification('Erreur de connexion au serveur', 'error');
            } finally {
                hideLoading();
            }
        }

        function openTaskModal(task = null) {
            const modal = document.getElementById('task-modal');
            const form = document.getElementById('task-form');
            
            // Réinitialiser le formulaire
            form.reset();
            
            if (task) {
                // Mode édition
                document.getElementById('modal-title').textContent = 'Modifier la tâche';
                document.getElementById('task-id').value = task.id;
                document.getElementById('task-title').value = task.title;
                document.getElementById('task-description').value = task.description || '';
                document.getElementById('task-priority').value = task.priority;
                document.getElementById('task-status').value = task.status;
                
                if (task.due_date) {
                    document.getElementById('task-due-date').value = task.due_date;
                }
            } else {
                // Mode création
                document.getElementById('modal-title').textContent = 'Nouvelle tâche';
                document.getElementById('task-id').value = '';
                document.getElementById('task-status').value = 'pending';
                // Set default date to today
                if (document.getElementById('task-due-date')._flatpickr) {
                    document.getElementById('task-due-date')._flatpickr.setDate(new Date());
                }
            }
            
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Focus sur le titre
            setTimeout(() => {
                document.getElementById('task-title').focus();
            }, 100);
        }

        function closeTaskModal() {
            document.getElementById('task-modal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function updateTasksList(tasks) {
            const container = document.getElementById('tasks-container');
            
            if (tasks.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-clipboard-list text-gray-300 text-6xl mb-4"></i>
                        <h3 class="text-xl font-medium text-gray-900 mb-2">Aucune tâche trouvée</h3>
                        <p class="text-gray-600 mb-6">Essayez de modifier vos filtres de recherche</p>
                        <button id="empty-new-task-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition inline-flex items-center">
                            <i class="fas fa-plus mr-2"></i>Créer une tâche
                        </button>
                    </div>
                `;
                
                document.getElementById('empty-new-task-btn').addEventListener('click', () => openTaskModal());
                return;
            }
            
            container.innerHTML = tasks.map(task => {
                const isOverdue = task.due_date && new Date(task.due_date) < new Date() && task.status !== 'completed';
                
                return `
                    <div class="task-card bg-white rounded-lg shadow p-6 ${task.priority} ${isOverdue ? 'overdue' : ''}" data-task-id="${task.id}">
                        <div class="flex flex-col md:flex-row md:items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-start space-x-4">
                                    <div class="flex-shrink-0">
                                        <div class="relative">
                                            <input type="checkbox" 
                                                   class="task-checkbox h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                                   data-task-id="${task.id}"
                                                   ${task.status === 'completed' ? 'checked' : ''}>
                                            ${isOverdue ? '<div class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full"></div>' : ''}
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <h3 class="text-lg font-medium text-gray-900 ${task.status === 'completed' ? 'line-through text-gray-500' : ''}">
                                                ${escapeHtml(task.title)}
                                            </h3>
                                            <span class="priority-badge priority-${task.priority}">
                                                ${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}
                                            </span>
                                            <span class="status-badge status-${task.status}">
                                                ${getStatusLabel(task.status)}
                                            </span>
                                        </div>
                                        
                                        ${task.description ? `
                                            <p class="text-gray-600 mb-4 ${task.status === 'completed' ? 'line-through' : ''}">
                                                ${escapeHtml(task.description).replace(/\n/g, '<br>')}
                                            </p>
                                        ` : ''}
                                        
                                        <div class="flex flex-wrap items-center space-x-4 text-sm text-gray-500">
                                            ${task.due_date ? `
                                                <div class="flex items-center">
                                                    <i class="fas fa-calendar-alt mr-2"></i>
                                                    <span class="${isOverdue ? 'text-red-600 font-medium' : ''}">
                                                        Échéance: ${formatDate(task.due_date)}
                                                    </span>
                                                </div>
                                            ` : ''}
                                            
                                            <div class="flex items-center">
                                                <i class="fas fa-clock mr-2"></i>
                                                <span>Créée le: ${formatDate(task.created_at)}</span>
                                            </div>
                                            
                                            ${task.completed_at ? `
                                                <div class="flex items-center">
                                                    <i class="fas fa-check mr-2"></i>
                                                    <span>Terminée le: ${formatDate(task.completed_at)}</span>
                                                </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-2 mt-4 md:mt-0">
                                <button class="task-edit-btn text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition"
                                        data-task-id="${task.id}"
                                        title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <button class="task-delete-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition"
                                        data-task-id="${task.id}"
                                        title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                                
                                ${task.status === 'pending' ? `
                                    <button class="task-start-btn text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 transition"
                                            data-task-id="${task.id}"
                                            title="Commencer">
                                        <i class="fas fa-play"></i>
                                    </button>
                                ` : ''}
                                
                                ${task.status === 'in_progress' ? `
                                    <button class="task-complete-btn text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 transition"
                                            data-task-id="${task.id}"
                                            title="Terminer">
                                        <i class="fas fa-check"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Fonctions utilitaires côté client
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR');
        }

        function getStatusLabel(status) {
            const labels = {
                'pending': 'En attente',
                'in_progress': 'En cours',
                'completed': 'Terminée'
            };
            return labels[status] || status;
        }

        // Initialiser
        document.addEventListener('DOMContentLoaded', function() {
            // Configurer les filtres initiaux
            document.getElementById('status-filter').value = currentFilters.status;
            document.getElementById('priority-filter').value = currentFilters.priority;
            document.getElementById('search-input').value = currentFilters.search;
        });
    </script>
</body>
</html>