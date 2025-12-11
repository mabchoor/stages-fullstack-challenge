## [BUG-003] Upload d'image > 2MB fait crasher l'application

**Statut** : Résolu

---

### Problème identifié

Erreur `413 Payload Too Large` lors de l'upload d'images supérieures à 2MB. Le code Laravel accepte jusqu'à 20MB (`max:20480` dans la validation), mais PHP bloque l'upload avant que la requête n'atteigne Laravel avec ses limites par défaut : `upload_max_filesize = 2M` et `post_max_size = 2M`. L'erreur se produit au niveau du serveur web/PHP, pas au niveau de l'application.

---

### Solution implémentée

**Fichier créé** : `project/backend/public/.user.ini`

Création d'un fichier de configuration PHP `.user.ini` pour augmenter les limites d'upload à 10MB, permettant l'upload d'images volumineuses sans modifier la configuration PHP globale du serveur.

**Code clé** :
```ini
; PHP Configuration for file uploads
; Increases upload limits to allow images up to 10MB

upload_max_filesize = 10M
post_max_size = 10M
memory_limit = 128M
max_execution_time = 300
```

---

### Tests effectués

- [x] Vérification des limites PHP actuelles via `php -r "echo ini_get('upload_max_filesize');"`
- [x] Création du fichier `.user.ini` dans le dossier public
- [x] Redémarrage du serveur PHP pour appliquer les changements
- [x] Vérification que les nouvelles limites sont bien prises en compte

---

### Réponses aux questions à considérer

**Q1** : Où se trouve la limite d'upload ? (PHP, Apache, Laravel, Docker) - comment l'identifier ?
> La limite se trouve dans la **configuration PHP**. Pour l'identifier : `php -r "echo ini_get('upload_max_filesize');"`. Laravel valide à 20MB dans le code, mais PHP bloque à 2MB par défaut avant que la requête n'atteigne le contrôleur Laravel. L'erreur 413 indique un blocage au niveau PHP/serveur web, pas au niveau applicatif.

**Q2** : Comment modifier cette configuration dans un environnement Docker sans tout reconstruire ?
> En créant un fichier `.user.ini` dans le dossier `public/` qui surcharge la configuration PHP globale. Ce fichier est lu automatiquement par PHP-FPM/CGI sans nécessiter de rebuild Docker. Il suffit de redémarrer le serveur PHP. Alternative : monter un volume Docker avec un fichier `php.ini` personnalisé.

**Q3** : Comment vérifier que la modification a bien été appliquée après redémarrage ?
> Trois méthodes : 1) Créer un fichier `phpinfo.php` avec `<?php phpinfo();` et chercher "upload_max_filesize". 2) Via CLI : `php -r "echo ini_get('upload_max_filesize');"` (doit afficher "10M"). 3) Test réel : uploader une image de 5MB et vérifier l'absence d'erreur 413.

---

### Temps passé

Environ 30 minutes

---

### Difficultés rencontrées

Aucune - problème de configuration classique. La solution avec `.user.ini` est élégante car elle ne nécessite pas de modifier la configuration PHP globale du serveur.

---

### Checklist

- [x] Code fonctionne localement
- [x] Tests manuels passés
- [x] Commits avec messages clairs
- [x] Documentation claire de la solution
