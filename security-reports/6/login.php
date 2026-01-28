<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
$error = '';
$success = '';

// Rediriger si déjà connecté
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Valider le token CSRF
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide. Veuillez réessayer.';
    } else {
        $identifier = $_POST['identifier'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $result = $auth->login($identifier, $password);
        
        if ($result['success']) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = $result['error'];
            
            // Afficher les tentatives restantes si disponible
            if (isset($result['attempts_remaining'])) {
                $error .= " (" . $result['attempts_remaining'] . " tentatives restantes)";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - TaskFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: transform 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="card rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full mb-4">
                    <i class="fas fa-tasks text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">TaskFlow</h1>
                <p class="text-gray-600 mt-2">Gérez vos tâches efficacement</p>
            </div>
            
            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                        <span class="text-red-700"><?php echo escape_output($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <span class="text-green-700"><?php echo escape_output($success); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="identifier">
                        <i class="fas fa-user mr-2"></i>Nom d'utilisateur ou Email
                    </label>
                    <input type="text" 
                           id="identifier" 
                           name="identifier" 
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                           placeholder="Entrez votre identifiant">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="password">
                        <i class="fas fa-lock mr-2"></i>Mot de passe
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition pr-10"
                               placeholder="Entrez votre mot de passe">
                        <button type="button" 
                                onclick="togglePasswordVisibility()"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" 
                        class="btn-primary w-full text-white font-semibold py-3 px-4 rounded-lg shadow-lg hover:shadow-xl transition duration-300">
                    <i class="fas fa-sign-in-alt mr-2"></i>Se connecter
                </button>
                
                <div class="mt-6 text-center">
                    <a href="register.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-user-plus mr-1"></i>Créer un compte
                    </a>
                </div>
            </form>
            
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Votre sécurité est notre priorité
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = passwordInput.nextElementSibling.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
        
        // Auto-submission protection
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            if (this.checkValidity()) {
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Connexion en cours...';
            }
        });
    </script>
</body>
</html>