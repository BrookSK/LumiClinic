<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Core\Container\Container;

final class OpenAiClient
{
    private ?string $overrideKey = null;

    public function __construct(private readonly Container $container) {}

    /**
     * Creates an OpenAiClient that uses the provided API key instead of the configured one.
     */
    public static function withKey(Container $container, string $apiKey): self
    {
        $instance = new self($container);
        $instance->overrideKey = $apiKey;
        return $instance;
    }

    private function resolveKey(): string
    {
        if ($this->overrideKey !== null && trim($this->overrideKey) !== '') {
            return $this->overrideKey;
        }
        $key = (new AiConfigService($this->container))->getOpenAiApiKeyPlain();
        if ($key === null || trim($key) === '') {
            throw new \RuntimeException('IA não configurada (OpenAI API key).');
        }
        return $key;
    }

    /**
     * @param array<int,array<string,mixed>> $messages
     * @return array<string,mixed>
     */
    public function chatCompletions(string $model, array $messages, float $temperature = 0.2): array
    {
        $key = $this->resolveKey();

        $http = new \App\Services\Http\HttpClient();
        $resp = $http->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'Authorization' => 'Bearer ' . $key,
        ], [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
        ], 60);

        if ($resp['status'] < 200 || $resp['status'] >= 300) {
            throw new \RuntimeException('Falha ao chamar OpenAI.');
        }

        $json = $resp['json'];
        if (!is_array($json)) {
            throw new \RuntimeException('Resposta inválida da OpenAI.');
        }

        /** @var array<string,mixed> */
        return $json;
    }

    /**
     * @return array<string,mixed>
     */
    public function audioTranscription(string $filePath, string $filename, string $model = 'whisper-1'): array
    {
        $key = $this->resolveKey();

        $filePath = trim($filePath);
        if ($filePath === '' || !is_file($filePath)) {
            throw new \RuntimeException('Áudio inválido.');
        }

        $model = trim($model);
        if ($model === '') {
            $model = 'whisper-1';
        }

        $ch = curl_init();
        if ($ch === false) {
            throw new \RuntimeException('Falha ao inicializar cURL.');
        }

        $cfile = new \CURLFile($filePath, null, $filename);
        $post = [
            'model' => $model,
            'file' => $cfile,
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/audio/transcriptions',
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $key,
            ],
            CURLOPT_POSTFIELDS => $post,
        ]);

        $respBody = curl_exec($ch);
        if ($respBody === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('HTTP request falhou: ' . $err);
        }

        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException('Falha ao chamar OpenAI.');
        }

        $json = json_decode((string)$respBody, true);
        if (!is_array($json)) {
            throw new \RuntimeException('Resposta inválida da OpenAI.');
        }

        /** @var array<string,mixed> */
        return $json;
    }
}
