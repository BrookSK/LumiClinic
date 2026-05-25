<?php
$plans = $plans ?? [];
$e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LumiClinic — Gestão Clínica com Inteligência Artificial Nativa</title>
<meta name="description" content="A LumiClinic devolve o seu tempo. Agenda, prontuário, financeiro e marketing integrados em uma plataforma criada por quem viveu cada dor da gestão clínica.">
<link rel="icon" href="/icone_1.png" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        gold: '#d4a24c',
        'gold-light': '#e8bc6a',
        'gold-dark': '#b8882e',
        'bg-deep': '#050505',
        'bg-card': '#0b0b0b',
        'bg-elevated': '#111111',
        'border-gold': 'rgba(212,162,76,0.2)',
        'text-primary': '#f5f5f5',
        'text-secondary': '#9b9b9b',
      },
      fontFamily: {
        heading: ['Sora', 'sans-serif'],
        body: ['DM Sans', 'sans-serif'],
      },
    }
  }
}
</script>
<link rel="stylesheet" href="/assets/css/landing.css?v=11" />
</head>
<body class="bg-bg-deep">

<!-- ========== HEADER ========== -->
<header class="header-blur fixed top-0 left-0 right-0 z-50">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <nav class="flex items-center justify-between h-16">
      <a href="/" class="flex items-center gap-2">
        <img src="/Principal_1.png" alt="LumiClinic" style="height:32px;" />
      </a>
      <ul class="hidden md:flex items-center gap-8">
        <li><a href="#funcionalidades" class="nav-link">Funcionalidades</a></li>
        <li><a href="#planos" class="nav-link">Planos</a></li>
        <li><a href="#para-quem" class="nav-link">Para quem é</a></li>
        <li><a href="#faq" class="nav-link">FAQ</a></li>
      </ul>
      <div class="hidden md:flex items-center gap-3">
        <a href="/login" class="nav-link">Entrar</a>
        <a href="/criar-conta" class="btn-gold px-5 py-2 text-sm">Começar grátis</a>
      </div>
      <button class="md:hidden p-2" onclick="toggleMenu()" style="color:#9b9b9b;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor"><rect y="3" width="20" height="2" rx="1"/><rect y="9" width="20" height="2" rx="1"/><rect y="15" width="20" height="2" rx="1"/></svg>
      </button>
    </nav>
  </div>
  <div id="mobile-menu" class="flex-col gap-1 px-6 pb-4 border-t" style="border-color:var(--border-gold);">
    <a href="#funcionalidades" class="block py-3 text-sm" style="color:#9b9b9b;">Funcionalidades</a>
    <a href="#planos" class="block py-3 text-sm" style="color:#9b9b9b;">Planos</a>
    <a href="#para-quem" class="block py-3 text-sm" style="color:#9b9b9b;">Para quem é</a>
    <a href="#faq" class="block py-3 text-sm" style="color:#9b9b9b;">FAQ</a>
    <a href="/criar-conta" class="btn-gold block text-center px-5 py-2.5 text-sm mt-3">Começar grátis</a>
  </div>
</header>

<!-- ========== HERO ========== -->
<section class="relative min-h-screen flex items-center pt-16 overflow-hidden grid-bg">
  <div class="absolute inset-0" style="background: radial-gradient(ellipse 80% 60% at 65% 40%, rgba(212,162,76,0.08) 0%, transparent 65%);"></div>
  <div class="max-w-7xl mx-auto px-6 lg:px-8 w-full py-24 lg:py-32">
    <div class="grid lg:grid-cols-2 gap-16 lg:gap-12 items-center">
      <div class="reveal">
        <div class="label-badge mb-8">
          <span style="width:6px;height:6px;background:var(--gold);border-radius:50%;display:inline-block;animation:pulse 2s infinite;"></span>
          Gestão clínica com inteligência artificial nativa
        </div>
        <h1 style="font-family:'Sora',sans-serif; font-size:clamp(2.4rem,4.5vw,3.8rem); font-weight:700; line-height:1.1; letter-spacing:-0.03em; color:#f5f5f5; margin-bottom:1.5rem;">
          Sua clínica é<br>
          <span class="text-gold-gradient">excelente.</span><br>
          Sua gestão pode<br>ser também.
        </h1>
        <p style="color:#9b9b9b; font-size:16px; line-height:1.8; max-width:480px; margin-bottom:2.5rem;">
          A LumiClinic devolve o seu tempo. Agenda, prontuário, financeiro e marketing integrados em uma plataforma criada por quem viveu cada dor da gestão clínica — e decidiu fazer diferente.
        </p>
        <div class="flex flex-wrap gap-3 mb-10">
          <a href="/criar-conta" class="btn-gold px-7 py-3.5" style="font-size:15px;">Testar agora →</a>
          <a href="#funcionalidades" class="btn-outline px-7 py-3.5 flex items-center gap-2" style="font-size:15px;">Ver como funciona ↓</a>
        </div>
        <p style="color:rgba(255,255,255,0.4);font-size:12px;display:flex;align-items:center;gap:8px;">
          <span style="color:var(--gold);font-size:10px;">✦</span>
          7 dias grátis · Migração de dados inclusa · Suporte humano desde o primeiro dia
        </p>
      </div>
      <div class="relative reveal reveal-delay-2">
        <div class="absolute inset-0" style="background: radial-gradient(ellipse 70% 60% at 50% 50%, rgba(212,162,76,0.12) 0%, transparent 70%); filter:blur(20px);"></div>
        <img src="/Imagem L com os mockups inicial - site lumiclinic.zip.png" alt="LumiClinic" style="position:relative;z-index:10;width:100%;max-width:580px;" />
      </div>
    </div>
  </div>
</section>

<!-- ========== DORES ========== -->
<section id="funcionalidades" class="py-24 lg:py-32 relative">
  <div class="absolute inset-0" style="background:radial-gradient(ellipse 60% 40% at 50% 50%, rgba(212,162,76,0.04) 0%, transparent 70%);"></div>
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="text-center mb-16 reveal">
      <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.8rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.02em;">
        Ainda parece <span class="text-gold-gradient">familiar?</span>
      </h2>
    </div>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="card-premium p-6 reveal">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        </div>
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">"A agenda virou bagunça"</h3>
        <p style="color:#9b9b9b;font-size:13.5px;line-height:1.7;">Profissionais diferentes, horários sobrepostos, confirmações que não chegam. O dia começa sempre no caos.</p>
      </div>
      <div class="card-premium p-6 reveal reveal-delay-1">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">"O financeiro é um mistério todo mês"</h3>
        <p style="color:#9b9b9b;font-size:13.5px;line-height:1.7;">Você fatura, mas não sabe exatamente quanto sobra. As taxas, os pacotes, as sessões — tudo na cabeça ou em planilha.</p>
      </div>
      <div class="card-premium p-6 reveal reveal-delay-2">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">"O prontuário come seu tempo"</h3>
        <p style="color:#9b9b9b;font-size:13.5px;line-height:1.7;">Digitar cada procedimento rouba minutos que deveriam ser de atendimento. No final do dia, você digitou mais do que cuidou.</p>
      </div>
      <div class="card-premium p-6 reveal reveal-delay-1">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3"/></svg>
        </div>
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">"Paciente sumiu? Você só descobre depois"</h3>
        <p style="color:#9b9b9b;font-size:13.5px;line-height:1.7;">Sem retorno automático, sem régua de relacionamento. A paciente que precisava de reforço foi para a concorrência.</p>
      </div>
      <div class="card-premium p-6 reveal reveal-delay-2">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">"Estoque? Torço para ter o produto no dia"</h3>
        <p style="color:#9b9b9b;font-size:13.5px;line-height:1.7;">Baixa automática? Alerta de vencimento? Controle de lote? A maioria improvisa até o último momento.</p>
      </div>
      <div class="card-premium p-6 reveal reveal-delay-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </div>
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">"Crescer parece arriscado demais"</h3>
        <p style="color:#9b9b9b;font-size:13.5px;line-height:1.7;">Cada novo profissional, cada nova sala, multiplica o caos. Crescer deveria ser bom, mas na prática dói.</p>
      </div>
    </div>
    <p class="text-center mt-10 reveal" style="color:#9b9b9b;font-size:14px;font-style:italic;">Se você se reconheceu em qualquer um desses cenários, não é falta de competência. É falta de ferramenta certa.</p>
  </div>
</section>

<!-- ========== PLATAFORMA ========== -->
<section id="plataforma" class="py-24 lg:py-32 relative">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-16 items-start">
      <div class="reveal">
        <p style="color:var(--gold);font-size:13px;font-family:'Sora',sans-serif;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:16px;">A plataforma</p>
        <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.6rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.02em;margin-bottom:16px;">
          Uma plataforma.<br><span class="text-gold-gradient">Toda a sua gestão.</span>
        </h2>
        <p style="color:#9b9b9b;font-size:15px;line-height:1.8;margin-bottom:40px;">
          A LumiClinic reúne em um único lugar tudo o que sua clínica precisa para funcionar com excelência — sem integrações frágeis, sem dados espalhados, sem complexidade desnecessária.
        </p>
        <div class="flex flex-col gap-1" id="tab-list">
          <button class="tab-btn active text-left px-5 py-4 rounded-xl border-l-2" style="border-left-color:var(--gold);background:rgba(212,162,76,0.05);" data-tab="agenda" onclick="switchTab('agenda', this)">
            <div style="font-family:'Sora',sans-serif;font-size:14px;font-weight:600;color:var(--gold);">Agenda inteligente</div>
            <div style="color:#9b9b9b;font-size:13px;margin-top:2px;">Confirmação automática · Lembretes · Online</div>
          </button>
          <button class="tab-btn text-left px-5 py-4 rounded-xl border-l-2" style="border-left-color:transparent;" data-tab="prontuario" onclick="switchTab('prontuario', this)">
            <div style="font-family:'Sora',sans-serif;font-size:14px;font-weight:600;color:#9b9b9b;">Prontuário com IA</div>
            <div style="color:#9b9b9b;font-size:13px;margin-top:2px;">Transcrição · Templates · Assinatura digital</div>
          </button>
          <button class="tab-btn text-left px-5 py-4 rounded-xl border-l-2" style="border-left-color:transparent;" data-tab="financeiro" onclick="switchTab('financeiro', this)">
            <div style="font-family:'Sora',sans-serif;font-size:14px;font-weight:600;color:#9b9b9b;">Financeiro completo</div>
            <div style="color:#9b9b9b;font-size:13px;margin-top:2px;">Vendas · Pacotes · Fluxo de caixa</div>
          </button>
          <button class="tab-btn text-left px-5 py-4 rounded-xl border-l-2" style="border-left-color:transparent;" data-tab="marketing" onclick="switchTab('marketing', this)">
            <div style="font-family:'Sora',sans-serif;font-size:14px;font-weight:600;color:#9b9b9b;">Marketing automatizado</div>
            <div style="color:#9b9b9b;font-size:13px;margin-top:2px;">Campanhas · Retorno · WhatsApp</div>
          </button>
          <button class="tab-btn text-left px-5 py-4 rounded-xl border-l-2" style="border-left-color:transparent;" data-tab="estoque" onclick="switchTab('estoque', this)">
            <div style="font-family:'Sora',sans-serif;font-size:14px;font-weight:600;color:#9b9b9b;">Estoque sem surpresas</div>
            <div style="color:#9b9b9b;font-size:13px;margin-top:2px;">Baixa automática · Alertas · Lotes</div>
          </button>
          <button class="tab-btn text-left px-5 py-4 rounded-xl border-l-2" style="border-left-color:transparent;" data-tab="portal" onclick="switchTab('portal', this)">
            <div style="font-family:'Sora',sans-serif;font-size:14px;font-weight:600;color:#9b9b9b;">Portal da paciente</div>
            <div style="color:#9b9b9b;font-size:13px;margin-top:2px;">Agendamento · Documentos · Evolução</div>
          </button>
        </div>
      </div>
      <div class="reveal reveal-delay-2">
        <div id="tab-panels">
          <div class="tab-panel active" id="panel-agenda">
            <div class="card-premium p-6 glow-gold" style="height:320px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:8px;">
              <div style="width:44px;height:44px;border-radius:50%;border:1px solid var(--border-gold);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:18px;">🖼</div>
              <span style="color:#9b9b9b;font-size:13px;">Preview da tela</span>
              <span style="color:rgba(155,155,155,0.5);font-size:11px;">Adicione aqui a imagem da seção Agenda inteligente no app</span>
            </div>
            <div class="mt-4 p-5 card-premium">
              <h3 style="font-family:'Sora',sans-serif;font-size:17px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Agenda inteligente</h3>
              <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Multiespecialistas, múltiplas salas, durações e valores distintos por serviço. Lembretes automáticos via WhatsApp, confirmação de presença e monitoramento de atrasos — tudo sem que você precise lembrar de nada.</p>
              <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-gold);">
                <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#9b9b9b;margin-bottom:6px;">Benefício</div>
                <div style="font-size:14px;color:var(--gold);font-style:italic;">Menos faltas, mais previsibilidade no seu dia.</div>
              </div>
            </div>
          </div>
          <div class="tab-panel" id="panel-prontuario">
            <div class="card-premium p-6 glow-gold" style="height:320px;display:flex;align-items:center;justify-content:center;color:#9b9b9b;font-size:13px;">Preview da tela — Prontuário com IA</div>
            <div class="mt-4 p-5 card-premium">
              <h3 style="font-family:'Sora',sans-serif;font-size:17px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Prontuário com IA</h3>
              <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Transcrição automática da consulta em tempo real. Fale enquanto atende, o sistema registra. Modelos personalizáveis, histórico completo e assinatura digital integrada.</p>
              <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-gold);">
                <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#9b9b9b;margin-bottom:6px;">Benefício</div>
                <div style="font-size:14px;color:var(--gold);font-style:italic;">Mais tempo com a paciente, menos tempo digitando.</div>
              </div>
            </div>
          </div>
          <div class="tab-panel" id="panel-financeiro">
            <div class="card-premium p-6 glow-gold" style="height:320px;display:flex;align-items:center;justify-content:center;color:#9b9b9b;font-size:13px;">Preview da tela — Financeiro</div>
            <div class="mt-4 p-5 card-premium">
              <h3 style="font-family:'Sora',sans-serif;font-size:17px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Financeiro completo</h3>
              <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Controle de vendas, pacotes, sessões, comissões por profissional, contas a pagar e fluxo de caixa. Relatórios automáticos que mostram exatamente onde está seu lucro.</p>
              <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-gold);">
                <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#9b9b9b;margin-bottom:6px;">Benefício</div>
                <div style="font-size:14px;color:var(--gold);font-style:italic;">Clareza financeira sem precisar de planilha.</div>
              </div>
            </div>
          </div>
          <div class="tab-panel" id="panel-marketing">
            <div class="card-premium p-6 glow-gold" style="height:320px;display:flex;align-items:center;justify-content:center;color:#9b9b9b;font-size:13px;">Preview da tela — Marketing</div>
            <div class="mt-4 p-5 card-premium">
              <h3 style="font-family:'Sora',sans-serif;font-size:17px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Marketing automatizado</h3>
              <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Réguas de relacionamento, campanhas segmentadas, lembretes de retorno e mensagens automáticas via WhatsApp.</p>
              <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-gold);">
                <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#9b9b9b;margin-bottom:6px;">Benefício</div>
                <div style="font-size:14px;color:var(--gold);font-style:italic;">Pacientes voltam sem que você precise correr atrás.</div>
              </div>
            </div>
          </div>
          <div class="tab-panel" id="panel-estoque">
            <div class="card-premium p-6 glow-gold" style="height:320px;display:flex;align-items:center;justify-content:center;color:#9b9b9b;font-size:13px;">Preview da tela — Estoque</div>
            <div class="mt-4 p-5 card-premium">
              <h3 style="font-family:'Sora',sans-serif;font-size:17px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Estoque sem surpresas</h3>
              <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Baixa automática por procedimento, alertas de vencimento, controle de lote e relatórios de consumo.</p>
              <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-gold);">
                <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#9b9b9b;margin-bottom:6px;">Benefício</div>
                <div style="font-size:14px;color:var(--gold);font-style:italic;">Nunca mais cancele um procedimento por falta de material.</div>
              </div>
            </div>
          </div>
          <div class="tab-panel" id="panel-portal">
            <div class="card-premium p-6 glow-gold" style="height:320px;display:flex;align-items:center;justify-content:center;color:#9b9b9b;font-size:13px;">Preview da tela — Portal</div>
            <div class="mt-4 p-5 card-premium">
              <h3 style="font-family:'Sora',sans-serif;font-size:17px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Portal da paciente</h3>
              <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Agendamento online, acesso a documentos, fotos de evolução, anamnese digital e comunicação direta.</p>
              <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-gold);">
                <div style="font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#9b9b9b;margin-bottom:6px;">Benefício</div>
                <div style="font-size:14px;color:var(--gold);font-style:italic;">Experiência premium que fideliza e diferencia.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== FUNDADORA ========== -->
<section class="py-24 lg:py-32 relative">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-16 items-center">
      <div class="reveal relative">
        <div style="border-radius:24px;overflow:hidden;border:1px solid var(--border-gold);position:relative;">
          <img src="/Imagem da mulher - site lumiclinic.png" alt="Dra. Letícia Brito - Fundadora da LumiClinic" style="width:100%;border-radius:24px;" />
          <div style="position:absolute;bottom:20px;left:20px;right:20px;">
            <div style="background:rgba(11,11,11,0.9);border:1px solid var(--border-gold);border-radius:14px;padding:16px;backdrop-filter:blur(20px);">
              <div style="font-family:'Sora',sans-serif;font-size:14px;font-weight:600;color:#f5f5f5;margin-bottom:2px;">Dra. Letícia Brito</div>
              <div style="color:var(--gold);font-size:12px;">Fundadora da LumiClinic · 9 anos de gestão clínica · Especialista em harmonização facial</div>
            </div>
          </div>
        </div>
      </div>
      <div class="reveal reveal-delay-2">
        <p style="color:var(--gold);font-size:13px;font-family:'Sora',sans-serif;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:16px;">Por quem conhece</p>
        <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.4rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.02em;margin-bottom:20px;">
          Criada por quem conhece <span class="text-gold-gradient">cada dor</span> que você sente
        </h2>
        <p style="color:#9b9b9b;font-size:15px;line-height:1.8;margin-bottom:16px;">A maioria dos softwares de gestão foi construída por engenheiros que nunca pisaram em uma clínica estética. A LumiClinic foi diferente.</p>
        <p style="color:#9b9b9b;font-size:15px;line-height:1.8;margin-bottom:16px;">A Dra. Letícia Brito passou nove anos na gestão real de clínicas estéticas — das noites resolvendo conflitos de agenda, dos meses tentando entender o financeiro em planilhas, das horas perdidas digitando prontuários enquanto pacientes esperavam.</p>
        <p style="color:#9b9b9b;font-size:15px;line-height:1.8;margin-bottom:16px;">Quando não encontrou a ferramenta que precisava, decidiu construir. Cada funcionalidade da LumiClinic responde a uma dor que ela própria viveu.</p>
        <p style="color:#9b9b9b;font-size:15px;line-height:1.8;margin-bottom:32px;">Concorrentes podem copiar features. Não podem copiar nove anos de experiência.</p>
        <div style="border-left:2px solid var(--gold);padding-left:20px;">
          <p style="color:#f5f5f5;font-size:15px;line-height:1.8;font-style:italic;margin-bottom:8px;">"Eu não queria mais um software de gestão. Queria o software que eu precisava ter tido e que nenhum sistema me ofereceu. Então construí."</p>
          <p style="color:var(--gold);font-size:13px;font-family:'Sora',sans-serif;">— Dra. Letícia Brito, fundadora da LumiClinic.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== BENEFÍCIOS ========== -->
<section class="py-24 lg:py-32 relative">
  <div class="absolute inset-0" style="background:radial-gradient(ellipse 50% 40% at 50% 50%, rgba(212,162,76,0.04) 0%, transparent 70%);"></div>
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="text-center mb-16 reveal">
      <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.8rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.02em;line-height:1.2;">
        O que muda quando a gestão <span class="text-gold-gradient">para de ser o problema</span>
      </h2>
    </div>
    <div class="grid md:grid-cols-3 gap-6">
      <div class="card-premium p-8 reveal">
        <div style="font-family:'Sora',sans-serif;font-size:48px;font-weight:800;color:var(--gold);line-height:1;margin-bottom:12px;">↑40%</div>
        <h3 style="font-family:'Sora',sans-serif;font-size:18px;font-weight:600;color:#f5f5f5;margin-bottom:10px;">Mais pacientes atendidos</h3>
        <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Com confirmações automáticas e agenda otimizada, sua taxa de comparecimento chega a 96%.</p>
      </div>
      <div class="card-premium p-8 reveal reveal-delay-2">
        <div style="font-family:'Sora',sans-serif;font-size:48px;font-weight:800;color:var(--gold);line-height:1;margin-bottom:12px;">3h+</div>
        <h3 style="font-family:'Sora',sans-serif;font-size:18px;font-weight:600;color:#f5f5f5;margin-bottom:10px;">Liberadas por semana</h3>
        <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Automatize confirmações, cobranças e follow-ups. Recupere horas que você hoje perde em tarefas manuais.</p>
      </div>
      <div class="card-premium p-8 reveal reveal-delay-4">
        <div style="font-family:'Sora',sans-serif;font-size:48px;font-weight:800;color:var(--gold);line-height:1;margin-bottom:12px;">2.4×</div>
        <h3 style="font-family:'Sora',sans-serif;font-size:18px;font-weight:600;color:#f5f5f5;margin-bottom:10px;">Mais retorno de pacientes</h3>
        <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Com o CRM ativo, campanhas de reativação e follow-ups no momento certo, seus pacientes voltam mais.</p>
      </div>
    </div>
  </div>
</section>

<!-- ========== PARA QUEM ========== -->
<section id="para-quem" class="py-24 lg:py-32 relative">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="mb-16 reveal">
      <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.6rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.02em;">
        A LumiClinic foi feita <span class="text-gold-gradient">para você</span>
      </h2>
    </div>
    <div class="grid md:grid-cols-2 gap-4">
      <div class="card-premium p-8 reveal" style="background:linear-gradient(145deg,rgba(212,162,76,0.04),transparent);">
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Se você é dona de clínica estética ou de harmonização facial</h3>
        <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">e quer parar de gerenciar no improviso e começar a crescer com estratégia, a LumiClinic é sua plataforma.</p>
      </div>
      <div class="card-premium p-8 reveal reveal-delay-1" style="background:linear-gradient(145deg,rgba(212,162,76,0.04),transparent);">
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Se você é médica ou profissional de saúde que abriu o próprio consultório</h3>
        <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">e descobriu que empreender exige mais do que técnica clínica, a LumiClinic cuida da gestão enquanto você cuida das suas pacientes.</p>
      </div>
      <div class="card-premium p-8 reveal reveal-delay-2" style="background:linear-gradient(145deg,rgba(212,162,76,0.04),transparent);">
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Se você tem uma equipe que precisa de coordenação</h3>
        <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">com múltiplos profissionais, salas e serviços diferentes, a LumiClinic organiza tudo com controle por usuário e indicadores por profissional.</p>
      </div>
      <div class="card-premium p-8 reveal reveal-delay-3" style="background:linear-gradient(145deg,rgba(212,162,76,0.04),transparent);">
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Se você está cansada de pagar por vários sistemas separados</h3>
        <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">que não conversam entre si, a LumiClinic substitui tudo em uma única plataforma integrada.</p>
      </div>
    </div>
    <p class="text-center mt-10 reveal" style="color:#9b9b9b;font-size:13px;font-style:italic;">Não importa se você tem uma sala ou dez. A LumiClinic cresce com você.</p>
  </div>
</section>

<!-- ========== PREÇOS ========== -->
<section id="planos" class="py-24 lg:py-32 relative">
  <div class="absolute inset-0" style="background:radial-gradient(ellipse 60% 50% at 50% 30%, rgba(212,162,76,0.06) 0%, transparent 70%);"></div>
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="text-center mb-16 reveal">
      <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.8rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.02em;margin-bottom:8px;">Transparência total.</h2>
      <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.8rem);font-weight:700;letter-spacing:-0.02em;" class="text-gold-gradient">Sem taxas escondidas.</h2>
      <p style="color:#9b9b9b;font-size:14px;margin-top:16px;max-width:480px;margin-left:auto;margin-right:auto;">Escolha o plano ideal para o momento da sua clínica. Todos com suporte humano e acesso completo à plataforma.</p>
    </div>
    <div class="grid md:grid-cols-3 gap-6 items-center max-w-5xl mx-auto">
      <?php
      $displayPlans = array_filter($plans, fn($p) => (int)($p['price_cents'] ?? 0) > 0);
      $displayPlans = array_values($displayPlans);
      $index = 0;
      $totalPlans = count($displayPlans);
      foreach ($displayPlans as $plan):
          $code = $plan['code'] ?? '';
          $name = $plan['name'] ?? $code;
          $priceCents = (int)($plan['price_cents'] ?? 0);
          $priceReais = intval($priceCents / 100);
          $trialDays = (int)($plan['trial_days'] ?? 0);
          $limits = json_decode($plan['limits_json'] ?? '{}', true) ?: [];
          $users = $limits['users'] ?? 0;
          $patients = $limits['patients'] ?? 0;
          $storageMb = $limits['storage_mb'] ?? 0;
          $storageGb = $storageMb >= 1000 ? round($storageMb / 1000) : 0;
          $portal = $limits['portal'] ?? false;
          $transcriptionMinutes = $limits['transcription_minutes'] ?? 0;
          $isFeatured = $index === 1;
          $index++;
      ?>
      <div class="<?= $isFeatured ? 'pricing-popular' : 'card-premium' ?> p-8 reveal <?= $isFeatured ? 'reveal-delay-2 relative' : ($index === $totalPlans ? 'reveal-delay-4' : '') ?>" style="border-radius:16px;">
        <?php if ($isFeatured): ?>
        <div style="position:absolute;top:-14px;left:50%;transform:translateX(-50%);">
          <div class="btn-gold px-5 py-1.5" style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;border-radius:100px;">Mais popular</div>
        </div>
        <?php endif; ?>
        <div style="font-family:'Sora',sans-serif;font-size:13px;font-weight:500;color:<?= $isFeatured ? 'var(--gold)' : '#9b9b9b' ?>;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:20px;"><?= $e($name) ?></div>
        <div class="mb-6">
          <div style="display:flex;align-items:baseline;gap:4px;">
            <span style="font-family:'Sora',sans-serif;font-size:14px;color:#9b9b9b;">R$</span>
            <span style="font-family:'Sora',sans-serif;font-size:48px;font-weight:700;line-height:1;<?= $isFeatured ? '' : 'color:#f5f5f5;' ?>" <?= $isFeatured ? 'class="text-gold-gradient"' : '' ?>><?= $priceReais ?></span>
            <span style="font-family:'Sora',sans-serif;font-size:14px;color:#9b9b9b;">/mês</span>
          </div>
          <?php if ($trialDays > 0): ?>
          <p style="color:var(--gold-light);font-size:13px;margin-top:6px;"><?= $trialDays ?> dias grátis</p>
          <?php endif; ?>
        </div>
        <div class="divider-gold mb-6"></div>
        <ul class="flex flex-col gap-3 mb-8">
          <?php if ($users > 0): ?>
          <li class="flex items-start gap-3 check-item" style="color:<?= $isFeatured ? '#f5f5f5' : '#9b9b9b' ?>;font-size:13.5px;">Até <?= number_format($users, 0, ',', '.') ?> usuários</li>
          <?php else: ?>
          <li class="flex items-start gap-3 check-item" style="color:<?= $isFeatured ? '#f5f5f5' : '#9b9b9b' ?>;font-size:13.5px;">Usuários <strong>ilimitados</strong></li>
          <?php endif; ?>
          <?php if ($patients > 0): ?>
          <li class="flex items-start gap-3 check-item" style="color:<?= $isFeatured ? '#f5f5f5' : '#9b9b9b' ?>;font-size:13.5px;">Até <?= number_format($patients, 0, ',', '.') ?> pacientes</li>
          <?php else: ?>
          <li class="flex items-start gap-3 check-item" style="color:<?= $isFeatured ? '#f5f5f5' : '#9b9b9b' ?>;font-size:13.5px;">Pacientes <strong>ilimitados</strong></li>
          <?php endif; ?>
          <?php if ($storageGb > 0): ?>
          <li class="flex items-start gap-3 check-item" style="color:<?= $isFeatured ? '#f5f5f5' : '#9b9b9b' ?>;font-size:13.5px;"><?= $storageGb ?> GB de armazenamento</li>
          <?php endif; ?>
          <?php if ($transcriptionMinutes > 0): ?>
          <li class="flex items-start gap-3 check-item" style="color:<?= $isFeatured ? '#f5f5f5' : '#9b9b9b' ?>;font-size:13.5px;">Transcrição com IA — <?= $transcriptionMinutes ?> min/mês</li>
          <?php endif; ?>
          <?php if ($portal): ?>
          <li class="flex items-start gap-3 check-item" style="color:<?= $isFeatured ? '#f5f5f5' : '#9b9b9b' ?>;font-size:13.5px;">Portal da paciente</li>
          <?php endif; ?>
          <li class="flex items-start gap-3 check-item" style="color:<?= $isFeatured ? '#f5f5f5' : '#9b9b9b' ?>;font-size:13.5px;">Financeiro completo</li>
          <li class="flex items-start gap-3 check-item" style="color:<?= $isFeatured ? '#f5f5f5' : '#9b9b9b' ?>;font-size:13.5px;">Marketing automatizado</li>
          <?php if ($index === $totalPlans): ?>
          <li class="flex items-start gap-3 check-item" style="color:#9b9b9b;font-size:13.5px;">Suporte humano prioritário</li>
          <li class="flex items-start gap-3 check-item" style="color:#9b9b9b;font-size:13.5px;">Migração de dados inclusa</li>
          <?php elseif ($isFeatured): ?>
          <li class="flex items-start gap-3 check-item" style="color:#f5f5f5;font-size:13.5px;">Suporte humano</li>
          <li class="flex items-start gap-3 check-item" style="color:#f5f5f5;font-size:13.5px;">Migração de dados inclusa</li>
          <?php else: ?>
          <li class="flex items-start gap-3 check-item" style="color:#9b9b9b;font-size:13.5px;">Suporte humano</li>
          <?php endif; ?>
        </ul>
        <?php if ($isFeatured): ?>
        <a href="/criar-conta" class="btn-gold block text-center px-6 py-3.5 text-sm">Começar agora</a>
        <?php elseif ($index === $totalPlans): ?>
        <a href="#" class="btn-outline block text-center px-6 py-3.5 text-sm">Falar com consultor</a>
        <?php else: ?>
        <a href="/criar-conta" class="btn-outline block text-center px-6 py-3.5 text-sm">Começar agora</a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="text-center mt-10 reveal" style="color:#9b9b9b;font-size:13px;">Todos os planos incluem agenda, prontuário, estoque, financeiro e portal da paciente. Sem contrato de fidelidade. Cancele quando quiser.</p>
  </div>
</section>

<!-- ========== FAQ ========== -->
<section id="faq" class="py-24 lg:py-32 relative">
  <div class="max-w-3xl mx-auto px-6 lg:px-8">
    <div class="text-center mb-16 reveal">
      <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.6rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.02em;">
        Perguntas <span class="text-gold-gradient">frequentes</span>
      </h2>
    </div>
    <div class="flex flex-col gap-0 reveal">
      <div class="accordion-item" style="border-bottom:1px solid var(--border-gold);">
        <button class="accordion-trigger w-full text-left py-5 flex items-center justify-between gap-4" onclick="toggleAccordion(this)">
          <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:500;color:#f5f5f5;">Preciso instalar alguma coisa?</span>
          <span class="accordion-icon" style="color:var(--gold);font-size:20px;">+</span>
        </button>
        <div class="accordion-content">
          <p style="color:#9b9b9b;font-size:14px;line-height:1.8;padding-bottom:20px;">Não. A LumiClinic funciona diretamente no navegador, em qualquer dispositivo. Sem instalação, sem atualização manual.</p>
        </div>
      </div>
      <div class="accordion-item" style="border-bottom:1px solid var(--border-gold);">
        <button class="accordion-trigger w-full text-left py-5 flex items-center justify-between gap-4" onclick="toggleAccordion(this)">
          <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:500;color:#f5f5f5;">Posso migrar meus dados do sistema atual?</span>
          <span class="accordion-icon" style="color:var(--gold);font-size:20px;">+</span>
        </button>
        <div class="accordion-content">
          <p style="color:#9b9b9b;font-size:14px;line-height:1.8;padding-bottom:20px;">Sim. Importamos pacientes, agendamentos, histórico financeiro e procedimentos de outros sistemas. Nossa equipe cuida de tudo.</p>
        </div>
      </div>
      <div class="accordion-item" style="border-bottom:1px solid var(--border-gold);">
        <button class="accordion-trigger w-full text-left py-5 flex items-center justify-between gap-4" onclick="toggleAccordion(this)">
          <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:500;color:#f5f5f5;">A transcrição com IA funciona em português?</span>
          <span class="accordion-icon" style="color:var(--gold);font-size:20px;">+</span>
        </button>
        <div class="accordion-content">
          <p style="color:#9b9b9b;font-size:14px;line-height:1.8;padding-bottom:20px;">Sim. A transcrição foi treinada para português brasileiro, incluindo termos técnicos de estética e harmonização facial.</p>
        </div>
      </div>
      <div class="accordion-item" style="border-bottom:1px solid var(--border-gold);">
        <button class="accordion-trigger w-full text-left py-5 flex items-center justify-between gap-4" onclick="toggleAccordion(this)">
          <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:500;color:#f5f5f5;">Meus dados ficam seguros?</span>
          <span class="accordion-icon" style="color:var(--gold);font-size:20px;">+</span>
        </button>
        <div class="accordion-content">
          <p style="color:#9b9b9b;font-size:14px;line-height:1.8;padding-bottom:20px;">Sim. Utilizamos criptografia de ponta a ponta, servidores no Brasil e estamos em conformidade com a LGPD.</p>
        </div>
      </div>
      <div class="accordion-item" style="border-bottom:1px solid var(--border-gold);">
        <button class="accordion-trigger w-full text-left py-5 flex items-center justify-between gap-4" onclick="toggleAccordion(this)">
          <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:500;color:#f5f5f5;">Posso trocar de plano depois?</span>
          <span class="accordion-icon" style="color:var(--gold);font-size:20px;">+</span>
        </button>
        <div class="accordion-content">
          <p style="color:#9b9b9b;font-size:14px;line-height:1.8;padding-bottom:20px;">Sim. Você pode fazer upgrade ou downgrade a qualquer momento, sem multa e sem burocracia.</p>
        </div>
      </div>
      <div class="accordion-item" style="border-bottom:1px solid var(--border-gold);">
        <button class="accordion-trigger w-full text-left py-5 flex items-center justify-between gap-4" onclick="toggleAccordion(this)">
          <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:500;color:#f5f5f5;">Tem suporte em português?</span>
          <span class="accordion-icon" style="color:var(--gold);font-size:20px;">+</span>
        </button>
        <div class="accordion-content">
          <p style="color:#9b9b9b;font-size:14px;line-height:1.8;padding-bottom:20px;">Sim. Todo o suporte é em português, feito por humanos que entendem a rotina de uma clínica estética.</p>
        </div>
      </div>
      <div class="accordion-item">
        <button class="accordion-trigger w-full text-left py-5 flex items-center justify-between gap-4" onclick="toggleAccordion(this)">
          <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:500;color:#f5f5f5;">Funciona para clínica com mais de um profissional?</span>
          <span class="accordion-icon" style="color:var(--gold);font-size:20px;">+</span>
        </button>
        <div class="accordion-content">
          <p style="color:#9b9b9b;font-size:14px;line-height:1.8;padding-bottom:20px;">Sim. A LumiClinic foi projetada para clínicas com múltiplos profissionais, salas e especialidades. Cada um com seu acesso e indicadores.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== CTA FINAL ========== -->
<section class="py-32 lg:py-40 relative overflow-hidden">
  <div class="absolute inset-0 grid-bg"></div>
  <div class="absolute inset-0" style="background:radial-gradient(ellipse 70% 60% at 50% 50%, rgba(212,162,76,0.1) 0%, transparent 70%);"></div>
  <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
    <div class="reveal">
      <h2 style="font-family:'Sora',sans-serif;font-size:clamp(2rem,5vw,3.5rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.03em;line-height:1.1;margin-bottom:20px;">
        Sua clínica merece gestão<br><span class="text-gold-gradient">à altura do seu atendimento.</span>
      </h2>
      <p style="color:#9b9b9b;font-size:16px;line-height:1.8;max-width:520px;margin:0 auto 20px;">
        Você já dominou o que é mais difícil: a técnica clínica. Agora é hora de ter uma plataforma que domina o resto por você.
      </p>
      <p style="color:#9b9b9b;font-size:14px;margin-bottom:40px;max-width:520px;margin-left:auto;margin-right:auto;">
        Comece com 7 dias gratuitos e descubra como é trabalhar com clareza, tranquilidade e tempo de sobra para o que realmente importa.
      </p>
      <div class="flex flex-wrap justify-center gap-4 mb-5">
        <a href="/criar-conta" class="btn-gold px-10 py-4" style="font-size:16px;">Começar meu período gratuito →</a>
        <a href="#" class="btn-outline px-8 py-4" style="font-size:15px;">Quero falar com alguém antes</a>
      </div>
      <p style="color:#9b9b9b;font-size:12px;">Sem cartão de crédito · Sem contrato · Suporte humano incluso</p>
    </div>
  </div>
</section>

<!-- ========== FOOTER ========== -->
<footer style="background:#050505;border-top:1px solid var(--border-gold);">
  <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16">
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-10 mb-14">
      <div class="col-span-2 lg:col-span-2">
        <div class="flex items-center gap-2 mb-5">
          <img src="/Principal_1.png" alt="LumiClinic" style="height:28px;" />
        </div>
        <p style="color:#9b9b9b;font-size:14px;line-height:1.8;max-width:280px;">
          Plataforma de gestão clínica com inteligência artificial nativa para clínicas de estética e harmonização facial.
        </p>
      </div>
      <div>
        <h4 style="font-family:'Sora',sans-serif;font-size:12px;font-weight:600;color:#f5f5f5;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:16px;">Produto</h4>
        <ul class="flex flex-col gap-3">
          <li><a href="#funcionalidades" class="footer-link">Funcionalidades</a></li>
          <li><a href="#planos" class="footer-link">Planos e preços</a></li>
          <li><a href="#" class="footer-link">Novidades</a></li>
          <li><a href="#" class="footer-link">Migração de dados</a></li>
        </ul>
      </div>
      <div>
        <h4 style="font-family:'Sora',sans-serif;font-size:12px;font-weight:600;color:#f5f5f5;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:16px;">Empresa</h4>
        <ul class="flex flex-col gap-3">
          <li><a href="#" class="footer-link">Sobre a LumiClinic</a></li>
          <li><a href="#" class="footer-link">Blog</a></li>
          <li><a href="#" class="footer-link">Fale conosco</a></li>
          <li><a href="#" class="footer-link">Trabalhe conosco</a></li>
        </ul>
      </div>
      <div>
        <h4 style="font-family:'Sora',sans-serif;font-size:12px;font-weight:600;color:#f5f5f5;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:16px;">Suporte</h4>
        <ul class="flex flex-col gap-3">
          <li><a href="#" class="footer-link">Central de ajuda</a></li>
          <li><a href="#" class="footer-link">Documentação</a></li>
          <li><a href="#" class="footer-link">Status do sistema</a></li>
          <li><a href="#" class="footer-link">Suporte via WhatsApp</a></li>
        </ul>
      </div>
    </div>
    <div class="divider-gold mb-8"></div>
    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
      <p style="color:#9b9b9b;font-size:13px;">© 2026 LumiClinic. Todos os direitos reservados.</p>
      <div class="flex gap-6">
        <a href="#" class="footer-link" style="font-size:13px;">Política de privacidade</a>
        <a href="#" class="footer-link" style="font-size:13px;">Termos de uso</a>
        <a href="#" class="footer-link" style="font-size:13px;">LGPD</a>
      </div>
    </div>
  </div>
</footer>

<!-- ========== SCRIPTS ========== -->
<script>
function toggleMenu() { document.getElementById('mobile-menu').classList.toggle('open'); }
function toggleAccordion(btn) {
  const content = btn.nextElementSibling;
  const icon = btn.querySelector('.accordion-icon');
  const isOpen = content.classList.contains('open');
  document.querySelectorAll('.accordion-content').forEach(el => el.classList.remove('open'));
  document.querySelectorAll('.accordion-icon').forEach(el => el.classList.remove('open'));
  if (!isOpen) { content.classList.add('open'); icon.classList.add('open'); }
}
function switchTab(tabId, btn) {
  document.querySelectorAll('.tab-btn').forEach(b => {
    b.classList.remove('active');
    b.style.background = 'transparent';
    b.querySelector('div:first-child').style.color = '#9b9b9b';
    b.style.borderLeftColor = 'transparent';
  });
  btn.classList.add('active');
  btn.style.background = 'rgba(212,162,76,0.05)';
  btn.querySelector('div:first-child').style.color = 'var(--gold)';
  btn.style.borderLeftColor = 'var(--gold)';
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('panel-' + tabId).classList.add('active');
}
// Scroll Reveal
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('visible'); revealObserver.unobserve(entry.target); } });
}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));
// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function(e) {
    const href = this.getAttribute('href');
    if (href === '#') return;
    const target = document.querySelector(href);
    if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth', block: 'start' }); document.getElementById('mobile-menu').classList.remove('open'); }
  });
});
</script>
</body>
</html>
