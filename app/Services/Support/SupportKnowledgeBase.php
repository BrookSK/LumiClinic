<?php

declare(strict_types=1);

namespace App\Services\Support;

use App\Core\Container\Container;
use App\Repositories\SupportKbEntryRepository;

/**
 * Base de conhecimento do assistente de suporte.
 *
 * Fontes: docs/knowledge-base/*.md (exceto README.md) e resoluções aprovadas em support_kb_entries.
 * Cada arquivo tem front matter (title, routes), seções "## " e, opcionalmente, um comentário
 * "<!-- perm: codigo -->" com a permissão exigida pela tela. Seções grandes são divididas por "### " e por
 * parágrafos. A busca usa BM25 sobre termos sem acento, com reforço quando a página atual está nas routes do arquivo.
 *
 * Os chunks tokenizados ficam em storage/cache/support_kb.json. A chave do cache combina a data de modificação
 * dos arquivos e a data das entradas aprovadas. Se o cache não puder ser lido ou gravado, tudo continua funcionando em memória.
 */
final class SupportKnowledgeBase
{
    public const MAX_CHUNK_CHARS = 2400;
    public const DEFAULT_LIMIT = 8;
    public const DEFAULT_TOKEN_BUDGET = 7000;
    private const CHARS_PER_TOKEN = 3.5;
    private const MAX_CHUNKS_PER_FILE = 5;
    private const ROUTE_BOOST = 1.35;
    private const CACHE_VERSION = 1;

    /** @var array{chunks:list<array<string,mixed>>,index:string,df:array<string,int>,avg_len:float}|null */
    private ?array $data = null;

    public function __construct(private readonly Container $container, private readonly ?string $docsDir = null) {}

    /**
     * @return list<array{id:string,file:string,title:string,perm:string,source:string,text:string,score:float}>
     */
    public function search(string $userText, string $context = '', string $pagePath = '', int $limit = self::DEFAULT_LIMIT, int $tokenBudget = self::DEFAULT_TOKEN_BUDGET): array
    {
        $data = $this->load();
        $chunks = $data['chunks'];
        $n = count($chunks);
        if ($n === 0) {
            return [];
        }

        $weights = [];
        foreach ($this->tokenize($userText) as $t) {
            $weights[$t] = 1.0;
        }
        foreach ($this->tokenize($context) as $t) {
            if (!isset($weights[$t])) {
                $weights[$t] = 0.5;
            }
        }
        if ($weights === []) {
            return [];
        }

        $pagePath = $this->normalizePath($pagePath);
        $k1 = 1.5;
        $b = 0.75;
        $avg = max(1.0, $data['avg_len']);

        $scored = [];
        foreach ($chunks as $i => $chunk) {
            $tf = $chunk['tf'];
            $len = max(1, (int)$chunk['len']);
            $score = 0.0;
            foreach ($weights as $term => $w) {
                if (!isset($tf[$term])) {
                    continue;
                }
                $df = $data['df'][$term] ?? 0;
                $idf = log(1 + ($n - $df + 0.5) / ($df + 0.5));
                $f = (float)$tf[$term];
                $score += $w * $idf * (($f * ($k1 + 1)) / ($f + $k1 * (1 - $b + $b * $len / $avg)));
            }
            if ($score <= 0.0) {
                continue;
            }
            if ($pagePath !== '' && $this->routeMatches($chunk['routes'], $pagePath)) {
                $score *= self::ROUTE_BOOST;
            }
            $scored[] = [$score, $i];
        }

        usort($scored, static fn(array $a, array $b): int => $b[0] <=> $a[0]);

        $out = [];
        $perFile = [];
        $usedChars = 0;
        $budgetChars = (int)($tokenBudget * self::CHARS_PER_TOKEN);
        foreach ($scored as [$score, $i]) {
            if (count($out) >= $limit) {
                break;
            }
            $chunk = $chunks[$i];
            $file = (string)$chunk['file'];
            if (($perFile[$file] ?? 0) >= self::MAX_CHUNKS_PER_FILE) {
                continue;
            }
            $size = mb_strlen((string)$chunk['text']);
            if ($out !== [] && $usedChars + $size > $budgetChars) {
                continue;
            }
            $perFile[$file] = ($perFile[$file] ?? 0) + 1;
            $usedChars += $size;
            $out[] = [
                'id' => (string)$chunk['id'],
                'file' => $file,
                'title' => (string)$chunk['title'],
                'perm' => (string)$chunk['perm'],
                'source' => (string)$chunk['source'],
                'text' => (string)$chunk['text'],
                'score' => $score,
            ];
        }

        return $out;
    }

    /** Lista compacta dos assuntos cobertos (um por arquivo), enviada em toda chamada. */
    public function compactIndex(): string
    {
        return $this->load()['index'];
    }

    /** @return array{chunks:int,files:int,chars:int} */
    public function stats(): array
    {
        $data = $this->load();
        $files = [];
        $chars = 0;
        foreach ($data['chunks'] as $c) {
            $files[(string)$c['file']] = true;
            $chars += mb_strlen((string)$c['text']);
        }
        return ['chunks' => count($data['chunks']), 'files' => count($files), 'chars' => $chars];
    }

    /** Estimativa de tokens (caracteres / 3,5). */
    public static function estimateTokens(string $text): int
    {
        return (int)ceil(mb_strlen($text) / self::CHARS_PER_TOKEN);
    }

    // ------------------------------------------------------------------ loading

    /** @return array{chunks:list<array<string,mixed>>,index:string,df:array<string,int>,avg_len:float} */
    private function load(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $dir = $this->docsDir ?? (dirname(__DIR__, 3) . '/docs/knowledge-base');
        $files = glob(rtrim($dir, '/') . '/*.md') ?: [];
        $files = array_values(array_filter($files, static fn(string $f): bool => strtolower(basename($f)) !== 'readme.md'));
        sort($files);

        $entries = $this->approvedEntries();
        $key = $this->cacheKey($files, $entries);

        $cacheFile = $this->cacheFile();
        if ($cacheFile !== null && is_file($cacheFile)) {
            $raw = @file_get_contents($cacheFile);
            if (is_string($raw) && $raw !== '') {
                $cached = json_decode($raw, true);
                if (is_array($cached) && ($cached['key'] ?? null) === $key && isset($cached['data']['chunks'], $cached['data']['df'], $cached['data']['index'], $cached['data']['avg_len'])) {
                    /** @var array{chunks:list<array<string,mixed>>,index:string,df:array<string,int>,avg_len:float} $d */
                    $d = $cached['data'];
                    return $this->data = $d;
                }
            }
        }

        $data = $this->build($files, $entries);

        if ($cacheFile !== null) {
            $payload = json_encode(['key' => $key, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($payload)) {
                $tmp = $cacheFile . '.' . bin2hex(random_bytes(4)) . '.tmp';
                if (@file_put_contents($tmp, $payload) !== false) {
                    if (!@rename($tmp, $cacheFile)) {
                        @unlink($tmp);
                    }
                }
            }
        }

        return $this->data = $data;
    }

    private function cacheFile(): ?string
    {
        $dir = dirname(__DIR__, 3) . '/storage/cache';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }
        if (!is_writable($dir)) {
            return null;
        }
        return $dir . '/support_kb.json';
    }

    /** @return list<array<string,mixed>> */
    private function approvedEntries(): array
    {
        try {
            $pdo = $this->container->get(\PDO::class);
            if (!$pdo instanceof \PDO) {
                return [];
            }
            return (new SupportKbEntryRepository($pdo))->listApproved();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param list<string> $files
     * @param list<array<string,mixed>> $entries
     */
    private function cacheKey(array $files, array $entries): string
    {
        $max = 0;
        foreach ($files as $f) {
            $m = @filemtime($f);
            if ($m !== false && $m > $max) {
                $max = $m;
            }
        }
        $parts = [];
        foreach ($entries as $e) {
            $parts[] = (int)($e['id'] ?? 0) . '@' . (string)($e['updated_at'] ?? $e['created_at'] ?? '');
        }
        sort($parts);
        return self::CACHE_VERSION . ':' . $max . ':' . count($files) . ':' . count($entries) . ':' . md5(implode('|', $parts));
    }

    /**
     * @param list<string> $files
     * @param list<array<string,mixed>> $entries
     * @return array{chunks:list<array<string,mixed>>,index:string,df:array<string,int>,avg_len:float}
     */
    private function build(array $files, array $entries): array
    {
        $chunks = [];
        $indexLines = [];

        foreach ($files as $path) {
            $raw = @file_get_contents($path);
            if (!is_string($raw) || trim($raw) === '') {
                continue;
            }
            $name = basename($path, '.md');
            [$meta, $body] = $this->parseFrontMatter($raw);
            $fileTitle = $meta['title'] !== '' ? $meta['title'] : $name;

            $sections = $this->splitSections($body);
            if ($sections === []) {
                continue;
            }
            $indexLines[] = '- ' . $fileTitle . ($meta['routes'] !== [] ? ' (' . implode(', ', array_slice($meta['routes'], 0, 4)) . ')' : '');

            $seq = 0;
            foreach ($sections as $section) {
                [$perm, $text] = $this->extractPerm($section['body']);
                foreach ($this->splitLarge($text) as $part) {
                    $seq++;
                    $chunks[] = $this->makeChunk(
                        $name . '#' . $seq,
                        $name,
                        $section['title'],
                        $fileTitle,
                        $meta['routes'],
                        $perm,
                        $part,
                        'kb'
                    );
                }
            }
        }

        foreach ($entries as $e) {
            $id = (int)($e['id'] ?? 0);
            $q = trim((string)($e['question'] ?? ''));
            $a = trim((string)($e['answer'] ?? ''));
            if ($id <= 0 || $q === '' || $a === '') {
                continue;
            }
            $chunks[] = $this->makeChunk('resolucao#' . $id, 'resolucao', $q, 'Resolução de chamado', [], '', "Pergunta: {$q}\nResposta: {$a}", 'resolucao');
        }
        if ($entries !== []) {
            $indexLines[] = '- Resoluções aprovadas de chamados anteriores';
        }

        $df = [];
        $totalLen = 0;
        foreach ($chunks as $c) {
            $totalLen += (int)$c['len'];
            foreach ($c['tf'] as $term => $_) {
                $df[$term] = ($df[$term] ?? 0) + 1;
            }
        }

        return [
            'chunks' => $chunks,
            'index' => implode("\n", $indexLines),
            'df' => $df,
            'avg_len' => $chunks === [] ? 1.0 : $totalLen / count($chunks),
        ];
    }

    /**
     * @param list<string> $routes
     * @return array<string,mixed>
     */
    private function makeChunk(string $id, string $file, string $title, string $fileTitle, array $routes, string $perm, string $text, string $source): array
    {
        $display = '### ' . $fileTitle . ' > ' . $title . "\n" . trim($text);
        $tf = [];
        $len = 0;
        // Título da seção pesa 3x, título do arquivo 1x, corpo 1x.
        foreach ([[$title, 3], [$fileTitle, 1], [$text, 1]] as [$src, $mult]) {
            foreach ($this->tokenize((string)$src) as $t) {
                $tf[$t] = ($tf[$t] ?? 0) + $mult;
                $len += $mult;
            }
        }
        return [
            'id' => $id,
            'file' => $file,
            'title' => $title,
            'routes' => $routes,
            'perm' => $perm,
            'source' => $source,
            'text' => $display,
            'tf' => $tf,
            'len' => $len,
        ];
    }

    // ------------------------------------------------------------------ parsing

    /** @return array{0:array{title:string,routes:list<string>},1:string} */
    private function parseFrontMatter(string $raw): array
    {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
        $raw = str_replace("\r\n", "\n", $raw);
        $meta = ['title' => '', 'routes' => []];

        if (!preg_match('/\A---\n(.*?)\n---[ \t]*\n?/s', $raw, $m)) {
            return [$meta, $raw];
        }

        $inRoutes = false;
        foreach (explode("\n", $m[1]) as $line) {
            if (preg_match('/^title:\s*(.*)$/u', $line, $t)) {
                $meta['title'] = trim($t[1], " \t\"'");
                $inRoutes = false;
            } elseif (preg_match('/^routes:\s*$/', $line)) {
                $inRoutes = true;
            } elseif ($inRoutes && preg_match('/^\s*-\s*(\S+)\s*$/', $line, $r)) {
                $meta['routes'][] = $this->normalizePath(trim($r[1], "\"'"));
            } elseif (trim($line) !== '') {
                $inRoutes = false;
            }
        }

        return [$meta, (string)substr($raw, strlen($m[0]))];
    }

    /** @return list<array{title:string,body:string}> */
    private function splitSections(string $body): array
    {
        $parts = preg_split('/^## +/m', "\n" . $body) ?: [];
        array_shift($parts);
        $out = [];
        foreach ($parts as $part) {
            $nl = strpos($part, "\n");
            $title = trim($nl === false ? $part : substr($part, 0, $nl));
            $text = $nl === false ? '' : trim(substr($part, $nl + 1));
            if ($title === '' || $text === '') {
                continue;
            }
            $out[] = ['title' => $title, 'body' => $text];
        }
        return $out;
    }

    /** @return array{0:string,1:string} permissão (texto do comentário) e corpo sem o comentário */
    private function extractPerm(string $body): array
    {
        $perm = '';
        $body = (string)preg_replace_callback('/<!--\s*perm:\s*(.*?)\s*-->\s*\n?/s', function (array $m) use (&$perm): string {
            if ($perm === '') {
                $perm = trim($m[1]);
            }
            return '';
        }, $body);
        return [$perm, trim($body)];
    }

    /** @return list<string> */
    private function splitLarge(string $text): array
    {
        if (mb_strlen($text) <= self::MAX_CHUNK_CHARS) {
            return [$text];
        }

        // 1) por subtítulos "### ", 2) por blocos separados por linha em branco (tabelas ficam inteiras).
        $blocks = preg_split('/^(?=### )/m', $text) ?: [$text];
        $units = [];
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }
            if (mb_strlen($block) <= self::MAX_CHUNK_CHARS) {
                $units[] = $block;
                continue;
            }
            $heading = '';
            if (preg_match('/^(### [^\n]*)\n/', $block, $h)) {
                $heading = $h[1] . "\n";
            }
            foreach (preg_split('/\n{2,}/', $block) ?: [$block] as $paragraph) {
                $paragraph = trim($paragraph);
                if ($paragraph === '') {
                    continue;
                }
                if (mb_strlen($paragraph) > self::MAX_CHUNK_CHARS) {
                    foreach (preg_split('/(?<=\n)/', $paragraph) ?: [$paragraph] as $line) {
                        $units[] = $line;
                    }
                } else {
                    $units[] = ($heading !== '' && !str_starts_with($paragraph, '###') ? $heading : '') . $paragraph;
                }
            }
        }

        $chunks = [];
        $cur = '';
        foreach ($units as $u) {
            $u = rtrim($u);
            if ($u === '') {
                continue;
            }
            if ($cur !== '' && mb_strlen($cur) + mb_strlen($u) + 2 > self::MAX_CHUNK_CHARS) {
                $chunks[] = $cur;
                $cur = '';
            }
            $cur = $cur === '' ? $u : $cur . "\n\n" . $u;
        }
        if ($cur !== '') {
            $chunks[] = $cur;
        }
        return $chunks === [] ? [$text] : $chunks;
    }

    // ------------------------------------------------------------------ text helpers

    /** @param list<string> $routes */
    private function routeMatches(array $routes, string $path): bool
    {
        foreach ($routes as $r) {
            if ($r === '/' || $r === '') {
                if ($path === '/') {
                    return true;
                }
                continue;
            }
            if ($path === $r || str_starts_with($path, $r . '/')) {
                return true;
            }
        }
        return false;
    }

    private function normalizePath(string $p): string
    {
        $p = trim($p);
        if ($p === '') {
            return '';
        }
        $q = strpos($p, '?');
        if ($q !== false) {
            $p = substr($p, 0, $q);
        }
        return $p === '/' ? '/' : rtrim($p, '/');
    }

    /** @return list<string> */
    private function tokenize(string $text): array
    {
        $text = strtr(mb_strtolower($text, 'UTF-8'), [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ]);
        $words = preg_split('/[^a-z0-9]+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stop = self::stopwords();
        $out = [];
        foreach ($words as $w) {
            if (strlen($w) < 2 || isset($stop[$w])) {
                continue;
            }
            $len = strlen($w);
            if ($len > 4 && str_ends_with($w, 'oes')) {
                $w = substr($w, 0, -3) . 'ao';
            } elseif ($len > 4 && str_ends_with($w, 's') && !str_ends_with($w, 'ss')) {
                $w = substr($w, 0, -1);
            }
            $out[] = $w;
        }
        return $out;
    }

    /** @return array<string,true> */
    private static function stopwords(): array
    {
        static $set = null;
        if ($set === null) {
            $words = 'a o as os um uma uns umas de do da dos das em no na nos nas por para pra com sem sob sobre entre ate ao aos '
                . 'e ou mas que se como quando onde qual quais quem quanto eu me meu minha meus minhas voce voces vc seu sua seus suas '
                . 'ele ela eles elas nos nosso nossa isso isto esse essa esses essas este esta estes estas aquilo aquele aquela '
                . 'ser sou e eh sao foi era sera estar esta estou estao ter tem tenho temos tinha ha haver fazer faz fiz '
                . 'pode posso podem consigo consegue conseguir quero queria gostaria preciso precisa '
                . 'ja nao sim mais menos muito muita pouco tambem so apenas ainda ate la aqui ai bem mal '
                . 'oi ola bom boa dia tarde noite obrigado obrigada favor pf';
            $set = [];
            foreach (explode(' ', $words) as $w) {
                if ($w !== '') {
                    $set[$w] = true;
                }
            }
        }
        return $set;
    }
}
