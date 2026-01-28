# Rapport d'Audit de Sécurité - TaskFlow App

**Date de l'audit** : 21 Janvier 2026
**Statut** : ✅ Sécurisé (avec avertissements mineurs)

## 1. Résumé Exécutif
L'intégration de la sécurité dans le pipeline CI/CD ("Shift Left") a été mise en place. Les outils suivants ont été configurés :
- **Snyk** : Scan des dépendances et du code.
- **SonarCloud** : Analyse statique du code (SAST).
- **Dependabot** : Mises à jour automatisées des dépendances.

## 2. Analyse des Dépendances (SCA)
Un scan local des dépendances a été effectué via `composer audit`.

- **Vulnérabilités trouvées** : 0
- **Packages abandonnés** : 1
  - `phploc/phploc` (Outil de développement, pas de risque direct en production, mais remplacement recommandé).

## 3. Analyse du Code Statique (SAST)
Un audit préliminaire du code a été réalisé avec un script de scan de sécurité personnalisé (`scripts/security-audit.php`) et une analyse manuelle.

### Résultats du Scan
- **Fausses alertes ("False Positives") identifiées** :
  - `register.php` : Multiples détections de "possible hardcoded password".
    - *Analyse* : Il s'agit des variables `$_POST['password']` et des IDs de champs HTML (`id="password"`). Ce ne sont **pas** des mots de passe en dur, mais des champs de formulaire légitimes.
    - *Action* : Marqués comme faux positifs.
  - `test_auth.php` : Détection similaire pour des variables de test.
  
- **Vulnérabilités Potentielles Vérifiées** :
  - Aucune injection SQL flagrante détectée (utilisation de PDO/Prepared Statements dans `includes/auth.php` et `includes/functions.php`).
  - Protection CSRF présente (`validate_csrf_token`).

## 4. Actions Prises
1. **Pipeline de Sécurité** : Création du fichier `.github/workflows/security.yml` intégrant Snyk et SonarCloud.
2. **Dependabot** : Configuration ajoutée dans `.github/dependabot.yml`.
3. **Audit Local** : Confirmation de l'absence de vulnérabilités critiques connues dans les dépendances actuelles.

## 5. Recommandations Futures
- Configurer les secrets `SNYK_TOKEN` et `SONAR_TOKEN` dans les paramètres du repository GitHub.
- Remplacer `phploc` par une alternative maintenue si nécessaire.
- Affiner les règles de détection du script `security-audit.php` pour ignorer les contextes de formulaires (input names).
