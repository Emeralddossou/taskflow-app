# Workflow Git

## Structure des branches :

main
└── dev
├── feature/nom-fonctionnalite
├── fix/correction-bug
└── hotfix/urgence-prod

text

## Règles :
1. `main` : version stable, déployée en production
2. `dev` : environnement de développement intégré
3. `feature/*` : nouvelle fonctionnalité (issue depuis dev)
4. `fix/*` : correction de bug
5. `hotfix/*` : correction urgente pour production