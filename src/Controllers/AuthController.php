<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Employee;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Flash\Messages;
use Slim\Psr7\Response;
use Slim\Views\Twig;

class AuthController
{
    public function __construct(
        private readonly Twig $twig,
        private readonly Messages $flash,
        private readonly Employee $employees,
        private readonly array $settings
    ) {
    }

    public function showLogin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->user() !== null) {
            return $this->redirect($response, '/');
        }

        return $this->render($response, 'auth/login.html.twig', [
            'show_nav' => false,
            'page_title' => 'Employee Login',
            'old' => $_SESSION['old'] ?? [],
        ]);
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $name = trim((string) ($data['name'] ?? ''));
        $pin = trim((string) ($data['pin'] ?? ''));

        $_SESSION['old'] = ['name' => $name];

        if ($name === '' || $pin === '') {
            $this->flash->addMessage('error', 'Name and PIN are required.');

            return $this->redirect($response, '/login');
        }

        $employee = $this->employees->findActiveByCredentials($name, $pin);

        if ($employee === null) {
            $this->flash->addMessage('error', 'Invalid credentials or inactive employee.');

            return $this->redirect($response, '/login');
        }

        session_regenerate_id(true);

        $_SESSION['employee'] = [
            'id' => (int) $employee['id'],
            'name' => $employee['name'],
            'code' => $employee['code'],
            'jobTitle' => $employee['jobTitle'],
            'email' => $employee['email'],
            'phone1' => $employee['phone1'],
        ];

        unset($_SESSION['old']);

        $this->flash->addMessage('success', 'Welcome back, ' . $employee['name'] . '.');

        return $this->redirect($response, '/');
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        unset($_SESSION['employee'], $_SESSION['old']);
        session_regenerate_id(true);

        $this->flash->addMessage('success', 'You have been signed out.');

        return $this->redirect($response, '/login');
    }

    public function dashboard(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $this->user();

        if ($user === null) {
            return $this->redirect($response, '/login');
        }

        return $this->render($response, 'dashboard.html.twig', [
            'page_title' => 'Dashboard',
            'auth' => $user,
            'nav_section' => 'dashboard',
        ]);
    }

    private function render(ResponseInterface $response, string $template, array $data = []): ResponseInterface
    {
        $defaults = [
            'auth' => $this->user(),
            'show_nav' => true,
        ];

        return $this->twig->render($response, $template, array_merge($defaults, $data));
    }

    private function redirect(ResponseInterface $response, string $path): ResponseInterface
    {
        return $response
            ->withHeader('Location', $this->url($path))
            ->withStatus(302);
    }

    private function url(string $path): string
    {
        $basePath = $this->settings['app']['base_path'];
        $normalizedPath = '/' . ltrim($path, '/');

        return ($basePath === '' ? '' : $basePath) . $normalizedPath;
    }

    private function user(): ?array
    {
        return $_SESSION['employee'] ?? null;
    }
}
