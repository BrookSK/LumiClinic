<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

final class WhatsappTemplateRenderer
{
    /** @param array<string,string> $vars */
    public function render(string $templateBody, array $vars): string
    {
        $out = $templateBody;

        foreach ($vars as $k => $v) {
            $key = trim((string)$k);
            if ($key === '') {
                continue;
            }
            $out = str_replace('{' . $key . '}', (string)$v, $out);
        }

        return $out;
    }
}
