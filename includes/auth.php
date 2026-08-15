<?php
/**
 * Authentication / authorization guards.
 * Include this at the top of any protected page.
 */
require_once __DIR__ . '/../config/config.php';

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentRole(): ?string {
    return $_SESSION['role'] ?? null;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if (currentRole() !== $role) {
        // Student trying to hit admin pages or vice versa -> send home, not an error dump.
        header('Location: ' . BASE_URL . '/login.php?error=unauthorized');
        exit;
    }
}

function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function currentUserName(): string {
    return $_SESSION['name'] ?? 'User';
}
