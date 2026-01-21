# 🚀 TaskFlow - Application de Gestion de Tâches

TaskFlow est une application web de gestion de tâches personnelle, sécurisée et intuitive, développée en PHP 8.2 avec MySQL.

## ✨ Fonctionnalités

- ✅ Authentification sécurisée (inscription/connexion)
- ✅ CRUD complet des tâches
- ✅ Système de priorités (Basse/Moyenne/Haute)
- ✅ Gestion des statuts (En attente/En cours/Terminée)
- ✅ Recherche et filtrage avancés
- ✅ Interface responsive avec Tailwind CSS
- ✅ API REST pour interactions AJAX
- ✅ Tests unitaires intégrés

## 🛡️ Sécurité

- 🔒 Protection contre les injections SQL (PDO Prepared Statements)
- 🛡️ Protection XSS (htmlspecialchars, échappement des sorties)
- 🔐 Sessions sécurisées (HttpOnly, Secure, SameSite)
- 🚫 Rate limiting et verrouillage de compte
- 📝 Logs de sécurité détaillés
- 🔄 Tokens CSRF pour toutes les actions

## 🛠️ Installation

### Prérequis

- PHP 8.2+
- MySQL 5.7+
- Apache/Nginx
- Composer (optionnel)
