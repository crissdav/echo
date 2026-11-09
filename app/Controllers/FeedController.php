<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Post;
use App\Models\User;

final class FeedController
{
    public function index(): void
    {
        $me = Auth::require();
        $posts = Post::feed((int) $me['id']);
        View::render('feed/index', [
            'title'       => 'Inicio',
            'posts'       => $posts,
            'suggestions' => User::suggestions((int) $me['id']),
            'active'      => User::recentlyActive((int) $me['id']),
            'nextCursor'  => count($posts) === Post::PAGE ? end($posts)['id'] : null,
        ]);
    }

    /** Scroll infinito: devuelve el HTML de la siguiente página. */
    public function more(): void
    {
        $me = Auth::require();
        $before = (int) ($_GET['before'] ?? 0);
        $posts = Post::feed((int) $me['id'], $before > 0 ? $before : null);
        $html = '';
        foreach ($posts as $post) {
            $html .= View::partial('partials/post_card', ['post' => $post]);
        }
        json_response([
            'ok'   => true,
            'html' => $html,
            'next' => count($posts) === Post::PAGE ? end($posts)['id'] : null,
        ]);
    }
}
