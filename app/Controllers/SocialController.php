<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Social;

/**
 * Endpoints AJAX (JSON): me gusta, comentarios y seguir.
 */
final class SocialController
{
    public function like(string $id): void
    {
        $me = Auth::require();
        try {
            json_response(['ok' => true] + Social::toggleLike((int) $me['id'], (int) $id));
        } catch (\InvalidArgumentException $e) {
            json_response(['ok' => false, 'error' => $e->getMessage()], 404);
        }
    }

    public function comment(string $id): void
    {
        $me = Auth::require();
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body === '' || mb_strlen($body) > 500) {
            json_response(['ok' => false, 'error' => 'El comentario debe tener entre 1 y 500 caracteres.'], 422);
        }
        try {
            $comment = Social::addComment((int) $me['id'], (int) $id, $body);
        } catch (\InvalidArgumentException $e) {
            json_response(['ok' => false, 'error' => $e->getMessage()], 404);
        }
        json_response([
            'ok'   => true,
            'html' => View::partial('partials/comment', ['c' => $comment, 'postOwner' => 0, 'full' => ($_POST['full'] ?? '') === '1']),
        ]);
    }

    public function deleteComment(string $id): void
    {
        $me = Auth::require();
        $ok = Social::deleteComment((int) $me['id'], (int) $id);
        json_response(['ok' => $ok], $ok ? 200 : 403);
    }

    public function follow(string $id): void
    {
        $me = Auth::require();
        try {
            json_response(['ok' => true] + Social::toggleFollow((int) $me['id'], (int) $id));
        } catch (\InvalidArgumentException $e) {
            json_response(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
