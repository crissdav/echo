<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Post;
use App\Models\User;

final class ExploreController
{
    public function index(): void
    {
        $me = Auth::require();
        $q = trim((string) ($_GET['q'] ?? ''));
        $q = ltrim($q, '@');

        // Búsqueda con "#" → página del hashtag
        if (str_starts_with($q, '#') && mb_strlen($q) > 1) {
            redirect('/tag/' . rawurlencode(mb_strtolower(substr($q, 1))));
        }

        View::render('explore/index', [
            'title'   => 'Explorar',
            'q'       => $q,
            'results' => $q !== '' ? User::search($q, (int) $me['id']) : [],
            'posts'   => $q === '' ? Post::explore() : [],
            'tags'    => Post::trendingTags(),
            'people'  => $q === '' ? User::suggestions((int) $me['id'], 8) : [],
        ]);
    }

    public function tag(string $tag): void
    {
        Auth::require();
        $tag = mb_strtolower($tag);
        View::render('explore/tag', [
            'title' => '#' . $tag,
            'tag'   => $tag,
            'posts' => Post::byTag($tag),
        ]);
    }
}
