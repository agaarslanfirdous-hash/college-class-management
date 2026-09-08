<?php
/**
 * Login, logout, password reset.
 *
 * @package CollegeCMS\Controllers
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\CSRF;
use App\Core\Mailer;
use App\Models\AuditLog;
use App\Models\LoginAttempt;
use App\Models\PasswordReset;
use App\Models\User;
final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        $this->view('auth/login', ['title' => 'Sign in']);
    }

    public function login(): void
    {
        $this->validateCsrf();
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $cfg = \app_config()['security'] ?? [];
        $max = (int) ($cfg['login_max_attempts'] ?? 5);
        $lockMins = (int) ($cfg['login_lockout_minutes'] ?? 15);

        $attempts = new LoginAttempt();
        if ($attempts->recentFailures($email, $lockMins) >= $max) {
            flash_set('error', 'Too many failed attempts. Try again later.');
            redirect('/login');
        }

        $userModel = new User();
        $row = $userModel->findByEmail($email);
        if ($row === false || !(bool) $row['is_active']) {
            $attempts->record($email, false);
            flash_set('error', 'Invalid credentials.');
            redirect('/login');
        }
        if (!password_verify($password, (string) $row['password_hash'])) {
            $attempts->record($email, false);
            flash_set('error', 'Invalid credentials.');
            redirect('/login');
        }

        $attempts->record($email, true);
        Auth::login($row);
        $userModel->touchLogin((int) $row['id']);
        (new AuditLog())->write((int) $row['id'], 'login', 'user', (int) $row['id'], null);

        flash_set('success', 'Welcome back.');
        redirect('/dashboard');
    }

    public function logout(): void
    {
        $uid = Auth::id();
        if ($uid) {
            (new AuditLog())->write($uid, 'logout', 'user', $uid, null);
        }
        Auth::logout();
        flash_set('success', 'You have been logged out.');
        redirect('/login');
    }

    public function showForgot(): void
    {
        $this->view('auth/forgot', ['title' => 'Forgot password']);
    }

    public function forgot(): void
    {
        $this->validateCsrf();
        $email = trim((string) ($_POST['email'] ?? ''));
        $userModel = new User();
        $row = $userModel->findByEmail($email);
        if ($row !== false && (bool) $row['is_active']) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            $expires = date('Y-m-d H:i:s', time() + 3600);
            (new PasswordReset())->create((int) $row['id'], $hash, $expires);
            $link = \base_url('reset-password/' . $token);
            $body = "Reset your password:\n\n{$link}\n\nThis link expires in 1 hour.";
            Mailer::send((string) $row['email'], 'Password reset', $body);
            (new AuditLog())->write((int) $row['id'], 'password_reset_requested', 'user', (int) $row['id'], null);
        }
        flash_set('success', 'If an account exists for that email, reset instructions were sent.');
        redirect('/login');
    }

    public function showReset(string $token): void
    {
        $hash = hash('sha256', $token);
        $pr = new PasswordReset();
        $row = $pr->findValid($hash);
        if ($row === false) {
            flash_set('error', 'Invalid or expired link.');
            redirect('/login');
        }
        $this->view('auth/reset', ['title' => 'Set new password', 'token' => $token]);
    }

    public function reset(): void
    {
        $this->validateCsrf();
        $token = (string) ($_POST['token'] ?? '');
        $pass = (string) ($_POST['password'] ?? '');
        $pass2 = (string) ($_POST['password_confirm'] ?? '');
        $minLen = (int) (\app_config()['security']['password_min_length'] ?? 10);
        if (strlen($pass) < $minLen || $pass !== $pass2) {
            flash_set('error', 'Passwords must match and meet minimum length.');
            redirect('/reset-password/' . rawurlencode($token));
        }
        if (!preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) {
            flash_set('error', 'Password must include letters and digits.');
            redirect('/reset-password/' . rawurlencode($token));
        }
        $hash = hash('sha256', $token);
        $pr = new PasswordReset();
        $row = $pr->findValid($hash);
        if ($row === false) {
            flash_set('error', 'Invalid or expired link.');
            redirect('/login');
        }
        $userId = (int) $row['user_id'];
        $userModel = new User();
        $userModel->updatePassword($userId, password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]));
        $pr->deleteForUser($userId);
        (new AuditLog())->write($userId, 'password_reset_complete', 'user', $userId, null);
        flash_set('success', 'Password updated. Please sign in.');
        redirect('/login');
    }
}
