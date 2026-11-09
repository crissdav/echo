<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class Post
{
    public const PAGE = 6;

    /** Columnas comunes: autor, contadores y si "yo" le di like. Requiere 1 parámetro (? = mi id). */
    private const SELECT = '
        SELECT p.id, p.user_id, p.image_key, p.thumb_key, p.width, p.height, p.caption, p.location, p.created_at,
               u.username, u.full_name, u.avatar_key,
               (SELECT COUNT(*) FROM likes l    WHERE l.post_id = p.id) AS likes_count,
               (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments_count,
               EXISTS(SELECT 1 FROM likes l2 WHERE l2.post_id = p.id AND l2.user_id = ?) AS liked
          FROM posts p
          JOIN users u ON u.id = p.user_id';

    /** Feed: mis publicaciones + las de quienes sigo, paginado por id (keyset). */
    public static function feed(int $me, ?int $beforeId = null): array
    {
        $before = $beforeId ?? PHP_INT_MAX;
        $posts = DB::all(
            self::SELECT . '
             WHERE (p.user_id = ? OR p.user_id IN (SELECT following_id FROM follows WHERE follower_id = ?))
               AND p.id < ?
             ORDER BY p.id DESC
             LIMIT ' . self::PAGE,
            [$me, $me, $me, $before]
        );
        return self::withPreviewComments($posts);
    }

    public static function find(int $id, int $me): ?array
    {
        return DB::one(self::SELECT . ' WHERE p.id = ?', [$me, $id]);
    }

    public static function byUser(int $userId): array
    {
        return DB::all(
            'SELECT p.id, p.thumb_key,
                    (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS likes_count,
                    (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments_count
               FROM posts p WHERE p.user_id = ? ORDER BY p.id DESC',
            [$userId]
        );
    }

    /** Explorar: publicaciones con más interacción (likes + 2×comentarios), con peso por recencia. */
    public static function explore(int $limit = 30): array
    {
        return DB::all(
            'SELECT p.id, p.thumb_key, p.likes_count, p.comments_count FROM (
                SELECT p.id, p.thumb_key, p.created_at,
                       (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS likes_count,
                       (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments_count
                  FROM posts p
             ) p
             ORDER BY (p.likes_count + 2 * p.comments_count) / POW(TIMESTAMPDIFF(HOUR, p.created_at, NOW()) + 2, 0.5) DESC
             LIMIT ' . (int) $limit
        );
    }

    public static function byTag(string $tag): array
    {
        return DB::all(
            'SELECT p.id, p.thumb_key,
                    (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS likes_count,
                    (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments_count
               FROM posts p
               JOIN post_hashtags ph ON ph.post_id = p.id
               JOIN hashtags h       ON h.id = ph.hashtag_id
              WHERE h.tag = ?
              ORDER BY p.id DESC',
            [$tag]
        );
    }

    public static function trendingTags(int $limit = 8): array
    {
        return DB::all(
            'SELECT h.tag, COUNT(*) AS uses
               FROM hashtags h JOIN post_hashtags ph ON ph.hashtag_id = h.id
              GROUP BY h.id, h.tag
              ORDER BY uses DESC, h.tag
              LIMIT ' . (int) $limit
        );
    }

    public static function create(int $userId, array $img, string $imageKey, string $thumbKey, ?string $caption, ?string $location): int
    {
        $pdo = DB::pdo();
        $pdo->beginTransaction();
        try {
            DB::query(
                'INSERT INTO posts (user_id, image_key, thumb_key, width, height, caption, location) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$userId, $imageKey, $thumbKey, $img['width'], $img['height'], $caption, $location]
            );
            $postId = DB::lastId();
            foreach (extract_hashtags((string) $caption) as $tag) {
                DB::query('INSERT INTO hashtags (tag) VALUES (?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)', [$tag]);
                DB::query('INSERT IGNORE INTO post_hashtags (post_id, hashtag_id) VALUES (?, ?)', [$postId, DB::lastId()]);
            }
            $pdo->commit();
            return $postId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function delete(int $id): void
    {
        DB::query('DELETE FROM posts WHERE id = ?', [$id]);
        // Limpia hashtags que quedaron sin uso
        DB::query('DELETE h FROM hashtags h LEFT JOIN post_hashtags ph ON ph.hashtag_id = h.id WHERE ph.post_id IS NULL');
    }

    /** Agrega los 2 últimos comentarios de cada publicación con UNA sola consulta. */
    private static function withPreviewComments(array $posts): array
    {
        if (!$posts) {
            return $posts;
        }
        $ids = array_column($posts, 'id');
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $rows = DB::all(
            "SELECT * FROM (
                SELECT c.id, c.post_id, c.body, u.username,
                       ROW_NUMBER() OVER (PARTITION BY c.post_id ORDER BY c.id DESC) AS rn
                  FROM comments c JOIN users u ON u.id = c.user_id
                 WHERE c.post_id IN ($marks)
             ) t WHERE t.rn <= 2 ORDER BY t.id",
            $ids
        );
        $byPost = [];
        foreach ($rows as $r) {
            $byPost[$r['post_id']][] = $r;
        }
        foreach ($posts as &$p) {
            $p['preview_comments'] = $byPost[$p['id']] ?? [];
        }
        return $posts;
    }
}
