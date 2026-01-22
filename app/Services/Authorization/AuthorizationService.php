<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use App\Core\Container\Container;
use App\Services\Auth\AuthService;

final class AuthorizationService
{
    public function __construct(private readonly Container $container) {}

    public function check(string $permissionCode): bool
    {
        $auth = new AuthService($this->container);

        $userId = $auth->userId();
        $clinicId = $auth->clinicId();

        if (isset($_SESSION['is_super_admin']) && (int)$_SESSION['is_super_admin'] === 1) {
            return true;
        }

        if ($userId === null || $clinicId === null) {
            return false;
        }

        $permissions = $_SESSION['permissions'] ?? null;
        if (!is_array($permissions)) {
            return false;
        }

        if (isset($permissions['allow'], $permissions['deny']) && is_array($permissions['allow']) && is_array($permissions['deny'])) {
            if (in_array($permissionCode, $permissions['deny'], true)) {
                return false;
            }
            return in_array($permissionCode, $permissions['allow'], true);
        }

        return in_array($permissionCode, $permissions, true);
    }
}
