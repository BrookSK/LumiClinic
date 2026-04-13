<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use App\Services\Http\HttpClient;

final class TuquinhaClient
{
    private const BASE_URL = 'https://tuquinha.onsolutionsbrasil.com.br';

    public function __construct(private readonly string $apiKey) {}

    /** @return list<array<string,mixed>> */
    public function listEvents(int $year, int $month): array
    {
        $resp = $this->get('/api/marketing-calendar/events', ['year' => $year, 'month' => $month]);
        if (!($resp['ok'] ?? false)) {
            throw new \RuntimeException('Tuquinha API: ' . ($resp['error'] ?? 'Erro desconhecido'));
        }
        return is_array($resp['events'] ?? null) ? $resp['events'] : [];
    }

    /** @return array<string,mixed> */
    public function getEvent(int $id): array
    {
        $resp = $this->get('/api/marketing-calendar/events/show', ['id' => $id]);
        if (!($resp['ok'] ?? false)) {
            throw new \RuntimeException('Tuquinha API: ' . ($resp['error'] ?? 'Erro desconhecido'));
        }
        return is_array($resp['event'] ?? null) ? $resp['event'] : $resp;
    }

    /** @return array<string,mixed> */
    public function createEvent(array $data): array
    {
        $resp = $this->post('/api/marketing-calendar/events', $data);
        if (!($resp['ok'] ?? false)) {
            throw new \RuntimeException('Tuquinha API: ' . ($resp['error'] ?? 'Erro ao criar evento'));
        }
        return $resp;
    }

    /** @return array<string,mixed> */
    public function updateEvent(array $data): array
    {
        $resp = $this->post('/api/marketing-calendar/events/update', $data);
        if (!($resp['ok'] ?? false)) {
            throw new \RuntimeException('Tuquinha API: ' . ($resp['error'] ?? 'Erro ao atualizar evento'));
        }
        return $resp;
    }

    /** @return array<string,mixed> */
    public function deleteEvent(int $id): array
    {
        $resp = $this->post('/api/marketing-calendar/events/delete', ['id' => $id]);
        if (!($resp['ok'] ?? false)) {
            throw new \RuntimeException('Tuquinha API: ' . ($resp['error'] ?? 'Erro ao excluir evento'));
        }
        return $resp;
    }

    /** @return array<string,mixed> */
    private function get(string $path, array $params = []): array
    {
        $url = self::BASE_URL . $path;
        if ($params !== []) {
            $url .= '?' . http_build_query($params);
        }

        $http = new HttpClient();
        $resp = $http->request('GET', $url, ['Authorization' => 'Bearer ' . $this->apiKey], null, 30);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            $body = trim((string)($resp['body'] ?? ''));
            throw new \RuntimeException('Tuquinha API HTTP ' . $resp['status'] . ($body !== '' ? ': ' . mb_substr($body, 0, 200) : ''));
        }

        return is_array($resp['json']) ? $resp['json'] : ['raw' => $resp['body']];
    }

    /** @return array<string,mixed> */
    private function post(string $path, array $data): array
    {
        $url = self::BASE_URL . $path;

        $http = new HttpClient();
        $resp = $http->request('POST', $url, ['Authorization' => 'Bearer ' . $this->apiKey], $data, 30);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            $body = trim((string)($resp['body'] ?? ''));
            throw new \RuntimeException('Tuquinha API HTTP ' . $resp['status'] . ($body !== '' ? ': ' . mb_substr($body, 0, 200) : ''));
        }

        return is_array($resp['json']) ? $resp['json'] : ['raw' => $resp['body']];
    }
}
