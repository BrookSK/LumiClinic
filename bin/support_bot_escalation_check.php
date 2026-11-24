<?php

declare(strict_types=1);

/**
 * Valida as regras de escalonamento do assistente de suporte para a equipe (SupportBotService),
 * sem banco de dados e sem OpenAI: usa apenas os métodos estáticos puros.
 *
 * Uso: php bin/support_bot_escalation_check.php
 *
 * Cobre a conversa em que o usuário clica em "Não, falar com a equipe", recebe a triagem e depois escreve
 * "ja verifiquei, não consegui ainda. quero uma reunião para me ensinar": o chamado precisa abrir.
 * Sai com código 1 se algum caso falhar.
 */

require dirname(__DIR__) . '/app/Services/Support/SupportBotService.php';

use App\Services\Support\SupportBotService as Bot;

$fails = 0;
$check = static function (string $name, bool $got, bool $want) use (&$fails): void {
    $ok = $got === $want;
    if (!$ok) {
        $fails++;
    }
    echo ($ok ? 'OK   ' : 'FAIL ') . $name . ' (esperado ' . ($want ? 'true' : 'false') . ', obtido ' . ($got ? 'true' : 'false') . ")\n";
};

$screenshot = 'ja verifiquei, não consegui ainda. quero uma reunião para me ensinar';

// wantsMeeting
$check('wantsMeeting: mensagem da conversa do print', Bot::wantsMeeting($screenshot), true);
$check('wantsMeeting: "quero treinamento"', Bot::wantsMeeting('quero treinamento'), true);
$check('wantsMeeting: "Gostaria de marcar uma reuniao"', Bot::wantsMeeting('Gostaria de marcar uma reuniao'), true);
$check('wantsMeeting: "alguém da equipe pode me ensinar?"', Bot::wantsMeeting('alguém da equipe pode me ensinar?'), true);
$check('wantsMeeting: "como agendo um paciente?"', Bot::wantsMeeting('como agendo um paciente?'), false);
$check('wantsMeeting: "pode me ensinar a cadastrar um paciente?"', Bot::wantsMeeting('pode me ensinar a cadastrar um paciente?'), false);
$check('wantsMeeting: "agendar uma ligação de retorno para o paciente"', Bot::wantsMeeting('agendar uma ligação de retorno para o paciente'), false);
$check('wantsMeeting: "quero agendar uma reunião com vocês"', Bot::wantsMeeting('quero agendar uma reunião com vocês'), true);
$check('wantsMeeting: "quero uma reunião para me ensinar a cadastrar paciente"', Bot::wantsMeeting('quero uma reunião para me ensinar a cadastrar paciente'), true);
// Dúvidas de uso com vocabulário de reunião/treinamento: o assistente responde, não abre chamado.
foreach ([
    'quero agendar uma reunião da equipe na agenda, como faço?',
    'como bloqueio a agenda para fazer uma reunião?',
    'preciso marcar reunião de equipe na agenda',
    'tem como fazer chamada de vídeo com o paciente?',
    'o profissional precisa ter treinamento cadastrado?',
    'quero cadastrar o treinamento do funcionário',
] as $q) {
    $check('wantsMeeting: "' . $q . '"', Bot::wantsMeeting($q), false);
}

// stillNeedsHelp
$check('stillNeedsHelp: "ja verifiquei, não consegui ainda"', Bot::stillNeedsHelp('ja verifiquei, não consegui ainda'), true);
$check('stillNeedsHelp: "não funcionou"', Bot::stillNeedsHelp('não funcionou'), true);
$check('stillNeedsHelp: "quero a equipe mesmo"', Bot::stillNeedsHelp('quero a equipe mesmo'), true);
$check('stillNeedsHelp: "continua dando erro"', Bot::stillNeedsHelp('continua dando erro'), true);
$check('stillNeedsHelp: "pode abrir um chamado"', Bot::stillNeedsHelp('pode abrir um chamado'), true);
$check('stillNeedsHelp: "obrigado, deu certo"', Bot::stillNeedsHelp('obrigado, deu certo'), false);
// Respostas às perguntas da triagem não contam como insistência.
$check('stillNeedsHelp: "ainda não"', Bot::stillNeedsHelp('ainda não'), false);
$check('stillNeedsHelp: "aparece a mensagem suporte indisponível"', Bot::stillNeedsHelp('aparece a mensagem suporte indisponível'), false);
$check('stillNeedsHelp: "não deu erro nenhum"', Bot::stillNeedsHelp('não deu erro nenhum'), false);

// wantsTeam (mensagem inteira)
$check('wantsTeam: "Não, falar com a equipe"', Bot::wantsTeam('Não, falar com a equipe'), true);
$check('wantsTeam: "quero falar com alguém da equipe"', Bot::wantsTeam('quero falar com alguém da equipe'), true);
$check('wantsTeam: "contato da pessoa no cadastro"', Bot::wantsTeam('contato da pessoa no cadastro'), false);

// decideTicket(mode, modelAction, userTurns, insistedAgain, meetingRequested)
$insisted = Bot::stillNeedsHelp($screenshot);
$meeting = Bot::wantsMeeting($screenshot);
$check('decideTicket: conversa do print (modo chamado, modelo pediu detalhes)', Bot::decideTicket(Bot::MODE_TICKET, 'ask_details', 1, $insisted, $meeting), true);
$check('decideTicket: modo chamado, usuário insiste sem reunião', Bot::decideTicket(Bot::MODE_TICKET, 'ask_details', 1, true, false), true);
$check('decideTicket: primeiro clique no botão (triagem, sem reunião)', Bot::decideTicket(Bot::MODE_TRIAGE, 'ask_details', 0, false, false), false);
$check('decideTicket: triagem com modelo pedindo create_ticket', Bot::decideTicket(Bot::MODE_TRIAGE, 'create_ticket', 3, false, false), false);
$check('decideTicket: triagem com pedido de reunião', Bot::decideTicket(Bot::MODE_TRIAGE, 'ask_details', 1, false, true), true);
$check('decideTicket: normal, modelo create_ticket, 1 resposta', Bot::decideTicket(Bot::MODE_NORMAL, 'create_ticket', 1, false, false), false);
$check('decideTicket: normal, modelo create_ticket, 2 respostas', Bot::decideTicket(Bot::MODE_NORMAL, 'create_ticket', 2, false, false), true);
$check('decideTicket: modo chamado, resposta sem insistência e modelo perguntando', Bot::decideTicket(Bot::MODE_TICKET, 'ask_details', 2, false, false), false);
$check('decideTicket: modo chamado, insistiu mas nada descrito (0 respostas)', Bot::decideTicket(Bot::MODE_TICKET, 'ask_details', 0, true, false), false);

// analyzeConversation: histórico já com a mensagem atual gravada (como em handleMessage).
$u = static fn(string $s): array => ['role' => 'user', 'content' => $s];
$a = static fn(string $s): array => ['role' => 'assistant', 'content' => $s];
$marker = ['role' => 'system', 'content' => Bot::FORCE_MARKER];

// Conversa do print: pergunta, clique no botão, triagem, insistência com pedido de reunião.
$h = [$u('como faço para lançar um procedimento?'), $a('Vá em Financeiro...'), $u(Bot::ESCALATE_TEXT), $marker, $a('Você já tentou salvar?'), $u($screenshot)];
$s = Bot::analyzeConversation($h, $screenshot, false);
$check('analyze: print, modo chamado', $s['mode'] === Bot::MODE_TICKET, true);
$check('analyze: print, insistiu de novo', $s['insisted_again'], true);
$check('analyze: print, não grava marcador de novo', $s['insert_marker'], false);
$check('analyze: print, botão fora das respostas reais', count($s['real_user_texts']) === 2, true);
$check('analyze+decide: print abre chamado', Bot::decideTicket($s['mode'], 'ask_details', count($s['real_user_texts']), $s['insisted_again'], $s['meeting_requested']), true);

// Conversa do print com o fluxo mínimo: só o clique no botão, a triagem e a mensagem de insistência.
$h = [$u(Bot::ESCALATE_TEXT), $marker, $a('Me conte o que você queria fazer.'), $u($screenshot)];
$s = Bot::analyzeConversation($h, $screenshot, false);
$check('analyze+decide: botão, triagem e insistência abre chamado', Bot::decideTicket($s['mode'], 'ask_details', count($s['real_user_texts']), $s['insisted_again'], $s['meeting_requested']), true);

// Primeiro clique no botão: grava o marcador e entra em triagem, sem chamado.
$h = [$u('como emito nota?'), $a('Vá em Financeiro...'), $u(Bot::ESCALATE_TEXT)];
$s = Bot::analyzeConversation($h, Bot::ESCALATE_TEXT, true);
$check('analyze: primeiro clique grava marcador', $s['insert_marker'], true);
$check('analyze: primeiro clique em triagem', $s['mode'] === Bot::MODE_TRIAGE, true);
$check('analyze: primeiro clique não é insistência', $s['insisted_again'], false);
$check('analyze+decide: primeiro clique não abre', Bot::decideTicket($s['mode'], 'create_ticket', count($s['real_user_texts']), $s['insisted_again'], $s['meeting_requested']), false);

// Pedido de reunião na primeira mensagem: grava o marcador e abre.
$h = [$u('quero uma reunião para me ensinar o sistema')];
$s = Bot::analyzeConversation($h, 'quero uma reunião para me ensinar o sistema', false);
$check('analyze: reunião grava marcador', $s['insert_marker'], true);
$check('analyze+decide: reunião na primeira mensagem abre', Bot::decideTicket($s['mode'], 'answer', count($s['real_user_texts']), $s['insisted_again'], $s['meeting_requested']), true);

// Dúvida de agenda com "reunião": modo normal, sem marcador, sem chamado.
$q = 'como bloqueio a agenda para fazer uma reunião?';
$s = Bot::analyzeConversation([$u($q)], $q, false);
$check('analyze: dúvida de agenda fica no modo normal', $s['mode'] === Bot::MODE_NORMAL && !$s['insert_marker'], true);
$check('analyze+decide: dúvida de agenda não abre', Bot::decideTicket($s['mode'], 'answer', 1, $s['insisted_again'], $s['meeting_requested']), false);

// Só pedidos de equipe, nada descrito: não abre, o assistente pergunta antes.
$h = [$u('quero falar com a equipe'), $marker, $a('Claro. O que está acontecendo?'), $u(Bot::ESCALATE_TEXT)];
$s = Bot::analyzeConversation($h, Bot::ESCALATE_TEXT, true);
$check('analyze+decide: só pedidos de equipe não abre', Bot::decideTicket($s['mode'], 'ask_details', count($s['real_user_texts']), $s['insisted_again'], $s['meeting_requested']), false);

// Resposta à pergunta da triagem não é insistência.
$h = [$u('erro ao salvar'), $u(Bot::ESCALATE_TEXT), $marker, $a('Você já salvou?'), $u('ainda não')];
$s = Bot::analyzeConversation($h, 'ainda não', false);
$check('analyze: "ainda não" depois da triagem não é insistência', $s['insisted_again'], false);

// completeTicketData
$t = Bot::completeTicketData(['title' => '', 'summary' => '', 'category' => '', 'severity' => ''], ['erro ao salvar', $screenshot], true);
$check('completeTicketData: título da última descrição', $t['title'] === $screenshot, true);
$check('completeTicketData: reunião vira "duvida"', $t['category'] === 'duvida', true);
$check('completeTicketData: resumo cita a reunião', str_contains($t['summary'], 'reunião/treinamento') && str_contains($t['summary'], 'erro ao salvar'), true);
$check('completeTicketData: gravidade padrão "media"', $t['severity'] === 'media', true);
$t = Bot::completeTicketData(['category' => ''], ['como faço para emitir nota?'], false);
$check('completeTicketData: "como faço" vira "duvida"', $t['category'] === 'duvida', true);
$t = Bot::completeTicketData(['category' => ''], ['o sistema travou na tela de vendas'], false);
$check('completeTicketData: sem reunião nem "como" vira "outro"', $t['category'] === 'outro', true);
$t = Bot::completeTicketData([], [], false);
$check('completeTicketData: sem descrição usa título padrão', $t['title'] === 'Pedido de atendimento da equipe', true);
$t = Bot::completeTicketData(['title' => 'X', 'summary' => 'Y', 'category' => 'bug', 'severity' => 'alta'], ['abc'], false);
$check('completeTicketData: mantém o que o modelo preencheu', $t === ['title' => 'X', 'summary' => 'Y', 'category' => 'bug', 'severity' => 'alta'], true);

// forcedTicketReply
$check('forcedTicketReply: reunião cita combinar a reunião', str_contains(Bot::forcedTicketReply(true), 'combinar a reunião'), true);

if ($fails > 0) {
    echo "\n$fails caso(s) falharam.\n";
    exit(1);
}
echo "\nOK: todos os casos passaram.\n";
