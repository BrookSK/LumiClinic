<?php

declare(strict_types=1);

/**
 * Valida a base de conhecimento do assistente de suporte (docs/knowledge-base/*.md).
 *
 * Uso: php bin/support_kb_check.php
 *
 * Sai com codigo 1 e lista os problemas quando encontra:
 *  - termos proibidos (area de plataforma, credenciais, infraestrutura);
 *  - e-mails reais, CPF/CNPJ, telefones, segredos aparentes e travessao;
 *  - arquivo sem front matter, sem "routes:" ou sem titulo "## ".
 * Sempre mostra o tamanho total em caracteres e uma estimativa de tokens.
 *
 * README.md e ignorado, pois lista os termos proibidos como exemplo do que nunca escrever.
 * 99-faq-resolucoes.md e gerenciado por maquina e nao precisa ter titulo "## ".
 */

$dir = dirname(__DIR__) . '/docs/knowledge-base';
$files = glob($dir . '/*.md');
if ($files === false || $files === []) {
    fwrite(STDERR, "Nenhum arquivo .md encontrado em $dir\n");
    exit(1);
}
sort($files);

// Termos simples (comparacao sem diferenciar maiusculas de minusculas).
$forbiddenTerms = [
    '/sys/',
    'super admin',
    'superadmin',
    'logar como',
    'impersonat',
    'evolution',
    'openai',
    'api_key',
    'apikey',
    "\u{2014}", // travessao
];

// Expressoes regulares (o sufixo "i" ja esta no padrao quando necessario).
$forbiddenRegex = [
    'token seguido de segredo longo' => '/token["\'\s:=]+[A-Za-z0-9_\-]{20,}/i',
    'CPF' => '/\b\d{3}\.\d{3}\.\d{3}-\d{2}\b/',
    'CNPJ' => '/\b\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}\b/',
    'numero de 11 ou mais digitos' => '/\b\d{11,}\b/',
    'telefone' => '/\(?\b\d{2}\)?[ .-]?9?\d{4}[ .-]\d{4}\b/',
];

$emailRegex = '/[A-Za-z0-9._%+\-]+@([A-Za-z0-9.\-]+\.[A-Za-z]{2,})/';
$allowedEmailDomains = ['example.com', 'example.org', 'example.net', 'exemplo.com', 'exemplo.com.br'];

$problems = [];
$totalChars = 0;
$checked = 0;

foreach ($files as $file) {
    $name = basename($file);
    if ($name === 'README.md') {
        continue;
    }

    $checked++;
    $text = (string) file_get_contents($file);
    $totalChars += mb_strlen($text, 'UTF-8');

    // Front matter: comeca com --- na primeira linha e tem a chave routes:.
    if (!preg_match('/\A---\R(.*?)\R---\R/s', $text, $fm)) {
        $problems[] = "$name: sem front matter (--- no inicio do arquivo)";
    } elseif (!preg_match('/^routes:/m', $fm[1])) {
        $problems[] = "$name: front matter sem a chave routes:";
    }

    // Pelo menos um titulo de secao (exceto o arquivo gerenciado por maquina).
    if ($name !== '99-faq-resolucoes.md' && !preg_match('/^## /m', $text)) {
        $problems[] = "$name: nenhum titulo '## ' encontrado";
    }

    $lines = preg_split('/\R/', $text) ?: [];
    foreach ($lines as $i => $line) {
        $n = $i + 1;

        foreach ($forbiddenTerms as $term) {
            if (mb_stripos($line, $term, 0, 'UTF-8') !== false) {
                $shown = $term === "\u{2014}" ? 'travessao (em dash)' : $term;
                $problems[] = "$name:$n: termo proibido '$shown'";
            }
        }

        foreach ($forbiddenRegex as $label => $regex) {
            if (preg_match($regex, $line) === 1) {
                $problems[] = "$name:$n: padrao proibido ($label)";
            }
        }

        if (preg_match_all($emailRegex, $line, $m) > 0) {
            foreach ($m[1] as $domain) {
                if (!in_array(strtolower($domain), $allowedEmailDomains, true)) {
                    $problems[] = "$name:$n: e-mail com dominio nao permitido ($domain)";
                }
            }
        }
    }
}

$tokens = (int) ceil($totalChars / 4);
echo "Arquivos verificados: $checked\n";
echo "Tamanho total: $totalChars caracteres (~$tokens tokens estimados)\n";

if ($problems !== []) {
    echo "\nProblemas encontrados (" . count($problems) . "):\n";
    foreach ($problems as $p) {
        echo " - $p\n";
    }
    exit(1);
}

echo "OK: nenhum problema encontrado.\n";
exit(0);
