<?php

declare(strict_types=1);

namespace App\Services\Http;

final class HttpClient
{
    /**
     * @param array<string,string> $headers
     * @param array<string,mixed>|null $json
     * @return array{status:int,headers:array<string,string>,body:string,json:mixed}
     */
    public function request(string $method, string $url, array $headers = [], ?array $json = null, int $timeoutSeconds = 30): array
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new \RuntimeException('Falha ao inicializar cURL.');
        }

        $method = strtoupper(trim($method));

        $curlHeaders = [];
        foreach ($headers as $k => $v) {
            $curlHeaders[] = $k . ': ' . $v;
        }

        $body = null;
        if ($json !== null) {
            $body = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($body === false) {
                throw new \RuntimeException('Falha ao serializar JSON.');
            }

            $curlHeaders[] = 'Content-Type: application/json; charset=UTF-8';
        }

        $responseHeaders = [];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_HTTPHEADER => $curlHeaders,
            CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$responseHeaders): int {
                $len = strlen($line);
                $line = trim($line);
                if ($line === '' || !str_contains($line, ':')) {
                    return $len;
                }
                [$name, $value] = explode(':', $line, 2);
                $responseHeaders[strtolower(trim($name))] = trim($value);
                return $len;
            },
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $respBody = curl_exec($ch);
        if ($respBody === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('HTTP request falhou: ' . $err);
        }

        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = null;
        $ct = $responseHeaders['content-type'] ?? '';
        if (str_contains($ct, 'application/json')) {
            $decoded = json_decode((string)$respBody, true);
        }

        return [
            'status' => $status,
            'headers' => $responseHeaders,
            'body' => (string)$respBody,
            'json' => $decoded,
        ];
    }
}
