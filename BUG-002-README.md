## [BUG-002] Impossible de supprimer le dernier commentaire d'un article

**Statut** : Résolu

---

### Problème identifié

Erreur `Undefined array key 0` lors de la suppression du dernier commentaire d'un article. Le code tentait d'accéder au premier élément d'un tableau vide après suppression : `$remainingComments[0]` échouait quand aucun commentaire ne restait. Un article peut avoir 0 commentaire, ce qui est un état valide.

---

### Solution implémentée

**Fichier modifié** : `app/Http/Controllers/CommentController.php` - méthode `destroy()`

Remplacement de l'accès direct par index `$remainingComments[0]` par la méthode Laravel `$remainingComments->first()` qui retourne `null` de manière sécurisée si la collection est vide au lieu de générer une erreur.

**Code clé** :
```php
public function destroy($id)
{
    $comment = Comment::findOrFail($id);
    $articleId = $comment->article_id;
    $comment->delete();
    
    $remainingComments = Comment::where('article_id', $articleId)->get();
    
    return response()->json([
        'message' => 'Comment deleted successfully',
        'remaining_count' => $remainingComments->count(),
        'first_remaining' => $remainingComments->first(), // null si vide
    ]);
}
```

---

### Tests effectués

- [x] Créer un article avec 1 seul commentaire
- [x] Supprimer ce commentaire → Fonctionne sans erreur 500
- [x] Vérifier que first_remaining retourne null
- [x] Tester la suppression avec 2+ commentaires → Pas de régression

---

### Réponses aux questions à considérer

**Q1** : Comment as-tu reproduit l'erreur de manière fiable pour la débugger ?
> J'ai créé un article avec exactement 1 commentaire via l'interface, puis tenté de le supprimer. L'erreur 500 apparaît immédiatement avec le message `Undefined array key 0` dans les logs Laravel (`storage/logs/laravel.log`).

**Q2** : Pourquoi l'erreur se produit seulement avec 1 commentaire et pas avec 2+ ?
> Avec 2+ commentaires, après suppression il reste au moins 1 commentaire, donc `$remainingComments[0]` accède à un élément existant. Avec 1 seul commentaire, après suppression `$remainingComments` devient une collection vide `[]`, et tenter d'accéder à l'index `[0]` sur un tableau vide génère l'erreur.

**Q3** : Quelle est la meilleure approche pour éviter ce type d'erreur à l'avenir dans d'autres parties du code ?
> Toujours utiliser les méthodes de Collection Laravel : `first()` (retourne null si vide), `firstOrFail()` (lance une exception), ou vérifier `isNotEmpty()` avant l'accès par index. Éviter l'accès direct `[0]` sans vérification préalable. Faire un code review pour identifier les autres occurences de ce pattern dans le projet.

---

### Temps passé

Environ 20 minutes

---

### Difficultés rencontrées

Aucune - bug classique et simple à identifier. La solution est directe avec les méthodes Laravel.

---

### Checklist

- [x] Code fonctionne localement
- [x] Tests manuels passés
- [x] Commits avec messages clairs
- [x] Pas de régression sur les autres fonctionnalités
