<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Core\Container\Container;
use App\Repositories\AuditLogRepository;
use App\Repositories\SaasPlanRepository;

final class SystemPlanService
{
    public function __construct(private readonly Container $container) {}

    /** @return list<array<string,mixed>> */
    public function listPlans(): array
    {
        return (new SaasPlanRepository($this->container->get(\PDO::class)))->listAll();
    }

    /** @param array<string,string> $data */
    public function createPlan(array $data, string $ip): int
    {
        $code = strtolower(trim($data['code'] ?? ''));
        $name = trim($data['name'] ?? '');
        $currency = strtoupper(trim($data['currency'] ?? 'BRL'));
        $intervalUnit = trim($data['interval_unit'] ?? 'month');
        $intervalCount = (int)($data['interval_count'] ?? '1');
        $trialDays = (int)($data['trial_days'] ?? '0');
        $priceCents = (int)($data['price_cents'] ?? '0');
        $limitsJson = trim($data['limits_json'] ?? '');
        $status = trim($data['status'] ?? 'active');

        if ($name === '') {
            throw new \RuntimeException('Nome é obrigatório.');
        }

        if ($code === '') {
            $base = strtolower($name);
            $base = preg_replace('/[^a-z0-9]+/i', '-', $base) ?? '';
            $base = trim($base, '-');
            $base = substr($base, 0, 48);
            $base = $base !== '' ? $base : 'plano';
            $code = $base;
        }

        if ($code === '' || !preg_match('/^[a-z0-9_\-]{2,64}$/', $code)) {
            throw new \RuntimeException('Código inválido.');
        }

        $allowedIntervals = ['day', 'week', 'month', 'year'];
        if (!in_array($intervalUnit, $allowedIntervals, true)) {
            throw new \RuntimeException('Intervalo inválido.');
        }
        if ($intervalCount <= 0 || $intervalCount > 120) {
            throw new \RuntimeException('Quantidade do intervalo inválida.');
        }
        if ($trialDays < 0 || $trialDays > 365) {
            throw new \RuntimeException('Dias de teste inválido.');
        }

        $priceCents = max(0, $priceCents);

        if ($limitsJson === '') {
            $limitsJson = '{}';
        }

        $decoded = json_decode($limitsJson, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('JSON de benefícios/limites inválido.');
        }
        $limitsJson = (string)json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $allowedStatus = ['active', 'inactive'];
        if (!in_array($status, $allowedStatus, true)) {
            throw new \RuntimeException('Status inválido.');
        }

        $repo = new SaasPlanRepository($this->container->get(\PDO::class));

        if ($repo->codeExists($code)) {
            $suffix = substr(bin2hex(random_bytes(4)), 0, 6);
            $candidate = substr($code, 0, 57) . '-' . $suffix;
            if (!$repo->codeExists($candidate)) {
                $code = $candidate;
            } else {
                throw new \RuntimeException('Já existe um plano com este código.');
            }
        }

        $id = $repo->create($code, $name, $priceCents, $currency, $intervalUnit, $intervalCount, $trialDays, $limitsJson, $status);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            (int)($_SESSION['user_id'] ?? null),
            null,
            'system.plans.create',
            ['id' => $id, 'code' => $code, 'name' => $name],
            $ip
        );

        return $id;
    }

    /** @param array<string,string> $data */
    public function updatePlan(array $data, string $ip): void
    {
        $id = (int)($data['id'] ?? '0');
        if ($id <= 0) {
            throw new \RuntimeException('Plano inválido.');
        }

        $name = trim($data['name'] ?? '');
        $currency = strtoupper(trim($data['currency'] ?? 'BRL'));
        $intervalUnit = trim($data['interval_unit'] ?? 'month');
        $intervalCount = (int)($data['interval_count'] ?? '1');
        $trialDays = (int)($data['trial_days'] ?? '0');
        $priceCents = (int)($data['price_cents'] ?? '0');
        $limitsJson = trim($data['limits_json'] ?? '');

        if ($name === '') {
            throw new \RuntimeException('Nome é obrigatório.');
        }

        $allowedIntervals = ['day', 'week', 'month', 'year'];
        if (!in_array($intervalUnit, $allowedIntervals, true)) {
            throw new \RuntimeException('Intervalo inválido.');
        }
        if ($intervalCount <= 0 || $intervalCount > 120) {
            throw new \RuntimeException('Quantidade do intervalo inválida.');
        }
        if ($trialDays < 0 || $trialDays > 365) {
            throw new \RuntimeException('Dias de teste inválido.');
        }

        $priceCents = max(0, $priceCents);

        if ($limitsJson === '') {
            $limitsJson = '{}';
        }

        $decoded = json_decode($limitsJson, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('JSON de benefícios/limites inválido.');
        }
        $limitsJson = (string)json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $repo = new SaasPlanRepository($this->container->get(\PDO::class));
        $repo->update($id, $name, $priceCents, $currency, $intervalUnit, $intervalCount, $trialDays, $limitsJson);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            (int)($_SESSION['user_id'] ?? null),
            null,
            'system.plans.update',
            ['id' => $id, 'name' => $name],
            $ip
        );
    }

    public function setStatus(int $id, string $status, string $ip): void
    {
        if ($id <= 0) {
            throw new \RuntimeException('Plano inválido.');
        }

        $status = trim($status);
        $allowed = ['active', 'inactive'];
        if (!in_array($status, $allowed, true)) {
            throw new \RuntimeException('Status inválido.');
        }

        $repo = new SaasPlanRepository($this->container->get(\PDO::class));
        $repo->setStatus($id, $status);

        (new AuditLogRepository($this->container->get(\PDO::class)))->log(
            (int)($_SESSION['user_id'] ?? null),
            null,
            'system.plans.set_status',
            ['id' => $id, 'status' => $status],
            $ip
        );
    }
}
