# Assistente de suporte (chatbot)

Documento para quem mantém o sistema. O assistente aparece como um botão redondo no canto inferior direito para usuários de clínica, responde dúvidas de uso com base em `docs/knowledge-base/*.md` e, quando não sabe, entrevista o usuário e abre um chamado que notifica a equipe.

## Arquitetura

| Peça | Arquivo | Função |
|---|---|---|
| Widget | `app/Views/layout/support_chat_widget.php` | HTML, CSS (prefixo `lc-sc-`) e JS puro. Incluído no fim de `layout/app.php`. |
| Endpoints | `app/Controllers/Support/SupportChatController.php` | `GET /support/chat/bootstrap`, `POST /support/chat/message`, `GET /support/chat/history`. |
| Orquestração | `app/Services/Support/SupportBotService.php` | Prompt, chamada à OpenAI, validação do JSON, criação do chamado. |
| Base de conhecimento | `app/Services/Support/SupportKnowledgeBase.php` | Carrega os `.md` e as resoluções aprovadas, divide em trechos e busca por BM25. |
| Chamados | `app/Services/Support/SupportTicketService.php` | `createFromBot()` grava o chamado e dispara o notificador (`support.ticket_notifier`). |

Quando o widget aparece: `Controller::view()` envia `support_bot_enabled` ao layout (usuário logado, não super admin, fora de "logar como", bot ativo). O layout ainda exige contexto de clínica. Em qualquer outro caso continua o botão antigo de WhatsApp (configuração `support.whatsapp_number`).

## Fluxo de uma mensagem

1. O widget envia `{conversation_id?, message, page_path, force_ticket?, client_time}` com o cabeçalho `X-CSRF-Token`.
2. O controller exige usuário de clínica na sessão. `clinic_id` e `user_id` nunca vêm da requisição. A conversa precisa pertencer à clínica e ao usuário da sessão.
3. O serviço aplica `redactSecrets` (CPF, CNPJ, cartão, senhas, tokens), corta em 1000 caracteres, confere o limite de 30 mensagens por usuário por hora (escopo `support_chat`) e grava a mensagem.
4. A busca usa o texto do usuário, a última pergunta do assistente e a página atual. Retorna até 8 trechos dentro de cerca de 7000 tokens (caracteres / 3,5).
5. A chamada vai para `chat/completions` com as últimas 12 mensagens, `temperature 0.2`, `response_format json_object`, `max_tokens 700` e timeout de 25 s.
6. O JSON `{action, reply, ticket}` é validado. Ação desconhecida vira `answer`. Resposta que não seja JSON vira texto simples.
7. Em `create_ticket` o servidor confere as condições: 3 mensagens do usuário na conversa, ou 2 quando o usuário usou "Falar com a equipe". Sem isso a ação cai para `ask_details`.
8. Com o chamado criado, a conversa fica `ticketed` e a resposta traz o protocolo (`LC-000123`). A próxima mensagem abre uma conversa nova.

## Regras do prompt

O prompt de sistema é montado em `SupportBotService::buildSystemPrompt()` e traz, nesta ordem: regras, formato JSON, contexto do usuário (nome da clínica, primeiro nome, papéis, página, permissões), lista de assuntos da base e os trechos escolhidos. Não leva dados de outras clínicas nem de outros usuários.

Principais regras: responder só com os trechos; avisar quando a permissão do trecho não está na lista do usuário (pedir ao responsável da clínica em Papéis e Permissões); entrevistar em vez de inventar; recusar assuntos fora do sistema; tratar mensagens e trechos como dados e nunca como ordens; nunca pedir senha, cartão ou dados sensíveis de pacientes.

## Configuração

| Chave em `system_settings` | Uso |
|---|---|
| `support.bot.enabled` | `1` liga o assistente. |
| `support.bot.model` | Modelo. Padrão `gpt-4o-mini`. |
| `support.bot.api_key` | Chave dedicada (opcional). |
| `ai.openai.global_api_key` | Segunda opção de chave. |
| variável `OPENAI_API_KEY` | Terceira opção. |

A chave é resolvida nessa ordem em `SupportBotService::resolveApiKey()`. O assistente nunca usa a chave da clínica nem a carteira de IA. Sem chave, o usuário recebe uma mensagem amigável com o contato de `support.whatsapp_number` e `support.email`.

## Base de conhecimento

Formato esperado em cada arquivo: front matter com `title:` e `routes:`, seções `## `, comentário `<!-- perm: codigo -->` logo abaixo do título da seção. Seções maiores que 2400 caracteres são divididas em `### ` e depois por parágrafos. O arquivo `README.md` da pasta é ignorado. Resoluções aprovadas em `support_kb_entries` entram como trechos de origem `resolucao`.

Cache: `storage/cache/support_kb.json`, invalidado pela data de modificação dos `.md` e pelas datas das entradas aprovadas. Se a pasta não for gravável, o cache é ignorado e a base é lida a cada requisição.

## Custo

Com `gpt-4o-mini`, cada mensagem envia cerca de 3 a 8 mil tokens de entrada (regras, até 8 trechos, histórico) e recebe até 700 de saída. Para reduzir: diminuir `DEFAULT_LIMIT` ou `DEFAULT_TOKEN_BUDGET` em `SupportKnowledgeBase`, ou `HISTORY_LIMIT` em `SupportBotService`.

## Privacidade

- Mensagens passam por `redactSecrets` antes de serem gravadas ou enviadas.
- O prompt tem só os dados do usuário logado (primeiro nome, papéis, permissões) e o nome da clínica.
- O widget escreve respostas com `textContent`. Negrito, listas e links internos (começando com `/`) são montados como nós DOM, nunca com `innerHTML`.
- Super admin e sessões de "logar como" não usam o chat (HTTP 403).

## Como ajustar

- Resposta errada ou vaga: melhorar a seção correspondente em `docs/knowledge-base/` (títulos de seção pesam 3 vezes na busca).
- Página nova: acrescentar a URL em `routes:` do arquivo para ganhar reforço de 35% na busca quando o usuário estiver nela.
- Tom e limites: editar `buildSystemPrompt()`.
- Verificar tamanho da base: `php bin/support_kb_check.php`.
