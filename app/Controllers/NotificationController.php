<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Social;

final class NotificationController
{
    public function index(): void
    {
        $me = Auth::require();
        $items = Social::notifications((int) $me['id']);
        Social::markAllRead((int) $me['id']);

        // Agrupar por período: Hoy, Esta semana, Este mes, Anteriores
        $groups = [];
        foreach ($items as $n) {
            $age = time() - strtotime($n['created_at']);
            $label = match (true) {
                $age < 86400   => 'Hoy',
                $age < 604800  => 'Esta semana',
                $age < 2592000 => 'Este mes',
                default        => 'Anteriores',
            };
            $groups[$label][] = $n;
        }
        View::render('notifications/index', ['title' => 'Notificaciones', 'groups' => $groups]);
    }
}
