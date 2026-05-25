<?php
$plans = $plans ?? [];
$e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$support_settings = $support_settings ?? [];
$_supportWa = trim((string)($support_settings['support_whatsapp_number'] ?? ''));
$_waClean = preg_replace('/\D+/', '', $_supportWa);
$_waUrl = $_waClean !== '' ? ('https://wa.me/' . $_waClean) : '#';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LumiClinic — Gestão Clínica Premium</title>
<meta name="description" content="A plataforma de gestão clínica mais sofisticada do mercado. Agenda, prontuários, financeiro e muito mais.">
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
<style>
  :root {
    --gold: #d4a24c;
    --gold-light: #e8bc6a;
    --gold-glow: rgba(212,162,76,0.15);
    --gold-glow-strong: rgba(212,162,76,0.3);
    --bg-deep: #050505;
    --bg-card: #0b0b0b;
    --bg-elevated: #111111;
    --border-gold: rgba(212,162,76,0.18);
    --border-gold-strong: rgba(212,162,76,0.35);
    --text-primary: #f5f5f5;
    --text-secondary: #9b9b9b;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }
  html { scroll-behavior: smooth; }

  body {
    background: var(--bg-deep);
    color: var(--text-primary);
    font-family: 'DM Sans', sans-serif;
    line-height: 1.6;
    overflow-x: hidden;
  }

  h1,h2,h3,h4,h5 { font-family: 'Sora', sans-serif; }

  ::-webkit-scrollbar { width: 4px; }
  ::-webkit-scrollbar-track { background: var(--bg-deep); }
  ::-webkit-scrollbar-thumb { background: var(--border-gold-strong); border-radius: 2px; }

  .text-gold { color: var(--gold); }
  .text-gold-gradient {
    background: linear-gradient(135deg, #d4a24c, #f0c96e, #d4a24c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .glow-gold { box-shadow: 0 0 40px var(--gold-glow), 0 0 80px rgba(212,162,76,0.08); }
  .glow-gold-strong { box-shadow: 0 0 60px var(--gold-glow-strong), 0 0 120px var(--gold-glow); }
  .text-glow { text-shadow: 0 0 30px rgba(212,162,76,0.4); }

  .border-gold { border: 1px solid var(--border-gold); }
  .border-gold-strong { border: 1px solid var(--border-gold-strong); }

  .card-premium {
    background: var(--bg-card);
    border: 1px solid var(--border-gold);
    border-radius: 16px;
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  }
  .card-premium:hover {
    border-color: var(--border-gold-strong);
    box-shadow: 0 0 30px var(--gold-glow), 0 20px 60px rgba(0,0,0,0.5);
    transform: translateY(-4px);
  }

  .btn-gold {
    background: linear-gradient(135deg, #d4a24c, #c49240);
    color: #050505;
    font-family: 'Sora', sans-serif;
    font-weight: 600;
    letter-spacing: 0.02em;
    border-radius: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 20px rgba(212,162,76,0.25);
    border: none;
    cursor: pointer;
  }
  .btn-gold:hover {
    background: linear-gradient(135deg, #e8bc6a, #d4a24c);
    box-shadow: 0 8px 30px rgba(212,162,76,0.4);
    transform: translateY(-1px);
  }

  .btn-outline {
    background: transparent;
    color: var(--text-primary);
    font-family: 'Sora', sans-serif;
    font-weight: 500;
    letter-spacing: 0.02em;
    border-radius: 8px;
    border: 1px solid rgba(245,245,245,0.2);
    transition: all 0.3s ease;
    cursor: pointer;
  }
  .btn-outline:hover {
    border-color: var(--gold);
    color: var(--gold);
    box-shadow: 0 0 20px var(--gold-glow);
  }

  .header-blur {
    background: rgba(5,5,5,0.8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border-gold);
  }

  .floating-card {
    background: var(--bg-card);
    border: 1px solid var(--border-gold);
    border-radius: 12px;
    backdrop-filter: blur(10px);
    animation: floatCard 4s ease-in-out infinite;
  }
  .floating-card:nth-child(2) { animation-delay: -2s; }
  .floating-card:nth-child(3) { animation-delay: -1s; }

  @keyframes floatCard {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-8px); }
  }

  .skeleton {
    background: linear-gradient(90deg, #111 25%, #1a1a1a 50%, #111 75%);
    background-size: 200% 100%;
    animation: shimmer 2s infinite;
    border-radius: 4px;
  }
  @keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
  }

  .accordion-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  }
  .accordion-content.open { max-height: 300px; }
  .accordion-icon { transition: transform 0.3s ease; }
  .accordion-icon.open { transform: rotate(45deg); }

  .tab-btn { transition: all 0.3s ease; }
  .tab-btn.active {
    color: var(--gold);
    border-left-color: var(--gold) !important;
  }
  .tab-panel { display: none; }
  .tab-panel.active { display: block; }

  .reveal {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.7s ease, transform 0.7s ease;
  }
  .reveal.visible {
    opacity: 1;
    transform: translateY(0);
  }
  .reveal-delay-1 { transition-delay: 0.1s; }
  .reveal-delay-2 { transition-delay: 0.2s; }
  .reveal-delay-3 { transition-delay: 0.3s; }
  .reveal-delay-4 { transition-delay: 0.4s; }
  .reveal-delay-5 { transition-delay: 0.5s; }

  .pricing-popular {
    background: linear-gradient(145deg, #0e0e0e, #0b0b0b);
    border: 1px solid var(--border-gold-strong);
    box-shadow: 0 0 60px var(--gold-glow), 0 0 120px rgba(212,162,76,0.05);
    transform: scale(1.03);
  }

  body::before {
    content: '';
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 9999;
    opacity: 0.4;
  }

  .label-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(212,162,76,0.08);
    border: 1px solid rgba(212,162,76,0.25);
    border-radius: 100px;
    padding: 4px 14px;
    font-size: 12px;
    font-family: 'Sora', sans-serif;
    font-weight: 500;
    color: var(--gold);
    letter-spacing: 0.05em;
    text-transform: uppercase;
  }

  .check-item::before {
    content: '';
    display: inline-block;
    width: 16px;
    height: 16px;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23d4a24c' stroke-width='2.5'%3E%3Cpolyline points='20 6 9 17 4 12'/%3E%3C/svg%3E") center/contain no-repeat;
    flex-shrink: 0;
  }

  .uncheck-item::before {
    content: '';
    display: inline-block;
    width: 16px;
    height: 16px;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23444' stroke-width='2.5'%3E%3Cline x1='18' y1='6' x2='6' y2='18'/%3E%3Cline x1='6' y1='6' x2='18' y2='18'/%3E%3C/svg%3E") center/contain no-repeat;
    flex-shrink: 0;
  }

  .grid-bg {
    background-image:
      linear-gradient(rgba(212,162,76,0.03) 1px, transparent 1px),
      linear-gradient(90deg, rgba(212,162,76,0.03) 1px, transparent 1px);
    background-size: 60px 60px;
  }

  .radial-glow {
    background: radial-gradient(ellipse 60% 50% at 50% 0%, rgba(212,162,76,0.12) 0%, transparent 70%);
  }

  #mobile-menu { display: none; }
  #mobile-menu.open { display: flex; }

  @media (max-width: 768px) {
    .pricing-popular { transform: scale(1); }
  }
</style>
</head>
<body class="bg-bg-deep">

<!-- ========== HEADER ========== -->
<header class="header-blur fixed top-0 left-0 right-0 z-50">
  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <nav class="flex items-center justify-between h-16">
      <a href="/" class="flex items-center gap-2">
        <img src="/Principal_1.png" alt="LumiClinic" style="height:42px;" />
      </a>

      <ul class="hidden md:flex items-center gap-8">
        <li><a href="#features" style="color:#9b9b9b; font-size:14px; font-family:'DM Sans',sans-serif; transition:color 0.2s;" onmouseover="this.style.color='#f5f5f5'" onmouseout="this.style.color='#9b9b9b'">Funcionalidades</a></li>
        <li><a href="#platform" style="color:#9b9b9b; font-size:14px; font-family:'DM Sans',sans-serif; transition:color 0.2s;" onmouseover="this.style.color='#f5f5f5'" onmouseout="this.style.color='#9b9b9b'">Plataforma</a></li>
        <li><a href="#pricing" style="color:#9b9b9b; font-size:14px; font-family:'DM Sans',sans-serif; transition:color 0.2s;" onmouseover="this.style.color='#f5f5f5'" onmouseout="this.style.color='#9b9b9b'">Preços</a></li>
        <li><a href="#faq" style="color:#9b9b9b; font-size:14px; font-family:'DM Sans',sans-serif; transition:color 0.2s;" onmouseover="this.style.color='#f5f5f5'" onmouseout="this.style.color='#9b9b9b'">FAQ</a></li>
      </ul>

      <div class="hidden md:flex items-center gap-3">
        <a href="/login" style="color:#9b9b9b; font-size:14px; font-family:'DM Sans',sans-serif; transition:color 0.2s;" onmouseover="this.style.color='#f5f5f5'" onmouseout="this.style.color='#9b9b9b'">Entrar</a>
        <a href="/criar-conta" class="btn-gold px-5 py-2 text-sm">Começar grátis</a>
      </div>

      <button class="md:hidden p-2" onclick="toggleMenu()" style="color:#9b9b9b;">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
          <rect y="3" width="20" height="2" rx="1"/><rect y="9" width="20" height="2" rx="1"/><rect y="15" width="20" height="2" rx="1"/>
        </svg>
      </button>
    </nav>
  </div>

  <div id="mobile-menu" class="flex-col gap-1 px-6 pb-4 border-t" style="border-color:var(--border-gold);">
    <a href="#features" class="block py-3 text-sm" style="color:#9b9b9b; border-bottom:1px solid var(--border-gold);">Funcionalidades</a>
    <a href="#platform" class="block py-3 text-sm" style="color:#9b9b9b; border-bottom:1px solid var(--border-gold);">Plataforma</a>
    <a href="#pricing" class="block py-3 text-sm" style="color:#9b9b9b; border-bottom:1px solid var(--border-gold);">Preços</a>
    <a href="#faq" class="block py-3 text-sm" style="color:#9b9b9b;">FAQ</a>
    <a href="/criar-conta" class="btn-gold block text-center px-5 py-2.5 text-sm mt-3">Começar grátis</a>
  </div>
</header>

<!-- ========== HERO ========== -->
<section class="relative min-h-screen flex items-center pt-16 overflow-hidden grid-bg">
  <div class="absolute inset-0" style="background: radial-gradient(ellipse 80% 60% at 65% 40%, rgba(212,162,76,0.08) 0%, transparent 65%);"></div>
  <div class="absolute top-0 left-0 right-0" style="height:1px; background:linear-gradient(90deg,transparent,rgba(212,162,76,0.2),transparent);"></div>

  <div class="max-w-7xl mx-auto px-6 lg:px-8 w-full py-24 lg:py-32">
    <div class="grid lg:grid-cols-2 gap-16 lg:gap-12 items-center">

      <!-- LEFT -->
      <div class="reveal">
        <div class="label-badge mb-8">
          <span style="width:6px;height:6px;background:var(--gold);border-radius:50%;display:inline-block;animation:pulse 2s infinite;"></span>
          Gestão clínica inteligente
        </div>

        <h1 style="font-family:'Sora',sans-serif; font-size:clamp(2.4rem,4.5vw,3.8rem); font-weight:700; line-height:1.1; letter-spacing:-0.03em; color:#f5f5f5; margin-bottom:1.5rem;">
          Sua clínica é<br>
          <span class="text-gold-gradient">excelente.</span><br>
          Sua gestão pode<br>ser também.
        </h1>

        <p style="color:#9b9b9b; font-size:16px; line-height:1.8; max-width:480px; margin-bottom:2.5rem;">
          A plataforma que centraliza agenda, prontuários, financeiro e relacionamento com pacientes em um único ambiente. Criada para profissionais que levam a sério o que fazem.
        </p>

        <div class="flex flex-wrap gap-3 mb-10">
          <a href="/criar-conta" class="btn-gold px-7 py-3.5" style="font-size:15px;">
            Começar gratuitamente
          </a>
          <a href="#platform" class="btn-outline px-7 py-3.5 flex items-center gap-2" style="font-size:15px;">
            Ver a plataforma
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 8h10M9 4l4 4-4 4"/></svg>
          </a>
        </div>

        <!-- Social proof -->
        <div class="flex items-center gap-4">
          <div class="flex -space-x-2">
            <div class="w-8 h-8 rounded-full border-2 skeleton" style="border-color:#0b0b0b;"></div>
            <div class="w-8 h-8 rounded-full border-2 skeleton" style="border-color:#0b0b0b;animation-delay:0.3s;"></div>
            <div class="w-8 h-8 rounded-full border-2 skeleton" style="border-color:#0b0b0b;animation-delay:0.6s;"></div>
            <div class="w-8 h-8 rounded-full border-2 skeleton" style="border-color:#0b0b0b;animation-delay:0.9s;"></div>
          </div>
          <div>
            <div class="flex items-center gap-1 mb-0.5">
              <span style="color:var(--gold);font-size:13px;">★★★★★</span>
            </div>
            <p style="color:#9b9b9b;font-size:12px;"><span style="color:#f5f5f5;font-weight:500;">+2.400 profissionais</span> já transformaram suas clínicas</p>
          </div>
        </div>
      </div>

      <!-- RIGHT — Mockup -->
      <div class="relative reveal reveal-delay-2">
        <div class="absolute inset-0" style="background: radial-gradient(ellipse 70% 60% at 50% 50%, rgba(212,162,76,0.12) 0%, transparent 70%); filter:blur(20px);"></div>
        <img src="/Imagem L com os mockups inicial - site lumiclinic.zip.png" alt="LumiClinic" style="position:relative;z-index:10;width:100%;max-width:580px;" />
      </div>
    </div>
  </div>
</section>

<!-- ========== DORES ========== -->
<section id="features" class="py-24 lg:py-32 relative">
  <div class="absolute inset-0" style="background:radial-gradient(ellipse 60% 40% at 50% 50%, rgba(212,162,76,0.04) 0%, transparent 70%);"></div>

  <div class="max-w-7xl mx-auto px-6 lg:px-8">
    <div class="text-center mb-16 reveal">
      <p style="color:var(--gold);font-size:13px;font-family:'Sora',sans-serif;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:16px;">O diagnóstico</p>
      <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.8rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.02em;">
        Ainda parece <span class="text-gold-gradient">familiar?</span>
      </h2>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="card-premium p-6 reveal">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/></svg>
        </div>
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Agenda no papel ou em planilha?</h3>
        <p style="color:#9b9b9b;font-size:13.5px;line-height:1.7;">Conflitos, falhas de comunicação e tempo perdido procurando informações básicas sobre os pacientes.</p>
      </div>

      <div class="card-premium p-6 reveal reveal-delay-1">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Prontuários desorganizados e lentos</h3>
        <p style="color:#9b9b9b;font-size:13.5px;line-height:1.7;">Minutos preciosos de atendimento perdidos procurando histórico clínico ou anotações de consultas anteriores.</p>
      </div>

      <div class="card-premium p-6 reveal reveal-delay-2">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">"Não sei quanto entrou este mês"</h3>
        <p style="color:#9b9b9b;font-size:13.5px;line-height:1.7;">Controle financeiro fragmentado, recebimentos esquecidos e relatórios impossíveis de gerar com precisão.</p>
      </div>

      <div class="card-premium p-6 reveal reveal-delay-1">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8a16 16 0 0 0 6.12 6.12l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7a2 2 0 0 1 1.72 2.02z"/></svg>
        </div>
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Pacientes que não retornam mais</h3>
        <p style="color:#9b9b9b;font-size:13.5px;line-height:1.7;">Sem acompanhamento automatizado, sem lembretes e sem relacionamento contínuo com quem já é seu paciente.</p>
      </div>

      <div class="card-premium p-6 reveal reveal-delay-2">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">"Sempre mais um app pra gerenciar"</h3>
        <p style="color:#9b9b9b;font-size:13.5px;line-height:1.7;">Dados espalhados em WhatsApp, planilha, sistema de agenda e app financeiro. Tudo separado, nada integrado.</p>
      </div>

      <div class="card-premium p-6 reveal reveal-delay-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <h3 style="font-family:'Sora',sans-serif;font-size:15px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Tempo gasto em burocracia absurda</h3>
        <p style="color:#9b9b9b;font-size:13.5px;line-height:1.7;">Horas por semana em confirmações manuais, cobranças, relatórios e tarefas que poderiam ser automatizadas.</p>
      </div>
    </div>

    <p class="text-center mt-10 reveal" style="color:#9b9b9b;font-size:14px;">
      Se você se identificou com pelo menos uma dessas situações, a LumiClinic foi feita para você.
    </p>
  </div>
</section>

<!-- ========== PLATAFORMA ========== -->
<section id="platform" class="py-24 lg:py-32 relative">
  <div class="max-w-7xl mx-auto px-6 lg:px-8 pt-16">
    <div class="grid lg:grid-cols-2 gap-16 items-start">

      <div class="reveal">
        <p style="color:var(--gold);font-size:13px;font-family:'Sora',sans-serif;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:16px;">A plataforma</p>
        <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.6rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.02em;margin-bottom:16px;">
          Uma plataforma.<br><span class="text-gold-gradient">Toda a sua gestão.</span>
        </h2>
        <p style="color:#9b9b9b;font-size:15px;line-height:1.8;margin-bottom:40px;">
          Construída com os melhores padrões do mercado, integra todos os módulos que sua clínica precisa em uma experiência fluida e moderna.
        </p>

        <div class="flex flex-col gap-1" id="tab-list">
          <button class="tab-btn active text-left px-5 py-4 rounded-xl border-l-2 transition-all" style="border-left-color:var(--gold);background:rgba(212,162,76,0.05);" data-tab="agenda" onclick="switchTab('agenda', this)">
            <div style="font-family:'Sora',sans-serif;font-size:14px;font-weight:600;color:var(--gold);">Agenda inteligente</div>
            <div style="color:#9b9b9b;font-size:13px;margin-top:2px;">Confirmação automática · Reminders · Online</div>
          </button>
          <button class="tab-btn text-left px-5 py-4 rounded-xl border-l-2" style="border-left-color:transparent;" data-tab="prontuario" onclick="switchTab('prontuario', this)">
            <div style="font-family:'Sora',sans-serif;font-size:14px;font-weight:600;color:#9b9b9b;">Prontuário eletrônico</div>
            <div style="color:#9b9b9b;font-size:13px;margin-top:2px;">Templates · Histórico · Assinatura digital</div>
          </button>
          <button class="tab-btn text-left px-5 py-4 rounded-xl border-l-2" style="border-left-color:transparent;" data-tab="financeiro" onclick="switchTab('financeiro', this)">
            <div style="font-family:'Sora',sans-serif;font-size:14px;font-weight:600;color:#9b9b9b;">Controle financeiro</div>
            <div style="color:#9b9b9b;font-size:13px;margin-top:2px;">Cobranças · Relatórios · DRE automático</div>
          </button>
          <button class="tab-btn text-left px-5 py-4 rounded-xl border-l-2" style="border-left-color:transparent;" data-tab="pacientes" onclick="switchTab('pacientes', this)">
            <div style="font-family:'Sora',sans-serif;font-size:14px;font-weight:600;color:#9b9b9b;">CRM de pacientes</div>
            <div style="color:#9b9b9b;font-size:13px;margin-top:2px;">Retenção · Fidelização · Campanha</div>
          </button>
          <button class="tab-btn text-left px-5 py-4 rounded-xl border-l-2" style="border-left-color:transparent;" data-tab="relatorios" onclick="switchTab('relatorios', this)">
            <div style="font-family:'Sora',sans-serif;font-size:14px;font-weight:600;color:#9b9b9b;">Relatórios & BI</div>
            <div style="color:#9b9b9b;font-size:13px;margin-top:2px;">Análise · Performance · Previsibilidade</div>
          </button>
        </div>
      </div>

      <div class="reveal reveal-delay-2">
        <div id="tab-panels">

          <!-- Agenda Panel -->
          <div class="tab-panel active" id="panel-agenda">
            <div class="card-premium p-0 overflow-hidden glow-gold" style="height:420px;">
              <div style="background:var(--bg-elevated);padding:14px 16px;border-bottom:1px solid var(--border-gold);display:flex;align-items:center;justify-content:space-between;">
                <div style="font-family:'Sora',sans-serif;font-size:13px;font-weight:600;color:#f5f5f5;">Agenda — Outubro 2024</div>
                <div class="flex gap-2">
                  <div class="w-6 h-6 skeleton rounded"></div>
                  <div class="w-6 h-6 skeleton rounded"></div>
                </div>
              </div>
              <div class="p-4 flex gap-3 h-full" style="height:calc(100% - 46px);">
                <div class="flex-shrink-0 w-28">
                  <div class="grid grid-cols-7 gap-0.5 mb-2">
                    <div class="text-center" style="color:#9b9b9b;font-size:9px;">D</div><div class="text-center" style="color:#9b9b9b;font-size:9px;">S</div><div class="text-center" style="color:#9b9b9b;font-size:9px;">T</div><div class="text-center" style="color:#9b9b9b;font-size:9px;">Q</div><div class="text-center" style="color:#9b9b9b;font-size:9px;">Q</div><div class="text-center" style="color:#9b9b9b;font-size:9px;">S</div><div class="text-center" style="color:#9b9b9b;font-size:9px;">S</div>
                  </div>
                  <div class="grid grid-cols-7 gap-0.5">
                    <div class="text-center rounded" style="font-size:9px;color:#9b9b9b;padding:2px;">1</div><div class="text-center rounded" style="font-size:9px;color:#9b9b9b;padding:2px;">2</div><div class="text-center rounded" style="font-size:9px;color:#9b9b9b;padding:2px;">3</div><div class="text-center rounded" style="font-size:9px;color:#9b9b9b;padding:2px;">4</div><div class="text-center rounded" style="font-size:9px;color:#9b9b9b;padding:2px;">5</div><div class="text-center rounded" style="font-size:9px;color:#9b9b9b;padding:2px;">6</div><div class="text-center rounded" style="font-size:9px;color:#9b9b9b;padding:2px;">7</div>
                    <div class="text-center rounded" style="font-size:9px;color:#9b9b9b;padding:2px;">8</div><div class="text-center rounded" style="font-size:9px;color:#9b9b9b;padding:2px;">9</div><div class="text-center rounded" style="font-size:9px;color:#9b9b9b;padding:2px;">10</div><div class="text-center rounded" style="font-size:9px;color:#9b9b9b;padding:2px;">11</div><div class="text-center rounded" style="font-size:9px;color:#9b9b9b;padding:2px;">12</div><div class="text-center rounded" style="font-size:9px;color:#9b9b9b;padding:2px;">13</div><div class="text-center rounded" style="font-size:9px;color:#9b9b9b;padding:2px;">14</div>
                    <div class="text-center rounded" style="font-size:9px;color:#9b9b9b;padding:2px;">15</div><div class="text-center rounded" style="font-size:9px;color:var(--gold);padding:2px;background:rgba(212,162,76,0.15);border-radius:4px;">16</div>
                  </div>
                </div>
                <div class="flex-1 flex flex-col gap-2 overflow-hidden">
                  <p style="font-size:11px;color:#9b9b9b;margin-bottom:4px;">Hoje, 16 de Outubro</p>
                  <div class="rounded-lg p-3 flex items-start gap-2" style="background:#111;border:1px solid rgba(74,222,128,0.2);">
                    <div style="width:3px;height:100%;min-height:36px;background:#4ade80;border-radius:2px;flex-shrink:0;"></div>
                    <div class="flex-1">
                      <div style="font-size:12px;color:#f5f5f5;font-family:'Sora',sans-serif;font-weight:500;">Ana Carolina M.</div>
                      <div style="font-size:11px;color:#9b9b9b;">08:30 · Consulta de retorno · 1h</div>
                    </div>
                    <div style="font-size:10px;color:#4ade80;background:rgba(74,222,128,0.1);padding:2px 8px;border-radius:100px;">Confirmado</div>
                  </div>
                  <div class="rounded-lg p-3 flex items-start gap-2" style="background:#111;border:1px solid var(--border-gold);">
                    <div style="width:3px;height:100%;min-height:36px;background:var(--gold);border-radius:2px;flex-shrink:0;"></div>
                    <div class="flex-1">
                      <div style="font-size:12px;color:#f5f5f5;font-family:'Sora',sans-serif;font-weight:500;">Pedro Henrique S.</div>
                      <div style="font-size:11px;color:#9b9b9b;">10:00 · Primeira consulta · 1h30</div>
                    </div>
                    <div style="font-size:10px;color:var(--gold);background:rgba(212,162,76,0.1);padding:2px 8px;border-radius:100px;">Aguardando</div>
                  </div>
                  <div class="rounded-lg p-3 flex items-start gap-2" style="background:#111;border:1px solid rgba(156,163,175,0.15);">
                    <div style="width:3px;height:100%;min-height:36px;background:#9b9b9b;border-radius:2px;flex-shrink:0;"></div>
                    <div class="flex-1">
                      <div style="font-size:12px;color:#f5f5f5;font-family:'Sora',sans-serif;font-weight:500;">Mariana Torres</div>
                      <div style="font-size:11px;color:#9b9b9b;">14:30 · Avaliação · 45min</div>
                    </div>
                    <div style="font-size:10px;color:#9b9b9b;background:rgba(155,155,155,0.1);padding:2px 8px;border-radius:100px;">Pendente</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-4 p-5 card-premium">
              <h3 style="font-family:'Sora',sans-serif;font-size:17px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Agenda inteligente</h3>
              <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Confirmações automáticas por WhatsApp, bloqueio inteligente de horários, link de agendamento online e lembretes personalizados. Menos faltas, mais previsibilidade.</p>
            </div>
          </div>

          <!-- Prontuário Panel -->
          <div class="tab-panel" id="panel-prontuario">
            <div class="card-premium p-6 glow-gold" style="height:420px;display:flex;flex-direction:column;gap:12px;">
              <div style="font-family:'Sora',sans-serif;font-size:13px;font-weight:600;color:#f5f5f5;">Prontuário — João Silveira</div>
              <div class="flex gap-3 flex-1">
                <div class="flex flex-col gap-2 flex-shrink-0 w-32">
                  <div style="background:#111;border:1px solid var(--border-gold);border-radius:8px;padding:8px;">
                    <div class="h-2 skeleton rounded mb-1"></div>
                    <div class="h-1.5 w-3/4 skeleton rounded"></div>
                  </div>
                  <div style="background:#111;border:1px solid var(--border-gold);border-radius:8px;padding:8px;">
                    <div class="h-2 skeleton rounded mb-1"></div>
                    <div class="h-1.5 w-3/4 skeleton rounded"></div>
                  </div>
                  <div style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold-strong);border-radius:8px;padding:8px;">
                    <div class="h-2 rounded mb-1" style="background:rgba(212,162,76,0.3);"></div>
                    <div class="h-1.5 w-3/4 rounded" style="background:rgba(212,162,76,0.2);"></div>
                  </div>
                </div>
                <div class="flex-1 flex flex-col gap-2">
                  <div style="background:#111;border:1px solid var(--border-gold);border-radius:8px;padding:12px;flex:1;">
                    <div class="h-2 skeleton rounded mb-2 w-1/2"></div>
                    <div class="h-1.5 skeleton rounded mb-1"></div>
                    <div class="h-1.5 skeleton rounded mb-1 w-5/6"></div>
                    <div class="h-1.5 skeleton rounded mb-1 w-4/5"></div>
                    <div class="h-1.5 skeleton rounded mb-1 w-full"></div>
                    <div class="h-1.5 skeleton rounded w-3/4"></div>
                  </div>
                  <div class="flex gap-2">
                    <div class="flex-1 h-8 btn-gold rounded-lg flex items-center justify-center">
                      <div class="h-2 w-16 rounded" style="background:rgba(5,5,5,0.3);"></div>
                    </div>
                    <div class="flex-1 h-8 btn-outline rounded-lg flex items-center justify-center">
                      <div class="h-2 w-16 rounded" style="background:rgba(245,245,245,0.1);"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-4 p-5 card-premium">
              <h3 style="font-family:'Sora',sans-serif;font-size:17px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Prontuário eletrônico</h3>
              <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Templates personalizados por especialidade, histórico completo, assinatura digital e acesso seguro em qualquer dispositivo. Tudo em conformidade com o CFM.</p>
            </div>
          </div>

          <!-- Financeiro Panel -->
          <div class="tab-panel" id="panel-financeiro">
            <div class="card-premium p-6 glow-gold" style="height:420px;">
              <div style="font-family:'Sora',sans-serif;font-size:13px;font-weight:600;color:#f5f5f5;margin-bottom:12px;">Financeiro — Outubro</div>
              <div class="grid grid-cols-2 gap-3 mb-3">
                <div style="background:#111;border:1px solid var(--border-gold);border-radius:10px;padding:12px;">
                  <div style="color:#9b9b9b;font-size:11px;margin-bottom:4px;">Recebido</div>
                  <div style="color:#4ade80;font-family:'Sora',sans-serif;font-size:22px;font-weight:700;">R$12.840</div>
                </div>
                <div style="background:#111;border:1px solid var(--border-gold);border-radius:10px;padding:12px;">
                  <div style="color:#9b9b9b;font-size:11px;margin-bottom:4px;">A receber</div>
                  <div style="color:var(--gold);font-family:'Sora',sans-serif;font-size:22px;font-weight:700;">R$4.200</div>
                </div>
              </div>
              <div style="background:#111;border:1px solid var(--border-gold);border-radius:10px;padding:12px;">
                <div style="color:#9b9b9b;font-size:11px;margin-bottom:8px;">Receitas por semana</div>
                <div class="flex items-end gap-1" style="height:60px;">
                  <div style="flex:1;background:rgba(212,162,76,0.3);border-radius:3px 3px 0 0;height:50%;"></div>
                  <div style="flex:1;background:rgba(212,162,76,0.5);border-radius:3px 3px 0 0;height:70%;"></div>
                  <div style="flex:1;background:rgba(212,162,76,0.4);border-radius:3px 3px 0 0;height:60%;"></div>
                  <div style="flex:1;background:var(--gold);border-radius:3px 3px 0 0;height:100%;"></div>
                </div>
              </div>
            </div>
            <div class="mt-4 p-5 card-premium">
              <h3 style="font-family:'Sora',sans-serif;font-size:17px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Controle financeiro</h3>
              <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Cobranças automáticas, DRE em tempo real, categorização de receitas e despesas, e relatórios que você finalmente entende.</p>
            </div>
          </div>

          <!-- CRM Panel -->
          <div class="tab-panel" id="panel-pacientes">
            <div class="card-premium p-6 glow-gold" style="height:420px;display:flex;flex-direction:column;gap:8px;">
              <div style="font-family:'Sora',sans-serif;font-size:13px;font-weight:600;color:#f5f5f5;margin-bottom:4px;">CRM de Pacientes</div>
              <div style="background:#111;border:1px solid var(--border-gold);border-radius:10px;padding:10px;display:flex;align-items:center;gap:10px;">
                <div class="w-8 h-8 rounded-full skeleton flex-shrink-0"></div>
                <div class="flex-1"><div class="h-2 skeleton rounded mb-1 w-2/3"></div><div class="h-1.5 skeleton rounded w-1/2"></div></div>
                <div style="font-size:10px;color:#4ade80;background:rgba(74,222,128,0.1);padding:2px 8px;border-radius:100px;">Ativo</div>
              </div>
              <div style="background:#111;border:1px solid var(--border-gold);border-radius:10px;padding:10px;display:flex;align-items:center;gap:10px;">
                <div class="w-8 h-8 rounded-full skeleton flex-shrink-0"></div>
                <div class="flex-1"><div class="h-2 skeleton rounded mb-1 w-3/4"></div><div class="h-1.5 skeleton rounded w-2/5"></div></div>
                <div style="font-size:10px;color:var(--gold);background:rgba(212,162,76,0.1);padding:2px 8px;border-radius:100px;">Retorno</div>
              </div>
              <div style="background:#111;border:1px solid var(--border-gold);border-radius:10px;padding:10px;display:flex;align-items:center;gap:10px;">
                <div class="w-8 h-8 rounded-full skeleton flex-shrink-0"></div>
                <div class="flex-1"><div class="h-2 skeleton rounded mb-1 w-1/2"></div><div class="h-1.5 skeleton rounded w-1/3"></div></div>
                <div style="font-size:10px;color:#9b9b9b;background:rgba(155,155,155,0.1);padding:2px 8px;border-radius:100px;">Inativo</div>
              </div>
              <div class="flex-1"></div>
              <div style="background:rgba(212,162,76,0.05);border:1px solid var(--border-gold);border-radius:10px;padding:10px;">
                <div style="font-size:11px;color:var(--gold);font-family:'Sora',sans-serif;margin-bottom:4px;">💬 Campanha agendada</div>
                <div class="h-1.5 skeleton rounded"></div>
              </div>
            </div>
            <div class="mt-4 p-5 card-premium">
              <h3 style="font-family:'Sora',sans-serif;font-size:17px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">CRM de pacientes</h3>
              <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Segmentação automática, campanhas de reativação, aniversários e follow-ups inteligentes. Seus pacientes voltam mais e indicam mais.</p>
            </div>
          </div>

          <!-- Relatórios Panel -->
          <div class="tab-panel" id="panel-relatorios">
            <div class="card-premium p-6 glow-gold" style="height:420px;display:flex;flex-direction:column;gap:12px;">
              <div style="font-family:'Sora',sans-serif;font-size:13px;font-weight:600;color:#f5f5f5;">Business Intelligence</div>
              <div class="grid grid-cols-3 gap-2">
                <div style="background:#111;border:1px solid var(--border-gold);border-radius:8px;padding:8px;text-align:center;">
                  <div style="color:var(--gold);font-family:'Sora',sans-serif;font-size:20px;font-weight:700;">↑24%</div>
                  <div style="color:#9b9b9b;font-size:10px;">Receita</div>
                </div>
                <div style="background:#111;border:1px solid var(--border-gold);border-radius:8px;padding:8px;text-align:center;">
                  <div style="color:#4ade80;font-family:'Sora',sans-serif;font-size:20px;font-weight:700;">96%</div>
                  <div style="color:#9b9b9b;font-size:10px;">Ocupação</div>
                </div>
                <div style="background:#111;border:1px solid var(--border-gold);border-radius:8px;padding:8px;text-align:center;">
                  <div style="color:#f5f5f5;font-family:'Sora',sans-serif;font-size:20px;font-weight:700;">4.9★</div>
                  <div style="color:#9b9b9b;font-size:10px;">NPS</div>
                </div>
              </div>
              <div style="background:#111;border:1px solid var(--border-gold);border-radius:10px;padding:12px;flex:1;">
                <div style="color:#9b9b9b;font-size:11px;margin-bottom:8px;">Tendência 6 meses</div>
                <div style="height:80px;position:relative;">
                  <svg viewBox="0 0 200 60" width="100%" height="100%" preserveAspectRatio="none">
                    <defs><linearGradient id="lineGrad" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="0%" stop-color="rgba(212,162,76,0.3)"/><stop offset="100%" stop-color="rgba(212,162,76,0)"/>
                    </linearGradient></defs>
                    <path d="M0,50 L30,42 L60,38 L90,28 L130,20 L170,12 L200,8" fill="none" stroke="var(--gold)" stroke-width="2"/>
                    <path d="M0,50 L30,42 L60,38 L90,28 L130,20 L170,12 L200,8 L200,60 L0,60Z" fill="url(#lineGrad)"/>
                  </svg>
                </div>
              </div>
            </div>
            <div class="mt-4 p-5 card-premium">
              <h3 style="font-family:'Sora',sans-serif;font-size:17px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Relatórios & BI</h3>
              <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Dashboards em tempo real, análise de performance por período, previsão de receita e relatórios exportáveis. Decisões baseadas em dados, não em achismos.</p>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== FUNDADORA ========== -->
<section class="py-24 lg:py-32 relative">
  <div class="max-w-7xl mx-auto px-6 lg:px-8 pt-16">
    <div class="grid lg:grid-cols-2 gap-16 items-center">

      <!-- Left: Doctor image -->
      <div class="reveal relative">
        <div class="relative" style="border-radius:24px;overflow:hidden;">
          <img src="/Imagem da mulher - site lumiclinic.png" alt="Dra. Letícia Brito" style="width:100%;border-radius:24px;" />

          <!-- Bottom info card -->
          <div style="position:absolute;bottom:20px;left:20px;right:20px;">
            <div style="background:rgba(11,11,11,0.9);border:1px solid var(--border-gold);border-radius:14px;padding:16px;backdrop-filter:blur(20px);">
              <div style="font-family:'Sora',sans-serif;font-size:14px;font-weight:600;color:#f5f5f5;margin-bottom:2px;">Dra. Letícia Brito</div>
              <div style="color:var(--gold);font-size:12px;">Fundadora da LumiClinic · 9 anos de gestão clínica · Especialista em harmonização facial</div>
            </div>
          </div>
        </div>

        <!-- Floating badge -->
        <div class="floating-card absolute" style="top:20px;right:-16px;padding:12px 16px;box-shadow:0 20px 50px rgba(0,0,0,0.5);">
          <div style="display:flex;align-items:center;gap:8px;">
            <div style="width:8px;height:8px;border-radius:50%;background:#4ade80;box-shadow:0 0 8px rgba(74,222,128,0.5);"></div>
            <div>
              <div style="font-family:'Sora',sans-serif;font-size:12px;font-weight:600;color:#f5f5f5;">3h recuperadas</div>
              <div style="font-size:11px;color:#9b9b9b;">por semana</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right: Content -->
      <div class="reveal reveal-delay-2">
        <p style="color:var(--gold);font-size:13px;font-family:'Sora',sans-serif;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:16px;">Por quem conhece</p>
        <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.4rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.02em;margin-bottom:20px;">
          Criada por quem conhece <span class="text-gold-gradient">cada dor</span> que você sente
        </h2>

        <!-- Quote -->
        <div style="border-left:2px solid var(--gold);padding-left:20px;margin-bottom:36px;">
          <p style="color:#f5f5f5;font-size:15px;line-height:1.8;font-style:italic;margin-bottom:8px;">"Eu não queria mais um software de gestão. Queria o software que eu precisava ter tido e que nenhum sistema me ofereceu. Então construí."</p>
          <p style="color:var(--gold);font-size:13px;font-family:'Sora',sans-serif;">— Dra. Letícia Brito, fundadora da LumiClinic.</p>
        </div>

        <!-- Stats grid -->
        <div class="grid grid-cols-3 gap-4">
          <div class="text-center p-4 card-premium">
            <div style="font-family:'Sora',sans-serif;font-size:26px;font-weight:700;color:var(--gold);">2.4k+</div>
            <div style="color:#9b9b9b;font-size:12px;margin-top:4px;">Profissionais</div>
          </div>
          <div class="text-center p-4 card-premium">
            <div style="font-family:'Sora',sans-serif;font-size:26px;font-weight:700;color:var(--gold);">98%</div>
            <div style="color:#9b9b9b;font-size:12px;margin-top:4px;">Satisfação</div>
          </div>
          <div class="text-center p-4 card-premium">
            <div style="font-family:'Sora',sans-serif;font-size:26px;font-weight:700;color:var(--gold);">3h</div>
            <div style="color:#9b9b9b;font-size:12px;margin-top:4px;">Economizadas/sem</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== BENEFÍCIOS ========== -->
<section class="py-24 lg:py-32 relative">
  <div class="absolute inset-0" style="background:radial-gradient(ellipse 50% 40% at 50% 50%, rgba(212,162,76,0.04) 0%, transparent 70%);"></div>

  <div class="max-w-7xl mx-auto px-6 lg:px-8 pt-16">
    <div class="text-center mb-16 reveal">
      <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.8rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.02em;line-height:1.2;">
        O que muda quando a gestão <span class="text-gold-gradient">para de ser<br>o problema</span>
      </h2>
    </div>

    <div class="grid md:grid-cols-3 gap-6">
      <div class="card-premium p-8 reveal">
        <div style="font-family:'Sora',sans-serif;font-size:48px;font-weight:800;color:var(--gold);line-height:1;margin-bottom:12px;">↑40%</div>
        <h3 style="font-family:'Sora',sans-serif;font-size:18px;font-weight:600;color:#f5f5f5;margin-bottom:10px;">Mais pacientes<br>atendidos</h3>
        <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Com confirmações automáticas e agenda otimizada, sua taxa de comparecimento chega a 96%. Mais receita, menos vaga desperdiçada.</p>
        <div class="mt-6 pt-6" style="border-top:1px solid var(--border-gold);">
          <div class="flex items-center gap-2">
            <div style="width:4px;height:4px;border-radius:50%;background:var(--gold);"></div>
            <span style="color:#9b9b9b;font-size:12px;">Redução de faltas em até 60%</span>
          </div>
        </div>
      </div>

      <div class="card-premium p-8 reveal reveal-delay-2">
        <div style="font-family:'Sora',sans-serif;font-size:48px;font-weight:800;color:var(--gold);line-height:1;margin-bottom:12px;">3h+</div>
        <h3 style="font-family:'Sora',sans-serif;font-size:18px;font-weight:600;color:#f5f5f5;margin-bottom:10px;">Liberadas por<br>semana</h3>
        <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Automatize confirmações, cobranças e follow-ups. Recupere horas que você hoje perde em tarefas manuais e use no que realmente importa.</p>
        <div class="mt-6 pt-6" style="border-top:1px solid var(--border-gold);">
          <div class="flex items-center gap-2">
            <div style="width:4px;height:4px;border-radius:50%;background:var(--gold);"></div>
            <span style="color:#9b9b9b;font-size:12px;">Automação de ponta a ponta</span>
          </div>
        </div>
      </div>

      <div class="card-premium p-8 reveal reveal-delay-4">
        <div style="font-family:'Sora',sans-serif;font-size:48px;font-weight:800;color:var(--gold);line-height:1;margin-bottom:12px;">2.4×</div>
        <h3 style="font-family:'Sora',sans-serif;font-size:18px;font-weight:600;color:#f5f5f5;margin-bottom:10px;">Mais retorno<br>de pacientes</h3>
        <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">CRM inteligente com follow-ups automáticos, campanhas de reativação e lembretes personalizados que trazem seus pacientes de volta.</p>
        <div class="mt-6 pt-6" style="border-top:1px solid var(--border-gold);">
          <div class="flex items-center gap-2">
            <div style="width:4px;height:4px;border-radius:50%;background:var(--gold);"></div>
            <span style="color:#9b9b9b;font-size:12px;">Fidelização automatizada</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== DIFERENCIAIS ========== -->
<section class="py-24 lg:py-32 relative">
  <div class="max-w-7xl mx-auto px-6 lg:px-8 pt-16">

    <div class="mb-16 reveal">
      <p style="color:var(--gold);font-size:13px;font-family:'Sora',sans-serif;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:16px;">Por que a LumiClinic</p>
      <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.6rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.02em;">
        A LumiClinic foi feita <span class="text-gold-gradient">para você</span>
      </h2>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div class="card-premium p-8 reveal" style="background:linear-gradient(145deg,rgba(212,162,76,0.04),transparent);">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
          </div>
          <div>
            <h3 style="font-family:'Sora',sans-serif;font-size:17px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Se você é um profissional de saúde, não precisa de TI</h3>
            <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Implantação em menos de 48h, suporte humano especializado e onboarding assistido. Você começa a usar no primeiro dia, sem dor.</p>
          </div>
        </div>
      </div>

      <div class="card-premium p-8 reveal reveal-delay-2" style="background:linear-gradient(145deg,rgba(212,162,76,0.04),transparent);">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <div>
            <h3 style="font-family:'Sora',sans-serif;font-size:17px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Seus dados são seus e sempre serão</h3>
            <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">LGPD nativa, criptografia em trânsito e em repouso, backup automático e acesso controlado. Conformidade total com o CFM.</p>
          </div>
        </div>
      </div>

      <div class="card-premium p-8 reveal reveal-delay-1" style="background:linear-gradient(145deg,rgba(212,162,76,0.04),transparent);">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
          </div>
          <div>
            <h3 style="font-family:'Sora',sans-serif;font-size:17px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Você tem um parceiro, não só um software</h3>
            <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Nossa equipe responde em menos de 2 horas. Você nunca ficará sozinho com um problema — seja às 7h ou às 22h.</p>
          </div>
        </div>
      </div>

      <div class="card-premium p-8 reveal reveal-delay-3" style="background:linear-gradient(145deg,rgba(212,162,76,0.04),transparent);">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(212,162,76,0.08);border:1px solid var(--border-gold);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="1.8"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          </div>
          <div>
            <h3 style="font-family:'Sora',sans-serif;font-size:17px;font-weight:600;color:#f5f5f5;margin-bottom:8px;">Você escala sem que a gestão atrapalhe</h3>
            <p style="color:#9b9b9b;font-size:14px;line-height:1.7;">Do profissional solo à clínica com múltiplas salas e equipe. A plataforma cresce com você sem mudar de sistema.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ========== PREÇOS ========== -->
<section id="pricing" class="py-24 lg:py-32 relative">
  <div class="absolute inset-0" style="background:radial-gradient(ellipse 60% 50% at 50% 30%, rgba(212,162,76,0.06) 0%, transparent 70%);"></div>

  <div class="max-w-7xl mx-auto px-6 lg:px-8 pt-16">
    <div class="text-center mb-16 reveal">
      <p style="color:var(--gold);font-size:13px;font-family:'Sora',sans-serif;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:16px;">Preços</p>
      <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.8rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.02em;margin-bottom:8px;">
        Transparência total.
      </h2>
      <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.8rem);font-weight:700;letter-spacing:-0.02em;" class="text-gold-gradient">
        Sem taxas escondidas.
      </h2>
    </div>

<?php
$displayPlans = array_filter($plans, fn($p) => (int)($p['price_cents'] ?? 0) > 0);
$displayPlans = array_values($displayPlans);
$totalPlans = count($displayPlans);
$planFeatures = [
    0 => ['Agenda inteligente', 'Prontuário eletrônico', 'Confirmação automática', 'Financeiro básico'],
    1 => ['Agenda inteligente', 'Prontuário eletrônico', 'Confirmação automática', 'Financeiro completo', 'CRM de pacientes', 'Marketing automatizado'],
    2 => ['Agenda inteligente', 'Prontuário eletrônico', 'Confirmação automática', 'Financeiro completo', 'CRM de pacientes', 'Marketing automatizado', 'BI avançado'],
];
$allFeatures = ['Agenda inteligente', 'Prontuário eletrônico', 'Confirmação automática', 'Financeiro completo', 'CRM de pacientes', 'Marketing automatizado', 'BI avançado'];
?>

    <div class="grid md:grid-cols-<?= $totalPlans ?> gap-6 items-stretch max-w-5xl mx-auto">
<?php foreach ($displayPlans as $i => $plan):
  $isPopular = ($i === 1);
  $priceCents = (int)($plan['price_cents'] ?? 0);
  $priceReais = intval($priceCents / 100);
  $name = $e($plan['name'] ?? 'Plano');
  $maxUsers = (int)($plan['max_users'] ?? 1);
  $maxPatients = (int)($plan['max_patients'] ?? 0);
  $storageMb = (int)($plan['storage_mb'] ?? 0);
  $storageGb = round($storageMb / 1000);
  $transcriptionMin = (int)($plan['transcription_minutes'] ?? 0);
  $trialDays = (int)($plan['trial_days'] ?? 14);
  $myFeatures = $planFeatures[$i] ?? $planFeatures[0];
  $delayClass = $i === 0 ? '' : ($i === 1 ? ' reveal-delay-2' : ' reveal-delay-4');
?>
      <div class="<?= $isPopular ? 'pricing-popular' : 'card-premium' ?> p-8 reveal<?= $delayClass ?> relative flex flex-col" style="border-radius:16px;min-height:600px;">
<?php if ($isPopular): ?>
        <div style="position:absolute;top:-14px;left:50%;transform:translateX(-50%);">
          <div class="btn-gold px-5 py-1.5" style="font-size:11px;letter-spacing:0.08em;text-transform:uppercase;border-radius:100px;">Mais popular</div>
        </div>
<?php endif; ?>

        <div style="font-family:'Sora',sans-serif;font-size:13px;font-weight:500;color:<?= $isPopular ? 'var(--gold)' : '#9b9b9b' ?>;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:20px;"><?= $name ?></div>
        <div class="mb-6">
          <div style="display:flex;align-items:baseline;gap:4px;">
            <span style="font-family:'Sora',sans-serif;font-size:14px;color:#9b9b9b;font-weight:400;">R$</span>
            <span style="font-family:'Sora',sans-serif;font-size:48px;font-weight:700;line-height:1;<?= $isPopular ? '' : 'color:#f5f5f5;' ?>" <?= $isPopular ? 'class="text-gold-gradient"' : '' ?>><?= $priceReais ?></span>
            <span style="font-family:'Sora',sans-serif;font-size:14px;color:#9b9b9b;">/mês</span>
          </div>
          <p style="color:#9b9b9b;font-size:13px;margin-top:6px;"><?= $maxUsers ?> usuário<?= $maxUsers > 1 ? 's' : '' ?> · <?= number_format($maxPatients) ?> pacientes · <?= $storageGb ?>GB · <?= $transcriptionMin ?>min transcrição</p>
        </div>

        <ul class="flex flex-col gap-3 mb-8 flex-1">
<?php foreach ($allFeatures as $feat):
  $has = in_array($feat, $myFeatures);
?>
          <li class="flex items-start gap-3 <?= $has ? 'check-item' : 'uncheck-item' ?>" style="color:<?= $has ? ($isPopular ? '#f5f5f5' : '#9b9b9b') : '#444' ?>;font-size:13.5px;"><?= $e($feat) ?></li>
<?php endforeach; ?>
        </ul>

        <a href="/criar-conta" class="<?= $isPopular ? 'btn-gold' : 'btn-outline' ?> block text-center px-6 py-3.5 text-sm">Começar grátis</a>
      </div>
<?php endforeach; ?>
    </div>

    <p class="text-center mt-10 reveal" style="color:#9b9b9b;font-size:13px;">
      Todos os planos incluem <?= (int)($displayPlans[0]['trial_days'] ?? 14) ?> dias gratuitos. Sem cartão de crédito. Cancele quando quiser.
    </p>
  </div>
</section>

<!-- ========== FAQ ========== -->
<section id="faq" class="py-24 lg:py-32 relative">
  <div class="max-w-3xl mx-auto px-6 lg:px-8 pt-16">

    <div class="text-center mb-16 reveal">
      <p style="color:var(--gold);font-size:13px;font-family:'Sora',sans-serif;letter-spacing:0.1em;text-transform:uppercase;margin-bottom:16px;">Dúvidas</p>
      <h2 style="font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3.5vw,2.6rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.02em;">
        Perguntas <span class="text-gold-gradient">frequentes</span>
      </h2>
    </div>

    <div class="flex flex-col gap-0 reveal">

      <div class="accordion-item" style="border-bottom:1px solid var(--border-gold);">
        <button class="accordion-trigger w-full text-left py-5 flex items-center justify-between gap-4" onclick="toggleAccordion(this)">
          <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:500;color:#f5f5f5;">Preciso instalar alguma coisa?</span>
          <span class="accordion-icon text-2xl flex-shrink-0" style="color:var(--gold);width:20px;height:20px;display:flex;align-items:center;justify-content:center;">+</span>
        </button>
        <div class="accordion-content">
          <p style="color:#9b9b9b;font-size:14px;line-height:1.8;padding-bottom:20px;">Não. A LumiClinic funciona 100% na nuvem, direto no navegador. Basta acessar de qualquer computador, tablet ou celular — sem downloads, sem instalações e sem atualizações manuais.</p>
        </div>
      </div>

      <div class="accordion-item" style="border-bottom:1px solid var(--border-gold);">
        <button class="accordion-trigger w-full text-left py-5 flex items-center justify-between gap-4" onclick="toggleAccordion(this)">
          <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:500;color:#f5f5f5;">Posso migrar meus dados?</span>
          <span class="accordion-icon text-2xl flex-shrink-0" style="color:var(--gold);width:20px;height:20px;display:flex;align-items:center;justify-content:center;">+</span>
        </button>
        <div class="accordion-content">
          <p style="color:#9b9b9b;font-size:14px;line-height:1.8;padding-bottom:20px;">Sim. Nossa equipe realiza a migração assistida de pacientes, histórico e agendamentos dos principais sistemas do mercado. O processo é seguro, sem interrupção no atendimento e você não perde nenhum dado.</p>
        </div>
      </div>

      <div class="accordion-item" style="border-bottom:1px solid var(--border-gold);">
        <button class="accordion-trigger w-full text-left py-5 flex items-center justify-between gap-4" onclick="toggleAccordion(this)">
          <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:500;color:#f5f5f5;">A transcrição com IA funciona em português?</span>
          <span class="accordion-icon text-2xl flex-shrink-0" style="color:var(--gold);width:20px;height:20px;display:flex;align-items:center;justify-content:center;">+</span>
        </button>
        <div class="accordion-content">
          <p style="color:#9b9b9b;font-size:14px;line-height:1.8;padding-bottom:20px;">Sim! Nossa IA de transcrição foi treinada para português brasileiro, incluindo termos médicos e jargões clínicos. Ela transcreve consultas em tempo real e gera resumos estruturados automaticamente.</p>
        </div>
      </div>

      <div class="accordion-item" style="border-bottom:1px solid var(--border-gold);">
        <button class="accordion-trigger w-full text-left py-5 flex items-center justify-between gap-4" onclick="toggleAccordion(this)">
          <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:500;color:#f5f5f5;">Meus dados ficam seguros?</span>
          <span class="accordion-icon text-2xl flex-shrink-0" style="color:var(--gold);width:20px;height:20px;display:flex;align-items:center;justify-content:center;">+</span>
        </button>
        <div class="accordion-content">
          <p style="color:#9b9b9b;font-size:14px;line-height:1.8;padding-bottom:20px;">Absolutamente. Utilizamos criptografia AES-256 em todos os dados, infraestrutura em nuvem com uptime de 99.9%, backup automático diário e conformidade total com a LGPD. Seus dados e os dos seus pacientes estão protegidos.</p>
        </div>
      </div>

      <div class="accordion-item" style="border-bottom:1px solid var(--border-gold);">
        <button class="accordion-trigger w-full text-left py-5 flex items-center justify-between gap-4" onclick="toggleAccordion(this)">
          <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:500;color:#f5f5f5;">Posso trocar de plano depois?</span>
          <span class="accordion-icon text-2xl flex-shrink-0" style="color:var(--gold);width:20px;height:20px;display:flex;align-items:center;justify-content:center;">+</span>
        </button>
        <div class="accordion-content">
          <p style="color:#9b9b9b;font-size:14px;line-height:1.8;padding-bottom:20px;">Sim, a qualquer momento. Você pode fazer upgrade ou downgrade diretamente pelo painel, sem burocracia. A cobrança é ajustada proporcionalmente no próximo ciclo.</p>
        </div>
      </div>

      <div class="accordion-item" style="border-bottom:1px solid var(--border-gold);">
        <button class="accordion-trigger w-full text-left py-5 flex items-center justify-between gap-4" onclick="toggleAccordion(this)">
          <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:500;color:#f5f5f5;">Tem suporte em português?</span>
          <span class="accordion-icon text-2xl flex-shrink-0" style="color:var(--gold);width:20px;height:20px;display:flex;align-items:center;justify-content:center;">+</span>
        </button>
        <div class="accordion-content">
          <p style="color:#9b9b9b;font-size:14px;line-height:1.8;padding-bottom:20px;">Sim! Todo o suporte é em português, via chat e WhatsApp, com tempo de resposta inferior a 2 horas. Todos os planos incluem onboarding assistido e central de ajuda com vídeos e tutoriais.</p>
        </div>
      </div>

      <div class="accordion-item">
        <button class="accordion-trigger w-full text-left py-5 flex items-center justify-between gap-4" onclick="toggleAccordion(this)">
          <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:500;color:#f5f5f5;">Funciona para clínica com mais de um profissional?</span>
          <span class="accordion-icon text-2xl flex-shrink-0" style="color:var(--gold);width:20px;height:20px;display:flex;align-items:center;justify-content:center;">+</span>
        </button>
        <div class="accordion-content">
          <p style="color:#9b9b9b;font-size:14px;line-height:1.8;padding-bottom:20px;">Sim! A LumiClinic foi projetada para escalar do profissional solo à clínica com múltiplas salas e equipe. Cada profissional tem sua agenda, prontuários e relatórios individuais, com visão consolidada para o gestor.</p>
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
      <h2 style="font-family:'Sora',sans-serif;font-size:clamp(2rem,5vw,4rem);font-weight:700;color:#f5f5f5;letter-spacing:-0.03em;line-height:1.1;margin-bottom:20px;">
        Sua clínica merece gestão<br><span class="text-gold-gradient">à altura do seu<br>atendimento.</span>
      </h2>

      <p style="color:#9b9b9b;font-size:16px;line-height:1.8;max-width:500px;margin:0 auto 48px;">
        Junte-se a mais de 2.400 profissionais que já transformaram sua gestão e voltaram a focar no que realmente importa: os pacientes.
      </p>

      <a href="/criar-conta" class="btn-gold inline-block px-12 py-5" style="font-size:16px;letter-spacing:0.01em;">
        Começar 14 dias grátis
      </a>

      <p style="color:#9b9b9b;font-size:13px;margin-top:16px;">Sem cartão de crédito · Cancele quando quiser · Suporte incluso</p>
    </div>
  </div>
</section>

<!-- ========== FOOTER ========== -->
<footer style="background:#050505;border-top:1px solid var(--border-gold);">
  <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16">
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-10 mb-14">
      <!-- Brand -->
      <div class="col-span-2 lg:col-span-2">
        <div class="flex items-center gap-2 mb-5">
          <img src="/Principal_1.png" alt="LumiClinic" style="height:36px;" />
        </div>
        <p style="color:#9b9b9b;font-size:14px;line-height:1.8;max-width:280px;margin-bottom:20px;">
          A plataforma de gestão clínica premium para profissionais de saúde que levam a sério o que fazem.
        </p>
        <div class="flex gap-3">
          <a href="#" style="width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,0.04);border:1px solid var(--border-gold);display:flex;align-items:center;justify-content:center;color:#9b9b9b;transition:all 0.3s;" onmouseover="this.style.borderColor='var(--gold)';this.style.color='var(--gold)'" onmouseout="this.style.borderColor='var(--border-gold)';this.style.color='#9b9b9b'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M24 4.56v14.91A4.56 4.56 0 0 1 19.44 24H4.56A4.56 4.56 0 0 1 0 19.44V4.56A4.56 4.56 0 0 1 4.56 0h14.88A4.56 4.56 0 0 1 24 4.56zM8 19V9H5v10h3zM6.5 7.5a1.75 1.75 0 1 0 0-3.5 1.75 1.75 0 0 0 0 3.5zM19 19v-5.5c0-2.5-1.5-3.5-3-3.5a2.9 2.9 0 0 0-2.5 1.4V9H11v10h2.5v-5.5c0-1.1.9-2 2-2s2 .9 2 2V19H19z"/></svg>
          </a>
          <a href="#" style="width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,0.04);border:1px solid var(--border-gold);display:flex;align-items:center;justify-content:center;color:#9b9b9b;transition:all 0.3s;" onmouseover="this.style.borderColor='var(--gold)';this.style.color='var(--gold)'" onmouseout="this.style.borderColor='var(--border-gold)';this.style.color='#9b9b9b'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>
          </a>
        </div>
      </div>

      <!-- Col 2 -->
      <div>
        <h4 style="font-family:'Sora',sans-serif;font-size:12px;font-weight:600;color:#f5f5f5;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:16px;">Produto</h4>
        <ul class="flex flex-col gap-3">
          <li><a href="#features" style="color:#9b9b9b;font-size:14px;transition:color 0.2s;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='#9b9b9b'">Funcionalidades</a></li>
          <li><a href="#pricing" style="color:#9b9b9b;font-size:14px;transition:color 0.2s;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='#9b9b9b'">Preços</a></li>
          <li><a href="/criar-conta" style="color:#9b9b9b;font-size:14px;transition:color 0.2s;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='#9b9b9b'">Começar grátis</a></li>
          <li><a href="/login" style="color:#9b9b9b;font-size:14px;transition:color 0.2s;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='#9b9b9b'">Entrar</a></li>
        </ul>
      </div>

      <!-- Col 3 -->
      <div>
        <h4 style="font-family:'Sora',sans-serif;font-size:12px;font-weight:600;color:#f5f5f5;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:16px;">Legal</h4>
        <ul class="flex flex-col gap-3">
          <li><a href="/settings/lgpd" style="color:#9b9b9b;font-size:14px;transition:color 0.2s;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='#9b9b9b'">Política de privacidade</a></li>
          <li><a href="#" style="color:#9b9b9b;font-size:14px;transition:color 0.2s;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='#9b9b9b'">Termos de uso</a></li>
          <li><a href="/portal/lgpd" style="color:#9b9b9b;font-size:14px;transition:color 0.2s;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='#9b9b9b'">LGPD</a></li>
        </ul>
      </div>

      <!-- Col 4 -->
      <div>
        <h4 style="font-family:'Sora',sans-serif;font-size:12px;font-weight:600;color:#f5f5f5;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:16px;">Suporte</h4>
        <ul class="flex flex-col gap-3">
          <li><a href="<?= $_waUrl ?>" target="_blank" style="color:#9b9b9b;font-size:14px;transition:color 0.2s;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='#9b9b9b'">Suporte via WhatsApp</a></li>
          <li><a href="#faq" style="color:#9b9b9b;font-size:14px;transition:color 0.2s;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='#9b9b9b'">FAQ</a></li>
        </ul>
      </div>
    </div>

    <div style="height:1px;background:linear-gradient(90deg,transparent,var(--border-gold-strong),transparent);margin-bottom:32px;"></div>

    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
      <p style="color:#9b9b9b;font-size:13px;">© 2026 LumiClinic. Todos os direitos reservados.</p>
      <div class="flex gap-6">
        <a href="/settings/lgpd" style="color:#9b9b9b;font-size:13px;transition:color 0.2s;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='#9b9b9b'">Política de Privacidade</a>
        <a href="#" style="color:#9b9b9b;font-size:13px;transition:color 0.2s;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='#9b9b9b'">Termos de Uso</a>
        <a href="/portal/lgpd" style="color:#9b9b9b;font-size:13px;transition:color 0.2s;" onmouseover="this.style.color='var(--gold)'" onmouseout="this.style.color='#9b9b9b'">LGPD</a>
      </div>
    </div>
  </div>
</footer>

<!-- ========== JAVASCRIPT ========== -->
<script>
  // Mobile Menu
  function toggleMenu() {
    const menu = document.getElementById('mobile-menu');
    menu.classList.toggle('open');
  }

  // Accordion
  function toggleAccordion(btn) {
    const content = btn.nextElementSibling;
    const icon = btn.querySelector('.accordion-icon');
    const isOpen = content.classList.contains('open');

    // Close all
    document.querySelectorAll('.accordion-content').forEach(el => el.classList.remove('open'));
    document.querySelectorAll('.accordion-icon').forEach(el => el.classList.remove('open'));

    if (!isOpen) {
      content.classList.add('open');
      icon.classList.add('open');
    }
  }

  // Tabs
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
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

  document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

  // Smooth scroll
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const href = this.getAttribute('href');
      if (href === '#') return;
      const target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.getElementById('mobile-menu').classList.remove('open');
      }
    });
  });

  // Header on scroll
  const header = document.querySelector('header');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 20) {
      header.style.background = 'rgba(5,5,5,0.95)';
    } else {
      header.style.background = 'rgba(5,5,5,0.8)';
    }
  });

  // Pulse keyframe for social proof dot
  const style = document.createElement('style');
  style.textContent = `
    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.5; transform: scale(0.8); }
    }
  `;
  document.head.appendChild(style);
</script>

</body>
</html>
