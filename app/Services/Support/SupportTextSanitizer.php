<?php

declare(strict_types=1);

namespace App\Services\Support;

/**
 * Limpeza de texto do suporte.
 *
 * redactSecrets(): usada em todo texto que vai para a OpenAI e para o banco (mensagens do chat, chamados).
 * anonymize(): usada em texto que pode virar conteúdo da base de conhecimento (resoluções). Inclui redactSecrets().
 */
final class SupportTextSanitizer
{
    /**
     * Remove segredos e documentos pessoais.
     *
     * Exemplos (entrada => saída):
     *  - "meu cpf é 123.456.789-09"            => "meu cpf é [CPF removido]"
     *  - "cnpj 11.222.333/0001-81"             => "cnpj [CNPJ removido]"
     *  - "cartão 4111 1111 1111 1111"          => "cartão [cartão removido]"   (13 a 19 dígitos que passam no Luhn)
     *  - "senha: abc123"                       => "senha: [removido]"
     *  - "Authorization: Bearer abc.def.ghi12" => "Authorization: Bearer [token removido]"
     *  - "sk-abcdefghijklmnopqrstuv"           => "[token removido]"
     *  - "a1b2c3...(32+ caracteres hex)"       => "[código removido]"
     *  - "agendamento 12345" (5 dígitos)       => inalterado (não é documento nem cartão)
     */
    public function redactSecrets(string $text): string
    {
        if ($text === '') {
            return '';
        }

        // Bearer tokens e chaves no formato sk-...
        $text = (string)preg_replace('/\bBearer\s+[A-Za-z0-9._~+\/=-]{8,}/i', 'Bearer [token removido]', $text);
        $text = (string)preg_replace('/\bsk-[A-Za-z0-9_-]{16,}/', '[token removido]', $text);

        // "senha: x", "password=x", "token: x", "api key: x"
        $text = (string)preg_replace(
            '/\b(senha|password|passwd|pwd|token|api[_ -]?key|chave(?:\s+de\s+api)?|secret)(?:\s*[:=]\s*|\s+(?:é|eh)\s+)\S+/iu',
            '$1: [removido]',
            $text
        );

        // CNPJ (14 dígitos, com ou sem pontuação) e CPF (11 dígitos). Sem pontuação, só se os dígitos verificadores conferem.
        $text = (string)preg_replace_callback(
            '/(?<!\d)\d{2}\.?\d{3}\.?\d{3}\/?\d{4}-?\d{2}(?!\d)/',
            function (array $m): string {
                $digits = preg_replace('/\D/', '', $m[0]);
                $formatted = preg_match('/[.\/-]/', $m[0]) === 1;
                return ($formatted || $this->validCnpj((string)$digits)) ? '[CNPJ removido]' : $m[0];
            },
            $text
        );
        $text = (string)preg_replace_callback(
            '/(?<!\d)\d{3}\.?\d{3}\.?\d{3}-?\d{2}(?!\d)/',
            function (array $m): string {
                $digits = preg_replace('/\D/', '', $m[0]);
                $formatted = preg_match('/[.-]/', $m[0]) === 1;
                return ($formatted || $this->validCpf((string)$digits)) ? '[CPF removido]' : $m[0];
            },
            $text
        );

        // Cartões: 13 a 19 dígitos (espaços ou hífens permitidos) que passam no Luhn
        $text = (string)preg_replace_callback(
            '/(?<![\d])(?:\d[ -]?){12,18}\d(?![\d])/',
            function (array $m): string {
                $digits = (string)preg_replace('/\D/', '', $m[0]);
                return $this->luhn($digits) ? '[cartão removido]' : $m[0];
            },
            $text
        );

        // Sequências longas de hex (32+) ou base64/token (40+)
        $text = (string)preg_replace('/\b[A-Fa-f0-9]{32,}\b/', '[código removido]', $text);
        $text = (string)preg_replace('/(?<![A-Za-z0-9+\/_-])[A-Za-z0-9+\/_-]{40,}={0,2}/', '[código removido]', $text);

        return $text;
    }

    /**
     * Anonimização completa, para texto que pode entrar na base de conhecimento.
     * Além de redactSecrets(), troca e-mails, links, IPs, telefones, números de 3+ dígitos e nomes conhecidos.
     *
     * Exemplos:
     *  - "maria@clinica.com"                    => "[e-mail]"
     *  - "https://x.com/a?id=5"                 => "[link]"
     *  - "ip 192.168.0.10"                      => "ip [ip]"
     *  - "(11) 99999-8888"                      => "[telefone]"
     *  - "paciente 4821"                        => "paciente [número]"
     *  - anonymize("Ana Souza ligou", ["Ana Souza"]) => "[nome] ligou"
     *
     * @param list<string> $knownNames nomes (clínica, usuário) a esconder, inteiros ou por partes com 4+ letras
     */
    public function anonymize(string $text, array $knownNames = []): string
    {
        $text = $this->redactSecrets($text);
        if ($text === '') {
            return '';
        }

        $text = (string)preg_replace('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', '[e-mail]', $text);
        $text = (string)preg_replace('~\b(?:https?://|www\.)[^\s<>"\']+~i', '[link]', $text);
        $text = (string)preg_replace('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', '[ip]', $text);
        $text = (string)preg_replace('/\b[0-9a-f]{0,4}(?::[0-9a-f]{1,4}){4,7}\b/i', '[ip]', $text);
        $text = (string)preg_replace('/(?<!\d)(?:\+?55\s?)?\(?\d{2}\)?\s?9?\d{4}[-\s]?\d{4}(?!\d)/', '[telefone]', $text);
        $text = (string)preg_replace('/\d{3,}/', '[número]', $text);

        $generic = ['clinica', 'clínica', 'estetica', 'estética', 'odonto', 'saude', 'saúde', 'centro', 'instituto', 'studio', 'espaco', 'espaço'];
        foreach ($knownNames as $name) {
            $name = trim((string)$name);
            if (mb_strlen($name) < 3) {
                continue;
            }
            $text = (string)preg_replace('/' . preg_quote($name, '/') . '/iu', '[nome]', $text);
            foreach (preg_split('/\s+/u', $name) ?: [] as $part) {
                if (mb_strlen($part) >= 4 && !in_array(mb_strtolower($part), $generic, true)) {
                    $text = (string)preg_replace('/\b' . preg_quote($part, '/') . '\b/iu', '[nome]', $text);
                }
            }
        }

        return $text;
    }

    private function luhn(string $digits): bool
    {
        $len = strlen($digits);
        if ($len < 13 || $len > 19) {
            return false;
        }
        $sum = 0;
        $alt = false;
        for ($i = $len - 1; $i >= 0; $i--) {
            $d = (int)$digits[$i];
            if ($alt) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $sum += $d;
            $alt = !$alt;
        }
        return $sum % 10 === 0;
    }

    private function validCpf(string $d): bool
    {
        if (strlen($d) !== 11 || preg_match('/^(\d)\1{10}$/', $d) === 1) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int)$d[$i] * (($t + 1) - $i);
            }
            $check = ((10 * $sum) % 11) % 10;
            if ((int)$d[$t] !== $check) {
                return false;
            }
        }
        return true;
    }

    private function validCnpj(string $d): bool
    {
        if (strlen($d) !== 14 || preg_match('/^(\d)\1{13}$/', $d) === 1) {
            return false;
        }
        foreach ([12, 13] as $t) {
            $weights = $t === 12 ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2] : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int)$d[$i] * $weights[$i];
            }
            $r = $sum % 11;
            $check = $r < 2 ? 0 : 11 - $r;
            if ((int)$d[$t] !== $check) {
                return false;
            }
        }
        return true;
    }
}
