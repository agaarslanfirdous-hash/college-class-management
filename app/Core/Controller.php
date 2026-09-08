<?php
/**
 * Base controller: views, JSON, auth guards.
 *
 * @package CollegeCMS\Core
 */

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $viewPath, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = APP_PATH . '/views/' . $viewPath . '.php';
        if (!is_file($file)) {
            http_response_code(500);
            echo 'View not found.';
            return;
        }
        require $file;
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function json(array $payload, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            flash_set('error', 'Please log in.');
            redirect('/login');
        }
    }

    /**
     * @param list<string> $roles
     */
    protected function requireRoles(array $roles): void
    {
        $this->requireAuth();
        $r = Auth::role();
        if ($r === null || !in_array($r, $roles, true)) {
            http_response_code(403);
            $this->view('layouts/error', [
                'title' => 'Forbidden',
                'message' => 'You do not have access to this page.',
            ]);
            exit;
        }
    }

    protected function validateCsrf(): void
    {
        $token = $_POST['_csrf'] ?? '';
        if (!CSRF::validate(is_string($token) ? $token : null)) {
            http_response_code(419);
            flash_set('error', 'Invalid session token. Please try again.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }
}
