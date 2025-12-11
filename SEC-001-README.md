## [SEC-001] Les mots de passe sont stockés en clair dans la base de données

**Statut** : Résolu

---

### Problème identifié

Les mots de passe des utilisateurs étaient stockés en clair (plain text) dans la table `users` au lieu d'être hashés avec bcrypt. Le seeder insérait les passwords directement sans hashing, et l'AuthController comparait les passwords avec `!==` au lieu d'utiliser `Hash::check()`. Cette faille de sécurité critique expose tous les mots de passe en cas de compromission de la base de données, violant les standards OWASP et le RGPD.

---

### Solution implémentée

**Fichiers modifiés** :
1. `project/backend/database/seeders/DatabaseSeeder.php` - Ajout de `Hash::make()` pour tous les passwords
2. `project/backend/app/Http/Controllers/AuthController.php` - Utilisation de `Hash::check()` (login) et `Hash::make()` (register)
3. `project/backend/app/Models/User.php` - Ajout du mutateur `setPasswordAttribute()` pour protection automatique
4. `project/backend/database/migrations/2025_12_11_230000_hash_existing_user_passwords.php` - Migration pour hasher les passwords existants en base

La solution hashe automatiquement tous les mots de passe avec **bcrypt** (algorithme par défaut de Laravel, sécurisé et irréversible). La migration vérifie si un password est déjà hashé avant de le traiter, évitant le double hashing.

**Code clé** :

DatabaseSeeder.php :
```php
use Illuminate\Support\Facades\Hash;

'password' => Hash::make('Admin123!'),
```

AuthController.php :
```php
// Login
if (!Hash::check($credentials['password'], $user->password)) {
    return response()->json(['message' => 'Invalid credentials'], 401);
}

// Register
'password' => Hash::make($validated['password']),
```

User.php :
```php
public function setPasswordAttribute($value)
{
    $this->attributes['password'] = \Illuminate\Support\Facades\Hash::make($value);
}
```

Migration :
```php
foreach ($users as $user) {
    if (!str_starts_with($user->password, '$2y$')) {
        DB::table('users')->where('id', $user->id)
            ->update(['password' => Hash::make($user->password)]);
    }
}
```

---

### Tests effectués

- [x] Vérification des passwords en DB via MySQL CLI : `SELECT email, LEFT(password, 30) FROM users;`
- [x] Passwords hashés commencent par `$2y$10$` (bcrypt)
- [x] Migration exécutée avec succès : `php artisan migrate`
- [x] Login fonctionne avec credentials originaux (admin@blog.com / Admin123!)
- [x] Register de nouveaux utilisateurs génère des passwords hashés

---

### Réponses aux questions à considérer

**Q1** : Qu'as-tu utilisé pour te connecter à la DB et exécuter la vérification `SELECT email, password FROM users;` ?
> J'ai utilisé **MySQL CLI** avec la commande `mysql -u root blog_db -e "SELECT email, password FROM users;"` pour vérifier l'état des mots de passe avant et après la correction. Cet outil en ligne de commande permet d'exécuter rapidement des requêtes SQL sans interface graphique.

**Q2** : Comment vas-tu migrer les mots de passe existants vers des mots de passe hashés ?
> J'ai créé une migration `2025_12_11_230000_hash_existing_user_passwords.php` qui récupère tous les utilisateurs, vérifie si leur password commence par `$2y$` (déjà hashé avec bcrypt), et hashe uniquement les passwords en clair avec `Hash::make()`. Cette approche conserve toutes les données et évite le double hashing.

**Q3** : Comment t'assurer que l'authentification fonctionne toujours après la modification ?
> J'ai modifié la méthode `login()` pour utiliser `Hash::check($plaintext, $hash)` qui compare le mot de passe fourni avec le hash stocké. La fonction `check()` applique le même algorithme bcrypt et compare les résultats. Test effectué : connexion avec admin@blog.com / Admin123! fonctionne après migration.

**Q4** : Où faut-il modifier le code pour que les futurs utilisateurs aient des mots de passe hashés ?
> Trois emplacements modifiés : **AuthController register()** avec `Hash::make()`, **User.php** avec mutateur `setPasswordAttribute()` (protection automatique à chaque assignation `$user->password = ...`), et **DatabaseSeeder** pour les seeds. Le mutateur garantit qu'aucun code ne peut stocker un password en clair, même par erreur.

---

### Temps passé

Environ 45 minutes

---

### Difficultés rencontrées

Aucune - problème classique de sécurité avec une solution standard Laravel. Le mutateur dans User.php ajoute une couche de protection supplémentaire pour éviter les oublis futurs.

---

### Checklist

- [x] Code fonctionne localement
- [x] Tests manuels passés
- [x] Commits avec messages clairs
- [x] Documentation claire de la solution
