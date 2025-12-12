<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ArticleController extends Controller
{
    /**
     * Display a listing of articles.
     * PERF-003: Cached for 1 minute
     */
    public function index(Request $request)
    {
        // Skip cache for performance test mode
        if ($request->has('performance_test')) {
            return $this->getArticles($request);
        }

        return Cache::remember('articles.index', 60, function () use ($request) {
            return $this->getArticles($request);
        });
    }

    /**
     * Get articles data (extracted for cache reuse).
     */
    private function getArticles(Request $request)
    {
        // PERF-001: Eager loading pour éviter le problème N+1
        $articles = Article::with(['author', 'comments'])->get();

        // PERF-001: Appliquer le délai artificiel une seule fois (pas par article)
        if ($request->has('performance_test')) {
            usleep(30000); // 30ms une seule fois pour simuler la latence
        }

        $articles = $articles->map(function ($article) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'content' => substr($article->content, 0, 200) . '...',
                'author' => $article->author->name,
                'comments_count' => $article->comments->count(),
                'published_at' => $article->published_at,
                'created_at' => $article->created_at,
            ];
        });

        return response()->json($articles);
    }

    /**
     * Display the specified article.
     */
    public function show($id)
    {
        $article = Article::with(['author', 'comments.user'])->findOrFail($id);

        return response()->json([
            'id' => $article->id,
            'title' => $article->title,
            'content' => $article->content,
            'author' => $article->author->name,
            'author_id' => $article->author->id,
            'image_path' => $article->image_path,
            'published_at' => $article->published_at,
            'created_at' => $article->created_at,
            'comments' => $article->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'user' => $comment->user->name,
                    'created_at' => $comment->created_at,
                ];
            }),
        ]);
    }

    /**
     * Search articles.
     * Fixed: Use Eloquent with prepared statements (secure against SQL injection)
     * Search in both title and content fields
     */
    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json([]);
        }

        // Utiliser Eloquent avec WHERE clause sécurisée (prepared statements automatiques)
        $articles = Article::where('title', 'LIKE', "%{$query}%")
            ->orWhere('content', 'LIKE', "%{$query}%")
            ->get();

        $results = $articles->map(function ($article) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'content' => substr($article->content, 0, 200),
                'published_at' => $article->published_at,
            ];
        });

        return response()->json($results);
    }

    /**
     * Store a newly created article.
     * PERF-003: Invalidate cache after creation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'author_id' => 'required|exists:users,id',
            'image_path' => 'nullable|string',
        ]);

        $article = Article::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'author_id' => $validated['author_id'],
            'image_path' => $validated['image_path'] ?? null,
            'published_at' => now(),
        ]);

        // Invalidate caches
        Cache::forget('articles.index');
        Cache::forget('stats');

        return response()->json($article, 201);
    }

    /**
     * Update the specified article.
     * PERF-003: Invalidate cache after update
     */
    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|max:255',
            'content' => 'sometimes|required',
        ]);

        $article->update($validated);

        // Invalidate caches
        Cache::forget('articles.index');
        Cache::forget('stats');

        return response()->json($article);
    }

    /**
     * Remove the specified article.
     * PERF-003: Invalidate cache after deletion
     */
    public function destroy($id)
    {
        $article = Article::findOrFail($id);
        $article->delete();

        // Invalidate caches
        Cache::forget('articles.index');
        Cache::forget('stats');

        return response()->json(['message' => 'Article deleted successfully']);
    }
}

