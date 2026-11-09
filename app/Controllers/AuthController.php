<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;

final class AuthController
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 60;

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

        // Bloqueo temporal tras varios intentos fallidos (fuerza bruta).
        $lock = $_SESSION['login_lock_until'] ?? 0;
        if ($lock > time()) {
            flash('error', 'Demasiados intentos. Espera ' . ($lock - time()) . ' segundos.');
            redirect('/login');
        }

        $user = $login !== '' ? User::findByLogin($login) : null;
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            if ($_SESSION['login_attempts'] >= self::MAX_ATTEMPTS) {
                $_SESSION['login_lock_until'] = time() + self::LOCK_SECONDS;
                $_SESSION['login_attempts'] = 0;
            }
            flash('error', 'Usuario o contraseña incorrectos.');
            redirect('/login');
        }

        unset($_SESSION['login_attempts'], $_SESSION['login_lock_until']);
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
}
