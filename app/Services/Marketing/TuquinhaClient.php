<?php

declare(strict_types=1);

namespace App\Services\Marketing;

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
        // Add api_token as query param fallback (some servers strip Authorization header)
        $params['api_token'] = $this->apiKey;
        $url = self::BASE_URL . $path . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_UNRESTRICTED_AUTH => true,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Accept: application/json',
                'User-Agent: LumiClinic/1.0',
            ],
        ]);

        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('Tuquinha API cURL error: ' . $error);
        }

        if ($status < 200 || $status >= 300) {
            $body = trim((string)$body);
            throw new \RuntimeException('Tuquinha API HTTP ' . $status . ($body !== '' ? ': ' . mb_substr($body, 0, 300) : ''));
        }

        $decoded = json_decode((string)$body, true);
        return is_array($decoded) ? $decoded : ['raw' => $body];
    }

    /** @return array<string,mixed> */
    private function post(string $path, array $data): array
    {
        $url = self::BASE_URL . $path;
        // Add api_token as query param fallback (some servers strip Authorization header)
        $url .= '?api_token=' . urlencode($this->apiKey);
        $jsonBody = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Log for debugging
        error_log('[TuquinhaClient] POST ' . $url . ' | key_len=' . strlen($this->apiKey) . ' key_prefix=' . substr($this->apiKey, 0, 8) . '...');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_UNRESTRICTED_AUTH => true,
            CURLOPT_POSTREDIR => 3,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json; charset=UTF-8',
                'Accept: application/json',
                'User-Agent: LumiClinic/1.0',
            ],
        ]);

        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('Tuquinha API cURL error: ' . $error);
        }

        if ($status < 200 || $status >= 300) {
            $body = trim((string)$body);
            throw new \RuntimeException('Tuquinha API HTTP ' . $status . ($body !== '' ? ': ' . mb_substr($body, 0, 300) : ''));
        }

        $decoded = json_decode((string)$body, true);
        return is_array($decoded) ? $decoded : ['raw' => $body];
    }
}
