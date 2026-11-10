<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\LoginThrottle;
use App\Models\User;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        View::render('auth/login', ['title' => 'Iniciar sesión'], 'guest');
    }

    public function login(): void
    {
        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        remember_input(['login' => $login]);

        $user = $login !== '' ? User::findByLogin($login) : null;
        $ip = client_ip();
        $key = LoginThrottle::key($user, $login);

        // Bloqueo temporal tras varios intentos fallidos (fuerza bruta), por cuenta y por IP.
        $wait = LoginThrottle::secondsLeft($ip, $key);
        if ($wait > 0) {
            flash('error', 'Demasiados intentos. Espera ' . self::humanWait($wait) . ' y vuelve a intentarlo.');
            redirect('/login');
        }

        if (!$user || !password_verify($password, $user['password_hash'])) {
            LoginThrottle::fail($ip, $key);
            flash('error', 'Usuario o contraseña incorrectos.');
            redirect('/login');
        }

        LoginThrottle::clear($key);
        forget_input();
        Auth::login((int) $user['id']);
        redirect('/');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        View::render('auth/register', ['title' => 'Crear cuenta'], 'guest');
    }

    public function register(): void
    {
        $data = [
            'full_name' => trim((string) ($_POST['full_name'] ?? '')),
            'username'  => mb_strtolower(trim((string) ($_POST['username'] ?? ''))),
            'email'     => mb_strtolower(trim((string) ($_POST['email'] ?? ''))),
        ];
        $password = (string) ($_POST['password'] ?? '');
        remember_input($data);

        $errors = [];
        if (mb_strlen($data['full_name']) < 2 || mb_strlen($data['full_name']) > 60) {
            $errors[] = 'Escribe tu nombre (2 a 60 caracteres).';
        }
        if (!preg_match('/^[a-z0-9_.]{3,30}$/', $data['username'])) {
            $errors[] = 'El usuario debe tener 3–30 caracteres: letras, números, punto o guion bajo.';
        } elseif (User::exists('username', $data['username'])) {
            $errors[] = 'Ese nombre de usuario ya está en uso.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo no es válido.';
        } elseif (User::exists('email', $data['email'])) {
            $errors[] = 'Ya existe una cuenta con ese correo.';
        }
        if (strlen($password) < 8 || !preg_match('/\d/', $password) || !preg_match('/[A-Za-z]/', $password)) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres, con letras y números.';
        }

        if ($errors) {
            flash('error', implode('<br>', array_map('e', $errors)));
            redirect('/register');
        }

        $id = User::create($data['username'], $data['email'], $data['full_name'], $password);
        forget_input();
        Auth::login($id);
        flash('success', '¡Bienvenido a Echo, ' . e($data['full_name']) . '! Sigue a algunas cuentas para llenar tu feed.');
        redirect('/explore');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }

    private static function humanWait(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' segundos';
        }
        $min = (int) ceil($seconds / 60);
        return $min . ($min === 1 ? ' minuto' : ' minutos');
    }
}
