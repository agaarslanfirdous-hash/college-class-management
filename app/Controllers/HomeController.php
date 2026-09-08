<?php
/**
 * Public pages: home, about, contact, privacy, terms.
 *
 * @package CollegeCMS\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

final class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', [
            'title' => \app_config()['app_name'] ?? 'College CMS',
        ]);
    }

    public function about(): void
    {
        $this->view('home/about', ['title' => 'About']);
    }

    public function contact(): void
    {
        $this->view('home/contact', ['title' => 'Contact']);
    }

    public function privacy(): void
    {
        $this->view('home/privacy', ['title' => 'Privacy Policy']);
    }

    public function terms(): void
    {
        $this->view('home/terms', ['title' => 'Terms of Use']);
    }

    public function notFound(): void
    {
        http_response_code(404);
        $this->view('layouts/error', [
            'title' => 'Page not found',
            'message' => 'The page you requested does not exist.',
        ]);
    }

    public function error500(string $message = 'An error occurred.'): void
    {
        http_response_code(500);
        $this->view('layouts/error', [
            'title' => 'Server error',
            'message' => $message,
        ]);
    }

    /** Redirect logged-in users to role dashboard */
    public function dashboardRedirect(): void
    {
        if (!Auth::check()) {
            redirect('/login');
        }
        $r = Auth::role();
        match ($r) {
            'admin', 'teacher', 'monitor' => redirect('/timetable-board'),
            'student' => redirect('/student'),
            default => redirect('/'),
        };
    }
}
