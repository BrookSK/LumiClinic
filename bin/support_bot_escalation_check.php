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

// stillNeedsHelp
$check('stillNeedsHelp: "ja verifiquei, não consegui ainda"', Bot::stillNeedsHelp('ja verifiquei, não consegui ainda'), true);
$check('stillNeedsHelp: "não funcionou"', Bot::stillNeedsHelp('não funcionou'), true);
$check('stillNeedsHelp: "quero a equipe mesmo"', Bot::stillNeedsHelp('quero a equipe mesmo'), true);
$check('stillNeedsHelp: "obrigado, deu certo"', Bot::stillNeedsHelp('obrigado, deu certo'), false);

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

if ($fails > 0) {
    echo "\n$fails caso(s) falharam.\n";
    exit(1);
}
echo "\nOK: todos os casos passaram.\n";
