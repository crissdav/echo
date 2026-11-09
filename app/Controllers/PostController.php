<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Image;
use App\Core\Storage;
use App\Core\View;
use App\Models\Post;
use App\Models\Social;

final class PostController
{
    public function create(): void
    {
        Auth::require();
        View::render('post/create', ['title' => 'Nueva publicación']);
    }

    public function store(): void
    {
        $me = Auth::require();
        $caption = trim((string) ($_POST['caption'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));

        if (mb_strlen($caption) > 2200 || mb_strlen($location) > 80) {
            flash('error', 'La descripción admite hasta 2200 caracteres y la ubicación 80.');
            redirect('/new');
        }

        $imageKey = $thumbKey = null;
        try {
            $img = Image::processUpload($_FILES['image'] ?? []);
            $imageKey = Image::key('posts');
            $thumbKey = str_replace('.jpg', '_t.jpg', $imageKey);
            Storage::put($imageKey, $img['full']);
            Storage::put($thumbKey, $img['thumb']);
            @unlink($img['full']);
            @unlink($img['thumb']);

            $id = Post::create((int) $me['id'], $img, $imageKey, $thumbKey, $caption ?: null, $location ?: null);
        } catch (\InvalidArgumentException $e) {
            flash('error', e($e->getMessage()));
            redirect('/new');
        } catch (\Throwable $e) {
            // Si falló la BD después de subir a S3, no dejamos archivos huérfanos.
            Storage::delete($imageKey);
            Storage::delete($thumbKey);
            throw $e;
        }

        flash('success', '¡Publicado! Tu foto ya está haciendo eco ✨');
        redirect('/p/' . $id);
    }

    public function show(string $id): void
    {
        $me = Auth::require();
        $post = Post::find((int) $id, (int) $me['id']);
        if (!$post) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Publicación no encontrada']);
            return;
        }
        View::render('post/show', [
            'title'    => $post['username'] . ' en Echo',
            'post'     => $post,
            'comments' => Social::comments((int) $post['id']),
        ]);
    }

    public function destroy(string $id): void
    {
        $me = Auth::require();
        $post = Post::find((int) $id, (int) $me['id']);
        if (!$post || (int) $post['user_id'] !== (int) $me['id']) {
            http_response_code(403);
            exit('No puedes eliminar esta publicación.');
        }
        Post::delete((int) $post['id']);
        Storage::delete($post['image_key']);
        Storage::delete($post['thumb_key']);
        flash('success', 'Publicación eliminada.');
        redirect('/u/' . $me['username']);
    }
}
