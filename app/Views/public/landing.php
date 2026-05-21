<?php
$plans = $plans ?? [];
$e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

// Mapear planos do banco para exibição na landing
$planDisplay = [];
foreach ($plans as $p) {
    $code = $p['code'] ?? '';
    $limits = json_decode($p['limits_json'] ?? '{}', true) ?: [];
    $planDisplay[$code] = [
        'name' => $p['name'] ?? $code,
        'price_cents' => (int)($p['price_cents'] ?? 0),
        'trial_days' => (int)($p['trial_days'] ?? 0),
        'limits' => $limits,
    ];
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>LumiClinic — Gestão Clínica com Inteligência Artificial Nativa</title>
    <meta name="description" content="A LumiClinic devolve o seu tempo. Agenda, prontuário, financeiro e marketing integrados em uma plataforma criada por quem viveu cada dor da gestão clínica." />
    <link rel="icon" href="/icone_1.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="/assets/css/landing.css?v=5" />
</head>
<body>

<!-- HEADER -->
<header class="lp-header">
    <div class="container">
        <a href="/" class="lp-logo">Lumi <span>Clinic</span></a>
        <nav class="lp-nav">
            <a href="#funcionalidades">Funcionalidades</a>
            <a href="#planos">Planos</a>
            <a href="#para-quem">Para quem é</a>
            <a href="#faq">Fale conosco</a>
        </nav>
        <a href="/criar-conta" class="lp-btn-outline">Começar agora</a>
        <button class="lp-menu-toggle" aria-label="Menu">☰</button>
    </div>
</header>

<!-- HERO -->
<section class="lp-hero">
    <div class="container">
        <div class="lp-hero-text">
            <div class="lp-hero-tag">Gestão clínica com inteligência artificial nativa</div>
            <h1>
                <span class="gold">Sua clínica é<br>excelente.</span><br>
                <span class="light">Sua gestão pode<br>ser também.</span>
            </h1>
            <p class="lp-hero-desc">
                A LumiClinic devolve o seu tempo. Agenda, prontuário, financeiro
                e marketing integrados em uma plataforma criada por quem
                viveu cada dor da gestão clínica — e decidiu fazer diferente.
            </p>
            <div class="lp-hero-actions">
                <a href="/criar-conta" class="lp-btn-gold">Testar agora →</a>
                <a href="#funcionalidades" class="lp-btn-ghost">Ver como funciona ↓</a>
            </div>
            <p class="lp-hero-note">7 dias grátis · Migração de dados inclusa · Suporte humano desde o primeiro dia</p>
        </div>
        <div class="lp-hero-img">
            <img src="/Imagem L com os mockups inicial - site lumiclinic.zip.png" alt="LumiClinic - Plataforma de gestão clínica" />
        </div>
    </div>
</section>

<!-- PAIN POINTS -->
<section class="lp-pain">
    <div class="container">
        <h2>Ainda parece <em>familiar</em>?</h2>
        <div class="lp-pain-grid">
            <div class="lp-pain-card">
                <h4>"A agenda virou bagunça"</h4>
                <p>Profissionais diferentes, horários sobrepostos, confirmações que não chegam. O dia começa sempre no caos.</p>
            </div>
            <div class="lp-pain-card">
                <h4>"O financeiro é um mistério todo mês"</h4>
                <p>Você fatura, mas não sabe exatamente quanto sobra. As taxas, os pacotes, as sessões — tudo na cabeça ou em planilha.</p>
            </div>
            <div class="lp-pain-card">
                <h4>"O prontuário come seu tempo"</h4>
                <p>Digitar cada procedimento rouba minutos que deveriam ser de atendimento. No final do dia, você digitou mais do que cuidou.</p>
            </div>
            <div class="lp-pain-card">
                <h4>"Paciente sumiu? Você só descobre depois"</h4>
                <p>Sem retorno automático, sem régua de relacionamento. A paciente que precisava de reforço foi para a concorrência.</p>
            </div>
            <div class="lp-pain-card">
                <h4>"Estoque? Torço para ter o produto no dia"</h4>
                <p>Baixa automática? Alerta de vencimento? Controle de lote? A maioria improvisa até o último momento.</p>
            </div>
            <div class="lp-pain-card">
                <h4>"Crescer parece arriscado demais"</h4>
                <p>Cada novo profissional, cada nova sala, multiplica o caos. Crescer deveria ser bom, mas na prática dói.</p>
            </div>
        </div>
        <p class="lp-pain-footer">Se você se reconheceu em qualquer um desses cenários, não é falta de competência. É falta de ferramenta certa.</p>
    </div>
</section>

<!-- PLATFORM FEATURES -->
<section class="lp-platform" id="funcionalidades">
    <div class="container">
        <div class="lp-platform-tag">A Plataforma</div>
        <h2>Uma plataforma. <em>Toda a sua gestão.</em></h2>
        <p class="lp-platform-desc">
            A LumiClinic reúne em um único lugar tudo o que sua clínica precisa para funcionar
            com excelência — sem integrações frágeis, sem dados espalhados, sem
            complexidade desnecessária.
        </p>
        <div class="lp-platform-content">
            <div class="lp-platform-tabs">
                <button class="lp-platform-tab active" data-tab="agenda">Agenda inteligente</button>
                <button class="lp-platform-tab" data-tab="prontuario">Prontuário com IA</button>
                <button class="lp-platform-tab" data-tab="financeiro">Financeiro completo</button>
                <button class="lp-platform-tab" data-tab="marketing">Marketing automatizado</button>
                <button class="lp-platform-tab" data-tab="estoque">Estoque sem surpresas</button>
                <button class="lp-platform-tab" data-tab="portal">Portal da paciente</button>
            </div>
            <div class="lp-platform-panel">
                <div class="lp-platform-preview">
                    <div class="lp-platform-preview-icon">🖼</div>
                    <span>Preview da tela</span>
                </div>
                <div class="lp-platform-detail" id="panel-agenda">
                    <h3>Agenda inteligente</h3>
                    <p>Multiespecialistas, múltiplas salas, durações e valores distintos por serviço.
                    Lembretes automáticos via WhatsApp, confirmação de presença e monitoramento
                    de atrasos — tudo sem que você precise lembrar de nada.</p>
                    <div class="lp-platform-benefit-label">Benefício</div>
                    <div class="lp-platform-benefit">Menos faltas, mais previsibilidade no seu dia.</div>
                </div>
                <div class="lp-platform-detail" id="panel-prontuario" style="display:none">
                    <h3>Prontuário com IA</h3>
                    <p>Transcrição automática da consulta em tempo real. Fale enquanto atende,
                    o sistema registra. Modelos personalizáveis, histórico completo e
                    assinatura digital integrada.</p>
                    <div class="lp-platform-benefit-label">Benefício</div>
                    <div class="lp-platform-benefit">Mais tempo com a paciente, menos tempo digitando.</div>
                </div>
                <div class="lp-platform-detail" id="panel-financeiro" style="display:none">
                    <h3>Financeiro completo</h3>
                    <p>Controle de vendas, pacotes, sessões, comissões por profissional,
                    contas a pagar e fluxo de caixa. Relatórios automáticos que mostram
                    exatamente onde está seu lucro.</p>
                    <div class="lp-platform-benefit-label">Benefício</div>
                    <div class="lp-platform-benefit">Clareza financeira sem precisar de planilha.</div>
                </div>
                <div class="lp-platform-detail" id="panel-marketing" style="display:none">
                    <h3>Marketing automatizado</h3>
                    <p>Réguas de relacionamento, campanhas segmentadas, lembretes de retorno
                    e mensagens automáticas via WhatsApp. Sua paciente nunca mais vai
                    esquecer de você.</p>
                    <div class="lp-platform-benefit-label">Benefício</div>
                    <div class="lp-platform-benefit">Pacientes voltam sem que você precise correr atrás.</div>
                </div>
                <div class="lp-platform-detail" id="panel-estoque" style="display:none">
                    <h3>Estoque sem surpresas</h3>
                    <p>Baixa automática por procedimento, alertas de vencimento, controle de lote
                    e relatórios de consumo. Saiba exatamente o que tem, o que precisa
                    e quando repor.</p>
                    <div class="lp-platform-benefit-label">Benefício</div>
                    <div class="lp-platform-benefit">Nunca mais cancele um procedimento por falta de material.</div>
                </div>
                <div class="lp-platform-detail" id="panel-portal" style="display:none">
                    <h3>Portal da paciente</h3>
                    <p>Agendamento online, acesso a documentos, fotos de evolução,
                    anamnese digital e comunicação direta. Sua paciente se sente
                    cuidada mesmo fora da clínica.</p>
                    <div class="lp-platform-benefit-label">Benefício</div>
                    <div class="lp-platform-benefit">Experiência premium que fideliza e diferencia.</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FOUNDER -->
<section class="lp-founder">
    <div class="container">
        <h2>Criada por quem conhece <em>cada dor</em> que você sente</h2>
        <div class="lp-founder-content">
            <div class="lp-founder-photo">
                <img src="/Imagem da mulher - site lumiclinic.png" alt="Dra. Letícia Brito - Fundadora da LumiClinic" />
                <div class="lp-founder-icons">
                    <span class="lp-founder-icon">✦</span>
                    <span class="lp-founder-icon">♡</span>
                    <span class="lp-founder-icon">✓</span>
                </div>
            </div>
            <div class="lp-founder-info">
                <h4>Dra. Letícia Brito</h4>
                <p class="subtitle">Fundadora da LumiClinic · 9 anos de gestão clínica · Especialista em harmonização facial</p>

                <p>A maioria dos softwares de gestão foi construída por engenheiros que nunca pisaram em uma clínica estética. A LumiClinic foi diferente.</p>

                <p>A Dra. Letícia Brito passou nove anos na gestão real de clínicas estéticas — das noites resolvendo conflitos de agenda, dos meses tentando entender o financeiro em planilhas, das horas perdidas digitando prontuários enquanto pacientes esperavam.</p>

                <p>Quando não encontrou a ferramenta que precisava, decidiu construir. Cada funcionalidade da LumiClinic responde a uma dor que ela própria viveu. Cada decisão de produto passou pelo filtro de quem conhece o problema por dentro.</p>

                <p>Concorrentes podem copiar features. Não podem copiar nove anos de experiência.</p>

                <div class="lp-founder-quote">
                    <p>Eu não queria mais um software de gestão. Queria o software que eu precisava ter tido e que nenhum sistema me ofereceu. Então construí.</p>
                    <cite>— Dra. Letícia Brito, fundadora da LumiClinic.</cite>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- DIFFERENTIALS -->
<section class="lp-differentials">
    <div class="container">
        <div class="lp-differentials-grid">
            <div class="lp-differential">
                <h4>Inteligência artificial nativa</h4>
                <p>Não é um plugin adicionado depois. A IA foi projetada desde o início para estar em cada fluxo — da transcrição do prontuário às análises de performance da clínica.</p>
            </div>
            <div class="lp-differential">
                <h4>Especialização em estética e harmonização facial</h4>
                <p>Não somos um sistema genérico adaptado para clínicas. Somos uma plataforma construída para este mercado, com vocabulário, fluxos e inteligência ajustados à sua realidade.</p>
            </div>
            <div class="lp-differential">
                <h4>Migração sem trauma</h4>
                <p>Vem do Clinicorp? Importamos pacientes, agendamentos, histórico financeiro e procedimentos. Nossa equipe faz o trabalho pesado. Você só valida o resultado.</p>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="lp-testimonials">
    <div class="container">
        <h2>O que muda quando a gestão <em>para de<br>ser o problema</em></h2>
        <div class="lp-testimonials-grid">
            <div class="lp-testimonial-card">
                <div class="quote-icon">"</div>
                <p>Eu passava as noites atualizando planilha. Hoje o financeiro fecha sozinho e eu nem preciso pensar nisso.</p>
                <div class="author">Dra. Amanda F.</div>
                <div class="role">Clínica de harmonização facial · São Paulo</div>
            </div>
            <div class="lp-testimonial-card">
                <div class="quote-icon">"</div>
                <p>A transcrição automática do prontuário parece mágica. Falo enquanto atendo, o sistema registra. Simples assim.</p>
                <div class="author">Dra. Carolina M.</div>
                <div class="role">Dermato-esteticista · Belo Horizonte</div>
            </div>
            <div class="lp-testimonial-card">
                <div class="quote-icon">"</div>
                <p>Minhas pacientes elogiaram o portal antes de eu elogiar para elas. Isso diz tudo.</p>
                <div class="author">Renata O.</div>
                <div class="role">Gestora de clínica estética · Curitiba</div>
            </div>
        </div>
    </div>
</section>

<!-- FOR YOU -->
<section class="lp-foryou" id="para-quem">
    <div class="container">
        <h2>A LumiClinic foi feita <em>para você</em></h2>
        <div class="lp-foryou-grid">
            <div class="lp-foryou-card">
                <h4>Se você é dona de clínica estética ou de harmonização facial</h4>
                <p>e quer parar de gerenciar no improviso e começar a crescer com estratégia, a LumiClinic é sua plataforma.</p>
            </div>
            <div class="lp-foryou-card">
                <h4>Se você é médica ou profissional de saúde que abriu o próprio consultório</h4>
                <p>e descobriu que empreender exige mais do que técnica clínica, a LumiClinic cuida da gestão enquanto você cuida das suas pacientes.</p>
            </div>
            <div class="lp-foryou-card">
                <h4>Se você tem uma equipe que precisa de coordenação</h4>
                <p>com múltiplos profissionais, salas e serviços diferentes, a LumiClinic organiza tudo com controle por usuário e indicadores por profissional.</p>
            </div>
            <div class="lp-foryou-card">
                <h4>Se você está cansada de pagar por vários sistemas separados</h4>
                <p>que não conversam entre si, a LumiClinic substitui tudo em uma única plataforma integrada.</p>
            </div>
        </div>
        <p class="lp-foryou-footer">Não importa se você tem uma sala ou dez. A LumiClinic cresce com você.</p>
    </div>
</section>

<!-- PRICING -->
<section class="lp-pricing" id="planos">
    <div class="container">
        <div class="lp-pricing-header">
            <h2>Transparência total.<br><em>Sem taxas escondidas.</em></h2>
            <p>Escolha o plano ideal para o momento da sua clínica. Todos com suporte humano e acesso completo à plataforma.</p>
        </div>
        <div class="lp-pricing-grid">
            <!-- Essencial -->
            <div class="lp-pricing-card">
                <h3>Essencial</h3>
                <div class="lp-pricing-price">
                    <span class="currency">R$</span>
                    <span class="amount">97</span>
                    <span class="period">/mês</span>
                </div>
                <div class="lp-pricing-trial">7 dias grátis</div>
                <ul class="lp-pricing-features">
                    <li>Até 3 usuários</li>
                    <li>Até 500 pacientes</li>
                    <li>1 GB de armazenamento</li>
                    <li>Portal da paciente</li>
                    <li>Financeiro completo</li>
                    <li>Marketing automatizado</li>
                    <li>Suporte humano</li>
                </ul>
                <a href="/criar-conta" class="lp-btn-outline">Começar agora</a>
            </div>

            <!-- Profissional -->
            <div class="lp-pricing-card featured">
                <div class="lp-pricing-badge">Mais popular</div>
                <h3>Profissional</h3>
                <div class="lp-pricing-price">
                    <span class="currency">R$</span>
                    <span class="amount">197</span>
                    <span class="period">/mês</span>
                </div>
                <div class="lp-pricing-trial">14 dias grátis</div>
                <ul class="lp-pricing-features">
                    <li>Até 10 usuários</li>
                    <li>Até 3.000 pacientes</li>
                    <li>5 GB de armazenamento</li>
                    <li>Transcrição com IA — 60 min/mês</li>
                    <li>Portal da paciente</li>
                    <li>Financeiro completo</li>
                    <li>Marketing automatizado</li>
                    <li>Suporte humano</li>
                    <li>Migração de dados inclusa</li>
                </ul>
                <a href="/criar-conta" class="lp-btn-gold">Começar agora</a>
            </div>

            <!-- Clínica Plus -->
            <div class="lp-pricing-card">
                <h3>Clínica Plus</h3>
                <div class="lp-pricing-price">
                    <span class="currency">R$</span>
                    <span class="amount">297</span>
                    <span class="period">/mês</span>
                </div>
                <div class="lp-pricing-trial">14 dias grátis</div>
                <ul class="lp-pricing-features">
                    <li>Usuários <strong>ilimitados</strong></li>
                    <li>Pacientes <strong>ilimitados</strong></li>
                    <li>10 GB de armazenamento</li>
                    <li>Transcrição com IA — 300 min/mês</li>
                    <li>Portal da paciente</li>
                    <li>Financeiro completo</li>
                    <li>Marketing automatizado</li>
                    <li>Suporte humano prioritário</li>
                    <li>Migração de dados inclusa</li>
                </ul>
                <a href="#" class="lp-btn-outline">Falar com consultor</a>
            </div>
        </div>
        <p class="lp-pricing-footer">Todos os planos incluem agenda, prontuário, estoque, financeiro e portal da paciente. Sem contrato de fidelidade. Cancele quando quiser.</p>
    </div>
</section>

<!-- FAQ -->
<section class="lp-faq" id="faq">
    <div class="container">
        <h2>Perguntas <em>frequentes</em></h2>
        <div class="lp-faq-list">
            <div class="lp-faq-item open">
                <button class="lp-faq-question">
                    <span>Preciso instalar alguma coisa?</span>
                    <span class="icon">+</span>
                </button>
                <div class="lp-faq-answer">
                    <p>Não. A LumiClinic funciona diretamente no navegador, em qualquer dispositivo. Sem instalação, sem atualização manual.</p>
                </div>
            </div>
            <div class="lp-faq-item">
                <button class="lp-faq-question">
                    <span>Posso migrar meus dados do sistema atual?</span>
                    <span class="icon">+</span>
                </button>
                <div class="lp-faq-answer">
                    <p>Sim. Importamos pacientes, agendamentos, histórico financeiro e procedimentos de outros sistemas. Nossa equipe cuida de tudo.</p>
                </div>
            </div>
            <div class="lp-faq-item">
                <button class="lp-faq-question">
                    <span>A transcrição com IA funciona em português?</span>
                    <span class="icon">+</span>
                </button>
                <div class="lp-faq-answer">
                    <p>Sim. A transcrição foi treinada para português brasileiro, incluindo termos técnicos de estética e harmonização facial.</p>
                </div>
            </div>
            <div class="lp-faq-item">
                <button class="lp-faq-question">
                    <span>Meus dados ficam seguros?</span>
                    <span class="icon">+</span>
                </button>
                <div class="lp-faq-answer">
                    <p>Sim. Utilizamos criptografia de ponta a ponta, servidores no Brasil e estamos em conformidade com a LGPD. Seus dados são seus.</p>
                </div>
            </div>
            <div class="lp-faq-item">
                <button class="lp-faq-question">
                    <span>Posso trocar de plano depois?</span>
                    <span class="icon">+</span>
                </button>
                <div class="lp-faq-answer">
                    <p>Sim. Você pode fazer upgrade ou downgrade a qualquer momento, sem multa e sem burocracia.</p>
                </div>
            </div>
            <div class="lp-faq-item">
                <button class="lp-faq-question">
                    <span>Tem suporte em português?</span>
                    <span class="icon">+</span>
                </button>
                <div class="lp-faq-answer">
                    <p>Sim. Todo o suporte é em português, feito por humanos que entendem a rotina de uma clínica estética.</p>
                </div>
            </div>
            <div class="lp-faq-item">
                <button class="lp-faq-question">
                    <span>Funciona para clínica com mais de um profissional?</span>
                    <span class="icon">+</span>
                </button>
                <div class="lp-faq-answer">
                    <p>Sim. A LumiClinic foi projetada para clínicas com múltiplos profissionais, salas e especialidades. Cada um com seu acesso e indicadores.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA FINAL -->
<section class="lp-cta">
    <div class="container">
        <h2><span class="gold">Sua clínica merece gestão</span><br>à altura do seu atendimento.</h2>
        <p class="lp-cta-desc">
            Você já dominou o que é mais difícil: a técnica clínica. Agora é hora de ter uma
            plataforma que domina o resto por você.
        </p>
        <p class="lp-cta-sub">
            Comece com 7 dias gratuitos e descubra como é trabalhar com clareza, tranquilidade
            e tempo de sobra para o que realmente importa.
        </p>
        <div class="lp-cta-actions">
            <a href="/criar-conta" class="lp-btn-gold">Começar meu período gratuito →</a>
            <a href="#" class="lp-btn-ghost">Quero falar com alguém antes</a>
        </div>
        <p class="lp-cta-note">Sem cartão de crédito · Sem contrato · Suporte humano incluso</p>
    </div>
</section>

<!-- FOOTER -->
<footer class="lp-footer">
    <div class="container">
        <div class="lp-footer-grid">
            <div class="lp-footer-brand">
                <h3>Lumi <span>Clinic</span></h3>
                <p>Plataforma de gestão clínica com inteligência artificial nativa para clínicas de estética e harmonização facial.</p>
            </div>
            <div class="lp-footer-col">
                <h4>Produto</h4>
                <a href="#funcionalidades">Funcionalidades</a>
                <a href="#planos">Planos e preços</a>
                <a href="#">Novidades</a>
                <a href="#">Migração de dados</a>
            </div>
            <div class="lp-footer-col">
                <h4>Empresa</h4>
                <a href="#">Sobre a LumiClinic</a>
                <a href="#">Blog</a>
                <a href="#">Fale conosco</a>
                <a href="#">Trabalhe conosco</a>
            </div>
            <div class="lp-footer-col">
                <h4>Suporte</h4>
                <a href="#">Central de ajuda</a>
                <a href="#">Documentação</a>
                <a href="#">Status do sistema</a>
                <a href="#">Suporte via WhatsApp</a>
            </div>
        </div>
        <div class="lp-footer-bottom">
            <span>&copy; 2025 LumiClinic. Todos os direitos reservados.</span>
            <div class="lp-footer-legal">
                <a href="#">Política de privacidade</a>
                <a href="#">Termos de uso</a>
                <a href="#">LGPD</a>
            </div>
        </div>
    </div>
</footer>

<!-- SCRIPTS -->
<script>
// FAQ Accordion
document.querySelectorAll('.lp-faq-question').forEach(btn => {
    btn.addEventListener('click', () => {
        const item = btn.closest('.lp-faq-item');
        const wasOpen = item.classList.contains('open');
        document.querySelectorAll('.lp-faq-item').forEach(i => i.classList.remove('open'));
        if (!wasOpen) item.classList.add('open');
    });
});

// Platform Tabs
document.querySelectorAll('.lp-platform-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.lp-platform-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const target = tab.dataset.tab;
        document.querySelectorAll('.lp-platform-detail').forEach(p => p.style.display = 'none');
        const panel = document.getElementById('panel-' + target);
        if (panel) panel.style.display = 'block';
    });
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const target = document.querySelector(a.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>
</body>
</html>
