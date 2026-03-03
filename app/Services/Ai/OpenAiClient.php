<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Core\Container\Container;
use App\Services\Http\HttpClient;

final class OpenAiClient
{
    public function __construct(private readonly Container $container) {}

    /**
     * @param array<int,array<string,mixed>> $messages
     * @return array<string,mixed>
     */
    public function chatCompletions(string $model, array $messages, float $temperature = 0.2): array
    {
        $key = (new AiConfigService($this->container))->getOpenAiApiKeyPlain();
        if ($key === null || trim($key) === '') {
            throw new \RuntimeException('IA não configurada (OpenAI API key).');
        }

        $http = new HttpClient();
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
}
