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
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Tous les champs sont obligatoires';
        } elseif ($password !== $confirm_password) {
            $error = 'Les mots de passe ne correspondent pas';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $error = 'Le nom d\'utilisateur doit faire entre 3 et 50 caractères';
        } else {
            $result = $auth->register($username, $email, $password);
            
            if ($result['success']) {
                // Connexion automatique après inscription
                $loginResult = $auth->login($email, $password);
                if ($loginResult['success']) {
                    header('Location: dashboard.php?registered=1');
                    exit;
                } else {
                    $success = 'Compte créé avec succès ! Veuillez vous connecter.';
                }
                // Vider le formulaire
                $_POST = [];
            } else {
                $error = $result['error'];
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
    <title>Inscription - TaskFlow</title>
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
        
        .password-strength-meter {
            height: 4px;
            border-radius: 2px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        
        .strength-0 { background-color: #ef4444; width: 20%; }
        .strength-1 { background-color: #f97316; width: 40%; }
        .strength-2 { background-color: #eab308; width: 60%; }
        .strength-3 { background-color: #22c55e; width: 80%; }
        .strength-4 { background-color: #16a34a; width: 100%; }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="card rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-500 to-blue-600 rounded-full mb-4">
                    <i class="fas fa-user-plus text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Créer un compte</h1>
                <p class="text-gray-600 mt-2">Rejoignez TaskFlow dès maintenant</p>
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
            
            <form method="POST" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="username">
                        <i class="fas fa-user mr-2"></i>Nom d'utilisateur
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?php echo escape_output($_POST['username'] ?? ''); ?>"
                           required
                           minlength="3"
                           maxlength="50"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                           placeholder="Choisissez un nom d'utilisateur">
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="email">
                        <i class="fas fa-envelope mr-2"></i>Adresse email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo escape_output($_POST['email'] ?? ''); ?>"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                           placeholder="votre@email.com">
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
                               minlength="8"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition pr-10"
                               placeholder="Créez un mot de passe sécurisé"
                               oninput="checkPasswordStrength(this.value)">
                        <button type="button" 
                                onclick="togglePasswordVisibility('password')"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="password-strength" class="password-strength-meter strength-0"></div>
                    <div id="password-requirements" class="text-xs text-gray-500 mt-2 space-y-1">
                        <div id="req-length"><i class="fas fa-times text-red-500 mr-1"></i> 8 caractères minimum</div>
                        <div id="req-uppercase"><i class="fas fa-times text-red-500 mr-1"></i> Une majuscule</div>
                        <div id="req-lowercase"><i class="fas fa-times text-red-500 mr-1"></i> Une minuscule</div>
                        <div id="req-number"><i class="fas fa-times text-red-500 mr-1"></i> Un chiffre</div>
                        <div id="req-special"><i class="fas fa-times text-red-500 mr-1"></i> Un caractère spécial</div>
                    </div>
                </div>
                
                <div class="mb-8">
                    <label class="block text-gray-700 text-sm font-medium mb-2" for="confirm_password">
                        <i class="fas fa-lock mr-2"></i>Confirmer le mot de passe
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition pr-10"
                               placeholder="Confirmez votre mot de passe">
                        <button type="button" 
                                onclick="togglePasswordVisibility('confirm_password')"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="password-match" class="text-xs mt-2 hidden">
                        <i class="fas fa-check-circle text-green-500 mr-1"></i>
                        <span class="text-green-600">Les mots de passe correspondent</span>
                    </div>
                </div>
                
                <button type="submit" 
                        id="submitBtn"
                        class="bg-gradient-to-r from-green-500 to-blue-600 w-full text-white font-semibold py-3 px-4 rounded-lg shadow-lg hover:shadow-xl transition duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-user-plus mr-2"></i>Créer mon compte
                </button>
                
                <div class="mt-6 text-center">
                    <a href="login.php" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fas fa-sign-in-alt mr-1"></i>Déjà un compte ? Se connecter
                    </a>
                </div>
            </form>
            
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Vos données sont sécurisées et cryptées
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePasswordVisibility(fieldId) {
            const passwordInput = document.getElementById(fieldId);
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
        
        function checkPasswordStrength(password) {
            let strength = 0;
            
            // Longueur
            if (password.length >= 8) {
                strength++;
                updateRequirement('req-length', true);
            } else {
                updateRequirement('req-length', false);
            }
            
            // Majuscule
            if (/[A-Z]/.test(password)) {
                strength++;
                updateRequirement('req-uppercase', true);
            } else {
                updateRequirement('req-uppercase', false);
            }
            
            // Minuscule
            if (/[a-z]/.test(password)) {
                strength++;
                updateRequirement('req-lowercase', true);
            } else {
                updateRequirement('req-lowercase', false);
            }
            
            // Chiffre
            if (/[0-9]/.test(password)) {
                strength++;
                updateRequirement('req-number', true);
            } else {
                updateRequirement('req-number', false);
            }
            
            // Caractère spécial
            if (/[^A-Za-z0-9]/.test(password)) {
                strength++;
                updateRequirement('req-special', true);
            } else {
                updateRequirement('req-special', false);
            }
            
            // Mettre à jour la barre de force
            const meter = document.getElementById('password-strength');
            meter.className = 'password-strength-meter strength-' + strength;
            
            // Vérifier la correspondance des mots de passe
            const confirmPassword = document.getElementById('confirm_password').value;
            checkPasswordMatch(password, confirmPassword);
            
            // On ne désactive plus le bouton pour éviter l'effet "rien ne se passe"
            // Le bouton reste actif et les erreurs seront gérées par le HTML5 ou le PHP
        }
        
        function updateRequirement(elementId, met) {
            const element = document.getElementById(elementId);
            const icon = element.querySelector('i');
            
            if (met) {
                icon.className = 'fas fa-check text-green-500 mr-1';
            } else {
                icon.className = 'fas fa-times text-red-500 mr-1';
            }
        }
        
        function checkPasswordMatch(password, confirmPassword) {
            const matchElement = document.getElementById('password-match');
            
            if (confirmPassword === '') {
                matchElement.classList.add('hidden');
                return;
            }
            
            if (password === confirmPassword) {
                matchElement.classList.remove('hidden');
                matchElement.querySelector('i').className = 'fas fa-check-circle text-green-500 mr-1';
                matchElement.querySelector('span').textContent = 'Les mots de passe correspondent';
                matchElement.querySelector('span').className = 'text-green-600';
            } else {
                matchElement.classList.remove('hidden');
                matchElement.querySelector('i').className = 'fas fa-times-circle text-red-500 mr-1';
                matchElement.querySelector('span').textContent = 'Les mots de passe ne correspondent pas';
                matchElement.querySelector('span').className = 'text-red-600';
            }
        }
        
        // Écouter les changements sur le champ de confirmation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            checkPasswordMatch(password, this.value);
        });
        
        // Auto-submission protection
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            if (this.checkValidity()) {
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Création du compte...';
            }
        });
    </script>
</body>
</html>