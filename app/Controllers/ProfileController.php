<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Image;
use App\Core\Storage;
use App\Core\View;
use App\Models\Post;
use App\Models\User;

final class ProfileController
{
    public function show(string $username): void
    {
        $me = Auth::require();
        $profile = User::profile(mb_strtolower($username), (int) $me['id']);
        if (!$profile) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Usuario no encontrado']);
            return;
        }
        View::render('profile/show', [
            'title'   => $profile['full_name'] . ' (@' . $profile['username'] . ')',
            'profile' => $profile,
            'posts'   => Post::byUser((int) $profile['id']),
            'isMe'    => (int) $profile['id'] === (int) $me['id'],
        ]);
    }

    public function connections(string $username, string $type): void
    {
        $me = Auth::require();
        if (!in_array($type, ['followers', 'following'], true)) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Página no encontrada']);
            return;
        }
        $profile = User::profile(mb_strtolower($username), (int) $me['id']);
        if (!$profile) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Usuario no encontrado']);
            return;
        }
        View::render('profile/connections', [
            'title'   => ($type === 'followers' ? 'Seguidores' : 'Seguidos') . ' de @' . $profile['username'],
            'profile' => $profile,
            'type'    => $type,
            'people'  => User::connections((int) $profile['id'], $type, (int) $me['id']),
        ]);
    }

    public function edit(): void
    {
        Auth::require();
        View::render('profile/edit', ['title' => 'Editar perfil']);
    }

    public function update(): void
    {
        $me = Auth::require();
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $bio = trim((string) ($_POST['bio'] ?? ''));
        $website = trim((string) ($_POST['website'] ?? ''));

        if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 60 || mb_strlen($bio) > 160 || mb_strlen($website) > 120) {
            flash('error', 'Revisa los campos: nombre (2–60), presentación (máx. 160) y sitio web (máx. 120).');
            redirect('/settings');
        }
        User::updateProfile((int) $me['id'], $fullName, $bio ?: null, $website ?: null);

        if (($_POST['remove_avatar'] ?? '') === '1' && $me['avatar_key']) {
            Storage::delete($me['avatar_key']);
            User::updateAvatar((int) $me['id'], null);
        } elseif (!empty($_FILES['avatar']['name'])) {
            try {
                $img = Image::processUpload($_FILES['avatar'], true);
                $key = Image::key('avatars');
                Storage::put($key, $img['thumb']); // avatar = versión cuadrada 400×400
                @unlink($img['full']);
                @unlink($img['thumb']);
                Storage::delete($me['avatar_key']);
                User::updateAvatar((int) $me['id'], $key);
            } catch (\InvalidArgumentException $e) {
                flash('error', e($e->getMessage()));
                redirect('/settings');
            }
        }
        flash('success', 'Perfil actualizado.');
        redirect('/u/' . $me['username']);
    }
}
