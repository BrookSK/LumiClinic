# Base de conhecimento do assistente de suporte

Esta pasta contém os manuais em Markdown que o assistente de suporte do LumiClinic lê para responder dúvidas da equipe da clínica. O texto é escrito para quem usa o sistema no dia a dia (recepção, profissionais, financeiro, dono da clínica), em português do Brasil, com acentos.

Este arquivo (README.md) é só para quem mantém a base. Ele **não** é lido pelo assistente e é ignorado pelo script de checagem.

## Arquivos e numeração
| Arquivo | Assunto |
|---|---|
| 00-indice.md | Índice geral, glossário, mapa dos módulos e limitações conhecidas |
| 01 a 06 | Primeiros passos, dashboard, agenda, pacientes, prontuário, serviços e profissionais |
| 07 a 15 | Financeiro, estoque, marketing e WhatsApp, relatórios, configurações e permissões, assinatura, portal do paciente, LGPD e segurança, solução de problemas |
| 90-quando-falar-com-a-equipe.md | O que fazer quando o assistente não resolve e quais dados reunir |
| 99-faq-resolucoes.md | Gerenciado por máquina: resoluções aprovadas de chamados. Não edite à mão |

Novos assuntos usam o próximo número livre entre 16 e 89. Nomes em minúsculas, sem acento, separados por hífen.

## Formato de cada arquivo
1. **Front matter** na primeira linha do arquivo, com `title:` e `routes:` (lista de URLs principais do assunto):
   ```
   ---
   title: Nome do assunto
   routes:
     - /exemplo
     - /exemplo/outra-tela
   ---
   ```
2. Um título `# ` e várias seções `## `. O script exige pelo menos uma seção `## ` (menos no 99).
3. Cada tela documentada segue o modelo: **Onde fica** (caminho no menu e URL), **passo a passo**, **tabela de campos** (campo, obrigatório, observação), **botões** com o texto exato da tela, **erros** com a mensagem exata, e **permissão**.
4. A permissão fica logo abaixo do título da seção, em um comentário: `<!-- perm: finance.sales.read (ver), finance.sales.create (criar) -->`. Use apenas códigos que existem nas migrações de permissões (database/migrations). Para tela sem permissão: `<!-- perm: nenhuma -->`.
5. Todo arquivo termina com **Perguntas frequentes** e, quando faz sentido, **Limitações conhecidas** e **Mapa de rotas** (lista de URLs).
6. Textos de tela entre aspas ou em negrito devem ser copiados **exatamente** como aparecem nas views, com acentos.

## Como adicionar uma tela ou módulo
1. Abra o controller, a view (app/Views/...) e a rota (routes/web.php) da tela. Não escreva de memória.
2. Anote: caminho no menu (app/Views/layout/app.php), campos, botões, filtros, mensagens de erro e sucesso (controllers e services), permissões exigidas.
3. Adicione uma seção `## ` no arquivo do módulo (ou crie um arquivo novo com o próximo número) e acrescente a nova URL em `routes:`.
4. Inclua pelo menos 3 perguntas frequentes realistas, escritas como o usuário pergunta.
5. Atualize o 00-indice.md se criou um arquivo novo.
6. Rode a checagem (abaixo).

## Como rodar a checagem
```
php bin/support_kb_check.php
```
O script percorre docs/knowledge-base/*.md (exceto README.md), imprime o total de arquivos, caracteres e uma estimativa de tokens, e sai com código 1 listando cada problema (arquivo e linha) se encontrar termos proibidos, padrões sensíveis, front matter ausente, falta de `routes:` ou ausência de seção `## `. Sem problemas imprime "OK" e sai com 0. Rode antes de cada commit que altere esta pasta.

## O que nunca escrever (lista de proibições)
**NUNCA ESCREVER nesta pasta (o script bloqueia).** A base é lida pelo assistente que conversa com a clínica, então não pode vazar nada de plataforma nem segredos:
- Qualquer referência à área de administração da plataforma: o caminho que começa com barra, sys, barra; as palavras "super admin" e "superadmin"; a função de entrar como outro usuário ("logar como", "impersonat...").
- Nomes de fornecedores ou componentes de infraestrutura usados nos bastidores (por exemplo o servidor de WhatsApp e o provedor de IA) e os nomes de campos de credenciais ("api_key", "apikey"). Se for preciso citar, diga "chave de acesso" ou "conexão do WhatsApp".
- Valores reais de chaves, tokens, senhas, números de cartão ou segredos de qualquer tipo.
- E-mails reais (use apenas domínios de exemplo, como example.com), CPF, CNPJ e telefones reais.
- Dados de pacientes ou de clínicas reais.
- O caractere travessão (em dash). Use vírgula, ponto ou hífen.
- Descrições de telas que a clínica não enxerga (somente da plataforma). Se um recurso não aparece no menu da clínica, diga apenas que "não está disponível no menu da clínica" e oriente a falar com o suporte.
- Culpar o sistema ("bug"). Escreva limitações em tom neutro, na seção Limitações conhecidas.

## Resoluções de chamados (99-faq-resolucoes.md)
Quando um chamado de suporte é resolvido, a solução aprovada é gravada no sistema e exportada para o arquivo 99-faq-resolucoes.md. O assistente carrega esse arquivo junto com os demais. Mantenha cada entrada curta: pergunta do usuário, causa e passo a passo da solução, sem dados de pessoas. Se uma resolução vale para todos, copie o conteúdo para o arquivo do módulo correspondente e remova a entrada do 99.

### Fluxo de revisão das resoluções
Chamados resolvidos geram um rascunho de FAQ anonimizado que um super admin revisa em `/sys/support/kb`. Só entradas aprovadas valem para o assistente, e o texto bruto da resolução nunca é exibido às clínicas. As entradas aprovadas são lidas direto do banco; o arquivo 99-faq-resolucoes.md (exportado em `/sys/support/kb/export.md`) serve para versionar o histórico, e a base evita duplicar uma pergunta que esteja nos dois lugares. Na revisão, confira à mão: nomes citados, detalhes de uma clínica específica, prazos prometidos e telas que a clínica não vê. Veja o fluxo completo em docs/support-chatbot.md.
