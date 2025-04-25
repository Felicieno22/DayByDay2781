commande au debut 

 composer install
    php artisan migrate --seed
    php artisan key:generate
    npm install 
    npm run dev

    php artisan migrate:fresh 

# Guide de Gestion des Branches Git

## 1. Configuration initiale des branches

```bash
# Créer et basculer vers la branche dev
git checkout -b dev
git push origin dev

# Créer et basculer vers la branche features
git checkout -b features
git push origin features
```

## 2. Workflow quotidien

### A. Travailler sur features
```bash
# S'assurer d'être sur features
git checkout features

# Mettre à jour features avec les derniers changements de dev
git pull origin dev

# Faire vos modifications...
git add .
git commit -m "description de vos modifications"
git push origin features
```

### B. Fusionner features dans dev
```bash
# Basculer sur dev
git checkout dev

# Mettre à jour dev
git pull origin dev

# Fusionner features dans dev
git merge features
git push origin dev
```

### C. Fusionner dev dans main
```bash
# Basculer sur main
git checkout main

# Mettre à jour main
git pull origin main

# Fusionner dev dans main
git merge dev
git push origin main
```

## 3. Bonnes pratiques
- Toujours créer une nouvelle branche features pour chaque nouvelle fonctionnalité
- Tester le code avant de fusionner dans dev
- S'assurer que dev est stable avant de fusionner dans main
- Faire des commits clairs et descriptifs
- Toujours pull avant de commencer à travailler

## 4. Consulter l'historique

### Voir l'historique des commits
```bash
# Voir l'historique de tous les commits
git log

# Voir l'historique avec les modifications en détail
git log -p

# Voir l'historique de manière condensée (une ligne par commit)
git log --oneline

# Voir l'historique avec un graphique des branches
git log --graph --oneline --all
```

### Comparer des versions
```bash
# Voir les différences entre deux commits
git diff commit1_hash commit2_hash

# Voir les différences entre deux branches
git diff dev features

# Voir les modifications d'un fichier spécifique
git log -p nom_du_fichier
```

### Revenir à une ancienne version
```bash
# Voir le contenu d'une ancienne version
git checkout commit_hash

# Revenir à une version précédente
git revert commit_hash

# Retourner à la dernière version de la branche
git checkout nom_de_la_branche
```

### Astuces
- Utilisez `git blame fichier` pour voir qui a modifié chaque ligne
- Utilisez `git show commit_hash` pour voir les détails d'un commit spécifique
- Les hashs des commits peuvent être abrégés (premiers caractères suffisent)
- Utilisez `q` pour quitter l'affichage de git log
