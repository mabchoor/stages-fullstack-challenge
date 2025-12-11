## [BUG-004] Les dates s'affichent en anglais et timezone US

**Statut** : Résolu

---

### Problème identifié

Les dates des articles s'affichent en format américain (12/25/2024 3:45 PM PST) au lieu du format français (25/12/2024 15:45 CET). Le problème vient de deux sources : la configuration Laravel utilise timezone 'America/Los_Angeles' et locale 'en', tandis que le composant React ArticleCard.jsx formate les dates avec 'en-US' et 'America/Los_Angeles'.

---

### Solution implémentée

**Fichiers modifiés** :
- `project/backend/config/app.php` : Configuration Laravel pour timezone et locale
- `project/frontend/src/components/ArticleCard.jsx` : Fonction de formatage des dates React

J'ai modifié la configuration Laravel pour utiliser le fuseau horaire Europe/Paris et la locale française, puis mis à jour le composant React pour formater les dates en français avec le bon timezone.

**Code clé** :

Backend (config/app.php) :
```php
'timezone' => 'Europe/Paris',
'locale' => 'fr',
'faker_locale' => 'fr_FR',
```

Frontend (ArticleCard.jsx) :
```javascript
return date.toLocaleDateString('fr-FR', {
  year: 'numeric',
  month: '2-digit',
  day: '2-digit',
  hour: '2-digit',
  minute: '2-digit',
  timeZone: 'Europe/Paris'
});
```

---

### Tests effectués

- [x] Vérification de la configuration backend (timezone et locale)
- [x] Vérification du formatage frontend (locale fr-FR et timezone Europe/Paris)
- [x] Validation du format de date affiché : 23/11/2025 12:47 (format français)
- [x] Commit et push des modifications vers GitHub

---

### Réponses aux questions à considérer

**Q1** : Où se configure la timezone et la locale dans une application Laravel ?
> Dans le fichier `config/app.php`. La timezone se configure via la clé `'timezone'` (ligne 71) et détermine comment PHP traite les dates. La locale se configure via `'locale'` (ligne 83) pour les traductions et `'faker_locale'` (ligne 109) pour la génération de données de test.

**Q2** : Faut-il modifier le backend, le frontend, ou les deux ?
> Les deux. Le backend Laravel définit la timezone pour les opérations serveur (création/modification en base), mais le formatage d'affichage se fait côté frontend React. Il faut donc modifier `config/app.php` (backend) ET `ArticleCard.jsx` (frontend) pour avoir une cohérence complète.

**Q3** : Comment s'assurer que les dates stockées en base restent cohérentes après le changement ?
> Les dates sont stockées en UTC dans la base de données (format MySQL TIMESTAMP). Laravel convertit automatiquement entre UTC et la timezone configurée. Le changement de timezone n'affecte que l'affichage et les nouvelles insertions, pas les données existantes. Pour vérifier : `SELECT created_at FROM articles LIMIT 1;` retournera toujours UTC.

---

### Temps passé

Environ 20 minutes

---

### Difficultés rencontrées

Aucune - problème de configuration simple. Il fallait juste identifier les deux endroits à modifier (backend + frontend).

---

### Checklist

- [x] Code fonctionne localement
- [x] Tests manuels passés
- [x] Commits avec messages clairs
- [x] Documentation claire de la solution
