<?php
/**
 * Shared authenticated pages (notifications).
 *
 * @package CollegeCMS\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Notification;

final class SharedController extends Controller
{
    public function notifications(): void
    {
        $this->requireAuth();
        $rows = (new Notification())->forUser((int) Auth::id());
        $this->view('shared/notifications', ['title' => 'Notifications', 'rows' => $rows]);
    }

    public function notificationRead(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $id = (int) ($_POST['id'] ?? 0);
        (new Notification())->markRead($id, (int) Auth::id());
        redirect('/notifications');
    }

    public function notificationsReadAll(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        (new Notification())->markAllRead((int) Auth::id());
        redirect('/notifications');
    }
}
