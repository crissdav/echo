<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

/**
 * Interacciones sociales: me gusta, comentarios, seguidores y notificaciones.
 */
final class Social
{
    // ---------------- Me gusta ----------------
    public static function toggleLike(int $me, int $postId): array
    {
        $owner = (int) DB::value('SELECT user_id FROM posts WHERE id = ?', [$postId]);
        if (!$owner) {
            throw new \InvalidArgumentException('La publicación no existe.');
        }
        $deleted = DB::query('DELETE FROM likes WHERE user_id = ? AND post_id = ?', [$me, $postId])->rowCount();
        if ($deleted) {
            DB::query("DELETE FROM notifications WHERE type = 'like' AND actor_id = ? AND post_id = ?", [$me, $postId]);
            $liked = false;
        } else {
            DB::query('INSERT IGNORE INTO likes (user_id, post_id) VALUES (?, ?)', [$me, $postId]);
            self::notify($owner, $me, 'like', $postId);
            $liked = true;
        }
        $count = (int) DB::value('SELECT COUNT(*) FROM likes WHERE post_id = ?', [$postId]);
        return ['liked' => $liked, 'count' => $count];
    }

    // ---------------- Comentarios ----------------
    public static function comments(int $postId): array
    {
        return DB::all(
            'SELECT c.id, c.body, c.created_at, c.user_id, u.username, u.full_name, u.avatar_key
               FROM comments c JOIN users u ON u.id = c.user_id
              WHERE c.post_id = ? ORDER BY c.id',
            [$postId]
        );
    }

    public static function addComment(int $me, int $postId, string $body): array
    {
        $owner = (int) DB::value('SELECT user_id FROM posts WHERE id = ?', [$postId]);
        if (!$owner) {
            throw new \InvalidArgumentException('La publicación no existe.');
        }
        DB::query('INSERT INTO comments (post_id, user_id, body) VALUES (?, ?, ?)', [$postId, $me, $body]);
        $id = DB::lastId();
        self::notify($owner, $me, 'comment', $postId);
        return DB::one(
            'SELECT c.id, c.body, c.created_at, c.user_id, u.username, u.full_name, u.avatar_key
               FROM comments c JOIN users u ON u.id = c.user_id WHERE c.id = ?',
            [$id]
        );
    }

    /** Puede borrar el autor del comentario o el dueño de la publicación. */
    public static function deleteComment(int $me, int $commentId): bool
    {
        return DB::query(
            'DELETE c FROM comments c JOIN posts p ON p.id = c.post_id
              WHERE c.id = ? AND (c.user_id = ? OR p.user_id = ?)',
            [$commentId, $me, $me]
        )->rowCount() > 0;
    }

    // ---------------- Seguidores ----------------
    public static function toggleFollow(int $me, int $target): array
    {
        if ($me === $target) {
            throw new \InvalidArgumentException('No puedes seguirte a ti mismo.');
        }
        $deleted = DB::query('DELETE FROM follows WHERE follower_id = ? AND following_id = ?', [$me, $target])->rowCount();
        if ($deleted) {
            DB::query("DELETE FROM notifications WHERE type = 'follow' AND actor_id = ? AND user_id = ?", [$me, $target]);
            $following = false;
        } else {
            DB::query('INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)', [$me, $target]);
            self::notify($target, $me, 'follow', null);
            $following = true;
        }
        $followers = (int) DB::value('SELECT COUNT(*) FROM follows WHERE following_id = ?', [$target]);
        return ['following' => $following, 'followers' => $followers];
    }

    // ---------------- Notificaciones ----------------
    public static function notify(int $to, int $actor, string $type, ?int $postId): void
    {
        if ($to === $actor) {
            return; // no notificar acciones sobre uno mismo
        }
        DB::query(
            'INSERT INTO notifications (user_id, actor_id, type, post_id) VALUES (?, ?, ?, ?)',
            [$to, $actor, $type, $postId]
        );
    }

    public static function unreadCount(int $me): int
    {
        return (int) DB::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$me]);
    }

    public static function notifications(int $me, int $limit = 50): array
    {
        return DB::all(
            'SELECT n.id, n.type, n.post_id, n.is_read, n.created_at,
                    a.username, a.full_name, a.avatar_key, p.thumb_key,
                    EXISTS(SELECT 1 FROM follows f WHERE f.follower_id = ? AND f.following_id = a.id) AS is_following,
                    a.id AS actor_id
               FROM notifications n
               JOIN users a ON a.id = n.actor_id
               LEFT JOIN posts p ON p.id = n.post_id
              WHERE n.user_id = ?
              ORDER BY n.id DESC
              LIMIT ' . (int) $limit,
            [$me, $me]
        );
    }

    public static function markAllRead(int $me): void
    {
        DB::query('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0', [$me]);
    }
}
