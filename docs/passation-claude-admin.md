## Session admin — note d'intention

### Contexte
Le socle public est refactorisé et fonctionnel (sessions 1-3, voir README).
L'admin fonctionnait avant la refacto du public — les deux coexistent.
Les JSON produits par l'admin alimentent correctement le front.

### Objectif de la session
Refactoriser l'admin sur le même modèle de rigueur que le public.

### Chantiers identifiés

1. **API — migration v2 vers racine**
   - `admin/api/v2/` devient `admin/api/`
   - L'ancienne `admin/api/` est abandonnée
   - Unifier le contrat : structure de réponse homogène, 
     gestion des erreurs cohérente, séparation lecture/écriture

2. **Aligner sur `config_admin.php`**
   - Vérifier que tous les fichiers admin chargent `config_admin.php`
   - Et non `config.php` directement

3. **Audit dans l'ordre logique**
   - `config_admin.php` d'abord
   - Puis les modèles `admin/src/model/`
   - Puis les API
   - Puis les pages admin

### Règles à maintenir
- Mêmes conventions que le public : BEM, DIR_*, htmlspecialchars
- Pas de `../` en dur
- Toute friction corrigée → test associé
- README mis à jour à chaque session

### Fichiers de référence à soumettre au début de session
- Ce README
- Cette note
- `config_admin.php` en premier