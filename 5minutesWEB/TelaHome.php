<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
  header('Location: entrarConta.php');
  exit;
}

include('INC/conexao.php');

$idUsuarioLogado = $_SESSION['usuario_id'];
$idComunidadePadrao = 1; // Certifique-se de ter rodado: INSERT INTO tb_comunidades (nm_comunidade) VALUES ('Geral'); no Supabase

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $acao = $_POST['acao'] ?? '';

  // --- NOVA DÚVIDA ---
  if ($acao == 'nova_duvida' && !empty(trim($_POST['ds_pergunta']))) {
    $sql = "INSERT INTO tb_perguntas (ds_pergunta, id_usuario, id_comunidade) 
            VALUES (:pergunta, :usuario, :comunidade)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':pergunta'   => trim($_POST['ds_pergunta']),
      ':usuario'    => $idUsuarioLogado,
      ':comunidade' => $idComunidadePadrao
    ]);
    header('Location: TelaHome.php#duvidas');
    exit;
  }

  // --- NOVA RESPOSTA ---
  if ($acao == 'nova_resposta' && !empty(trim($_POST['ds_resposta']))) {
    $sql = "INSERT INTO tb_respostas (ds_resposta, id_pergunta, id_usuario) 
            VALUES (:resposta, :pergunta, :usuario)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':resposta' => trim($_POST['ds_resposta']),
      ':pergunta' => (int)$_POST['id_pergunta'],
      ':usuario'  => $idUsuarioLogado
    ]);
    header('Location: TelaHome.php#duvidas');
    exit;
  }

  // --- NOVO VÍDEO ---
  if ($acao == 'novo_video' && isset($_FILES['vidFile']) && $_FILES['vidFile']['error'] == UPLOAD_ERR_OK) {
    $pastaDestino = 'uploads/videos/';
    if (!is_dir($pastaDestino)) {
      mkdir($pastaDestino, 0777, true);
    }

    $extensao = pathinfo($_FILES['vidFile']['name'], PATHINFO_EXTENSION);
    $nomeArquivo = uniqid('vid_') . '.' . $extensao;
    $caminhoFinal = $pastaDestino . $nomeArquivo;

    if (move_uploaded_file($_FILES['vidFile']['tmp_name'], $caminhoFinal)) {
      $titulo = trim($_POST['nm_titulo'] ?? '');
      if (empty($titulo)) {
        $titulo = pathinfo($_FILES['vidFile']['name'], PATHINFO_FILENAME);
      }

      $sql = "INSERT INTO tb_publicacoes (nm_titulo, ds_video_publicacao, id_usuario, id_comunidade) 
              VALUES (:titulo, :video, :usuario, :comunidade)";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ':titulo'     => $titulo,
        ':video'      => $caminhoFinal,
        ':usuario'    => $idUsuarioLogado,
        ':comunidade' => $idComunidadePadrao
      ]);
    }
    header('Location: TelaHome.php#videos');
    exit;
  }
}

// Buscar Vídeos do Banco
$stmtVideos = $pdo->query("
  SELECT p.*, u.nm_usuario 
  FROM tb_publicacoes p
  JOIN tb_usuarios u ON p.id_usuario = u.cd_usuario
  WHERE p.ds_video_publicacao IS NOT NULL
  ORDER BY p.dt_publicacao DESC
");
$listaVideos = $stmtVideos->fetchAll(PDO::FETCH_ASSOC);

// Buscar Dúvidas do Banco
$stmtPerguntas = $pdo->query("
  SELECT p.*, u.nm_usuario 
  FROM tb_perguntas p
  JOIN tb_usuarios u ON p.id_usuario = u.cd_usuario
  ORDER BY p.cd_pergunta DESC
");
$listaPerguntas = $stmtPerguntas->fetchAll(PDO::FETCH_ASSOC);

// Preparar query de respostas
$stmtRespostas = $pdo->prepare("
  SELECT r.*, u.nm_usuario 
  FROM tb_respostas r
  JOIN tb_usuarios u ON r.id_usuario = u.cd_usuario
  WHERE r.id_pergunta = :id_pergunta
  ORDER BY r.cd_resposta ASC
");
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>5 minutos</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@400;600&family=Sora:wght@400;500;600&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

  <style>
    :root {
      --bg: #F9F8F4;
      --surface: #FFFFFF;
      --ink: #2B2922;
      --muted: #6A665A;
      --gold: #E2B93B;
      --gold-soft: #FBF1CB;
      --gold-band: #F3E4A6;
      --gold-ink: #7C5E00;
      --line: #D8D3C2;
      --field: #EDEBE4;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      background-color: var(--bg) !important;
      color: var(--ink) !important;
      font-family: 'Sora', sans-serif !important;
      font-size: 15px !important;
      line-height: 1.5 !important;
    }

    button, input, textarea {
      font-family: inherit;
      font-size: inherit;
      color: inherit;
    }

    a {
      color: inherit !important;
      text-decoration: none !important;
    }

    [hidden] {
      display: none !important;
    }

    .header {
      position: sticky;
      top: 0;
      z-index: 20;
      background-color: var(--gold-band);
      border-bottom: 1px solid var(--gold);
      padding: 10px 20px;
      height: 72px;
      display: flex;
      align-items: center;
    }

    .header-content {
      width: 100%;
      max-width: 1100px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }

    .logo {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      font-family: 'Fraunces', serif;
      font-size: 28px;
      font-weight: 600;
      line-height: 1;
    }

    .logo span {
      color: var(--gold-ink);
    }

    .logo-img {
      height: 46px;
      width: auto;
      max-width: 140px;
      border-radius: 10px;
      object-fit: contain;
      border: 1.5px solid var(--gold-ink);
      background-color: var(--surface);
      padding: 2px;
      display: block;
      flex-shrink: 0;
    }

    .sidebar {
      position: fixed;
      top: 72px;
      left: 0;
      bottom: 0;
      width: 88px;
      background-color: var(--gold-band);
      border-right: 1px solid var(--gold);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 16px 4px;
      gap: 8px;
      z-index: 15;
      overflow-y: auto;
    }

    .sidebar a {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
      width: 72px;
      padding: 10px 4px;
      border-radius: 10px;
      font-size: 11px;
      text-align: center;
      transition: background-color 0.2s;
      flex-shrink: 0;
    }

    .sidebar a:hover,
    .sidebar a.active {
      background-color: var(--gold);
    }

    .sidebar .logout-link {
      margin-top: auto;
      color: #a82020 !important;
    }

    .sidebar .logout-link:hover {
      background-color: #f8d7da;
    }

    .sidebar-divider {
      width: 56px;
      height: 1px;
      background-color: rgba(124, 94, 0, 0.3);
      margin: 4px 0;
      flex-shrink: 0;
    }

    .sidebar-favs {
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
    }

    .sidebar-favs-title {
      font-size: 10px;
      font-weight: 600;
      color: var(--gold-ink);
      text-transform: uppercase;
      letter-spacing: 0.4px;
      text-align: center;
    }

    .sidebar-fav-item {
      width: 76px;
      background-color: var(--surface);
      border: 1px solid var(--gold);
      border-radius: 8px;
      padding: 6px;
      font-size: 10px;
      line-height: 1.25;
      color: var(--ink);
      text-align: left;
      cursor: pointer;
      transition: transform 0.15s, background-color 0.15s;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .sidebar-fav-item:hover {
      background-color: var(--gold-soft);
      transform: scale(1.03);
    }

    .main-container {
      max-width: 1100px;
      margin: 0 auto;
      padding: 28px 20px 60px 108px;
      display: grid;
      grid-template-columns: repeat(12, 1fr);
      gap: 24px;
    }

    section {
      scroll-margin-top: 88px;
    }

    .grid-12 { grid-column: span 12; }
    .grid-7 { grid-column: span 7; }
    .grid-5 { grid-column: span 5; }
    .grid-4 { grid-column: span 4; }

    h1 {
      font-family: 'Fraunces', serif !important;
      font-size: 36px !important;
      font-weight: 400 !important;
      color: var(--muted) !important;
      margin-bottom: 16px !important;
    }

    h2 {
      font-family: 'Fraunces', serif !important;
      font-size: 24px !important;
      font-weight: 600 !important;
      margin-bottom: 14px !important;
      color: var(--ink) !important;
    }

    .muted {
      color: var(--muted);
      font-size: 13px;
    }

    .badge-custom {
      font-size: 10px;
      text-transform: uppercase;
      padding: 2px 8px;
      border-radius: 12px;
      background-color: var(--gold-soft);
      color: var(--gold-ink);
      border: 1px solid var(--gold);
      margin-left: 6px;
    }

    .quote-box {
      border: 2px solid var(--ink);
      border-radius: 12px;
      padding: 18px 20px;
      background-color: var(--surface);
      font-family: 'Fraunces', serif;
      font-size: 20px;
    }

    .card-box {
      background-color: var(--surface);
      border: 1.5px solid var(--gold);
      border-radius: 12px;
      padding: 18px;
    }

    .card-box.soft {
      background-color: var(--gold-soft);
    }

    .btn-gold {
      background-color: var(--gold);
      border: 1.5px solid var(--gold-ink);
      border-radius: 8px;
      padding: 8px 16px;
      font-family: 'Fraunces', serif;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      color: var(--ink);
    }

    .btn-gold:hover {
      filter: brightness(1.04);
    }

    .btn-ghost {
      background-color: transparent;
      border: 1px solid var(--line);
      border-radius: 8px;
      padding: 6px 12px;
      font-size: 13px;
      cursor: pointer;
    }

    .btn-ghost:hover {
      background-color: var(--field);
    }

    .btn-small {
      padding: 4px 10px;
      font-size: 12px;
    }

    .btn-spotify {
      display: inline-block;
      background-color: var(--gold);
      color: var(--ink) !important;
      border: 1px solid var(--gold-ink);
      border-radius: 8px;
      padding: 5px 12px;
      font-size: 12px;
      font-weight: 500;
      cursor: pointer;
    }

    .btn-spotify:hover {
      background-color: var(--gold-soft);
    }

    .input {
      width: 100%;
      padding: 10px 12px;
      border: 1.5px solid var(--gold);
      border-radius: 8px;
      background-color: var(--field);
      margin-bottom: 10px;
      outline: none;
    }

    .input:focus {
      background-color: var(--surface);
    }

    .file-input {
      padding: 7px 12px;
      cursor: pointer;
    }

    .file-input::file-selector-button {
      background-color: var(--gold-soft);
      border: 1px solid var(--gold-ink);
      color: var(--ink);
      border-radius: 6px;
      padding: 4px 10px;
      margin-right: 10px;
      font-family: 'Fraunces', serif;
      cursor: pointer;
    }

    .flex-row {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .cal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-family: 'Fraunces', serif;
      font-size: 17px;
      margin-bottom: 6px;
    }

    .cal-nav button {
      background: transparent;
      border: none;
      font-size: 18px;
      cursor: pointer;
      padding: 2px 8px;
      border-radius: 6px;
    }

    .cal-nav button:hover {
      background-color: var(--gold);
    }

    .cal-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      margin-top: 10px;
      border-top: 1px solid var(--ink);
      border-left: 1px solid var(--ink);
      background-color: var(--surface);
    }

    .cal-grid div {
      text-align: center;
      padding: 6px 0;
      border-right: 1px solid var(--ink);
      border-bottom: 1px solid var(--ink);
      font-size: 13px;
    }

    .cal-dow {
      font-weight: 600;
      background-color: var(--gold-band);
    }

    .cal-today {
      background-color: var(--gold);
      font-weight: 600;
    }

    .cal-blank {
      opacity: 0.4;
    }

    .quick-links {
      display: flex;
      flex-direction: column;
      gap: 4px;
      height: 100%;
      justify-content: space-between;
    }

    .quick-links a {
      font-size: 20px;
      color: var(--muted) !important;
      padding: 6px 10px;
      border-radius: 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .quick-links a:hover {
      background-color: var(--gold-soft);
      color: var(--ink) !important;
    }

    .quick-links small {
      font-size: 12px;
      color: var(--gold-ink);
      font-weight: 600;
    }

    .quick-links hr {
      border: none;
      height: 4px;
      background-color: var(--gold);
      border-radius: 2px;
      margin-top: 10px;
      opacity: 1;
    }

    .timer-presets {
      display: flex;
      justify-content: center;
      gap: 8px;
    }

    .timer-presets button.active {
      background-color: var(--gold);
      border-color: var(--gold-ink);
      font-weight: 600;
    }

    .timer-display {
      font-family: 'Fraunces', serif;
      font-size: 60px;
      text-align: center;
      margin: 14px 0;
      line-height: 1;
    }

    .progress-bar-custom {
      height: 8px;
      background-color: var(--field);
      border-radius: 10px;
      overflow: hidden;
      margin-bottom: 14px;
    }

    .progress-fill {
      height: 100%;
      width: 0%;
      background-color: var(--gold);
      transition: width 0.3s linear;
    }

    .list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-top: 14px;
    }

    .grid-cards {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 14px;
      margin-top: 14px;
    }

    .item {
      background-color: var(--surface);
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 14px;
    }

    .item h3 {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .item p {
      white-space: pre-wrap;
      margin-bottom: 0;
    }

    .item-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 10px;
      font-size: 12px;
      color: var(--muted);
    }

    .empty-msg {
      padding: 16px;
      border: 1.5px dashed var(--line);
      border-radius: 10px;
      text-align: center;
      color: var(--muted);
      grid-column: 1 / -1;
    }

    .video-player-box {
      width: 100%;
      aspect-ratio: 16 / 9;
      background-color: #1F1E19;
      border: 1px solid var(--gold);
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 10px;
    }

    .video-player-box video {
      width: 100%;
      height: 100%;
      object-fit: contain;
      display: block;
    }

    .reply {
      margin: 8px 0 0 12px;
      padding-left: 10px;
      border-left: 3px solid var(--gold);
      font-size: 14px;
    }

    .reply-form {
      display: flex;
      gap: 8px;
      margin-top: 10px;
    }

    .reply-form input {
      margin-bottom: 0;
    }

    .btn-like {
      border: 1px solid var(--line);
      background: transparent;
      border-radius: 20px;
      padding: 3px 12px;
      font-size: 12px;
      cursor: pointer;
    }

    .btn-like.liked {
      background-color: var(--gold);
      border-color: var(--gold-ink);
    }

    .footer {
      background-color: var(--gold-band);
      border-top: 1.5px solid var(--gold);
      padding: 32px 20px 32px 108px;
      margin-top: 20px;
    }

    .footer-content {
      max-width: 1100px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 24px;
    }

    .footer-brand {
      display: flex;
      flex-direction: column;
      gap: 6px;
      max-width: 340px;
    }

    .footer-logo {
      display: flex;
      align-items: center;
      gap: 8px;
      font-family: 'Fraunces', serif;
      font-size: 22px;
      font-weight: 600;
      color: var(--ink);
    }

    .footer-logo span {
      color: var(--gold-ink);
    }

    .footer-bottom {
      max-width: 1100px;
      margin: 20px auto 0 auto;
      padding-top: 16px;
      border-top: 1px solid rgba(124, 94, 0, 0.2);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px;
      font-size: 12px;
      color: var(--muted);
    }

    .btn-top {
      font-family: 'Fraunces', serif;
      font-size: 13px;
      font-weight: 600;
      color: var(--gold-ink) !important;
      padding: 4px 10px;
      border-radius: 6px;
      background-color: var(--surface);
      border: 1px solid var(--gold);
    }

    .btn-top:hover {
      background-color: var(--gold-soft);
    }

    .toast-custom {
      position: fixed;
      bottom: 24px;
      left: 50%;
      transform: translateX(-50%);
      background-color: var(--ink);
      color: var(--bg);
      padding: 10px 20px;
      border-radius: 20px;
      font-size: 14px;
      z-index: 50;
    }

    @media (max-width: 900px) {
      .grid-7, .grid-5, .grid-4 {
        grid-column: span 12;
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        top: auto;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        height: 64px;
        flex-direction: row;
        justify-content: space-around;
        padding: 4px 0;
        border-right: none;
        border-top: 1px solid var(--gold);
        overflow-y: hidden;
      }

      .sidebar .logout-link {
        margin-top: 0;
      }

      .sidebar-divider, .sidebar-favs {
        display: none;
      }

      .main-container {
        padding: 20px 16px 40px 16px;
      }

      .footer {
        padding: 28px 16px 88px 16px;
      }

      .logo {
        font-size: 24px;
      }

      .logo-img {
        height: 38px;
      }

      .toast-custom {
        bottom: 80px;
      }
    }
  </style>
</head>

<body>

  <header class="header">
    <div class="header-content">
      <a class="logo" href="#inicio">
        <img src="logo.jpg" alt="5Minutes" class="logo-img" onerror="this.style.display='none'; document.getElementById('fallbackLogoSvg').style.display='block';">
        <svg id="fallbackLogoSvg" style="display: none;" width="28" height="36" viewBox="0 0 34 44" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M5 3h24M5 41h24" />
          <path d="M8 3c0 9 5 11 9 16-4 5-9 7-9 22M26 3c0 9-5 11-9 16 4 5 9 7 9 22" />
          <path d="M13 36c2-3 6-3 8 0" stroke="var(--gold-ink)" />
        </svg>
        <div>5Minutes<span>.</span></div>
      </a>
      
      <span class="muted" style="font-weight: 500;">Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome'] ?? 'Estudante'); ?>!</span>
    </div>
  </header>

  <nav class="sidebar">
    <a href="#inicio" class="active">
      <svg width="22" height="22" viewBox="0 0 28 28" fill="currentColor">
        <path d="M14 3L2 14h3.5v10h6v-7h5v7h6V14H26z" />
      </svg>
      Inicio
    </a>

    <a href="#anotacoes">
      <svg width="22" height="22" viewBox="0 0 28 28" fill="none" stroke="currentColor" stroke-width="3">
        <path d="M14 3v22M3 14h22" />
      </svg>
      Anotar
    </a>

    <a href="#curiosidades">
      <svg width="22" height="22" viewBox="0 0 28 28" fill="currentColor">
        <path d="M14 2a8 8 0 0 0-4.5 14.6c.9.7 1.5 1.6 1.5 2.7V21h6v-1.7c0-1.1.6-2 1.5-2.7A8 8 0 0 0 14 2z" />
        <rect x="11" y="22.5" width="6" height="2.2" rx="1" />
      </svg>
      Curiosidades
    </a>

    <div class="sidebar-divider"></div>

    <div class="sidebar-favs">
      <span class="sidebar-favs-title">★ Favoritas</span>
      <div id="sidebarFavList" style="display: flex; flex-direction: column; gap: 6px; align-items: center;"></div>
    </div>

    <a href="logout.php" class="logout-link" title="Sair da conta">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
        <polyline points="16 17 21 12 16 7"></polyline>
        <line x1="21" y1="12" x2="9" y2="12"></line>
      </svg>
      Sair
    </a>
  </nav>

  <main class="main-container">

    <section id="inicio" class="grid-12">
      <h1>Olá</h1>
      <div class="quote-box">
        Cinco minutos de foco hoje valem mais do que duas horas de distração.
      </div>
    </section>

    <section class="grid-4 card-box soft">
      <div class="cal-header">
        <span id="calToday" style="text-decoration: underline;"></span>
        <div class="cal-nav">
          <button type="button" id="calPrev">‹</button>
          <button type="button" id="calNext">›</button>
        </div>
      </div>
      <div id="calMonth" style="font-family: 'Fraunces', serif; font-size: 17px;"></div>
      <div class="cal-grid" id="calGrid"></div>
    </section>

    <section class="grid-4">
      <nav class="quick-links">
        <a href="#videos">Meus vídeos</a>
        <a href="#anotacoes">Anotações <small id="notesCount"></small></a>
        <a href="#tempo">Tempo</a>
        <a href="#curiosidades">Curiosidades</a>
        <a href="#musicas">Músicas</a>
        <a href="#duvidas">Dúvidas</a>
        <hr>
      </nav>
    </section>

    <section id="timer" class="grid-4 card-box">
      <h2>Tempo</h2>
      <div class="timer-presets" id="timerPresets">
        <button type="button" class="btn-ghost active" data-min="5">5 mim</button>
        <button type="button" class="btn-ghost" data-min="15">15 mim</button>
        <button type="button" class="btn-ghost" data-min="25">25 mim</button>
      </div>

      <div class="timer-display" id="timerClock">05:00</div>
      <div class="progress-bar-custom">
        <div class="progress-fill" id="timerProgress"></div>
      </div>
      <div class="flex-row" style="justify-content: center; margin-bottom: 10px;">
        <button type="button" class="btn-gold" id="btnTimerToggle">Começar</button>
        <button type="button" class="btn-ghost" id="btnTimerReset">Zerar</button>
      </div>
      <p class="muted" style="text-align: center; margin: 0;">Estudado hoje <b id="todayMinutes">0</b> min</p>
    </section>

    <section id="anotacoes" class="grid-12">
      <h2>Anotações</h2>
      <form id="noteForm" class="card-box">
        <input class="input" id="noteTitle" placeholder="Título (opcional)" maxlength="80">
        <textarea class="input" id="noteBody" rows="3" placeholder="Escreva o que você acabou de aprender..." required></textarea>
        <div class="flex-row">
          <button class="btn-gold" type="submit">Salvar anotação</button>
          <span class="muted">Foi Salvo</span>
        </div>
      </form>
      <div class="list" id="notesList"></div>
    </section>

    <!-- SEÇÃO VÍDEOS CONECTADA AO BANCO -->
    <section id="videos" class="grid-12">
      <h2>Meus vídeos</h2>
      <form action="TelaHome.php" method="POST" enctype="multipart/form-data" class="card-box flex-row">
        <input type="hidden" name="acao" value="novo_video">
        <input class="input" style="flex: 1; min-width: 180px; margin-bottom: 0;" name="nm_titulo" placeholder="Nome do vídeo (opcional)" maxlength="150">
        <input class="input file-input" style="flex: 2; min-width: 220px; margin-bottom: 0;" name="vidFile" type="file" accept="video/*" required>
        <button class="btn-gold" type="submit">Salvar vídeo</button>
      </form>

      <div class="grid-cards">
        <?php if (empty($listaVideos)): ?>
          <div class="empty-msg">Nenhum vídeo foi guardado ainda.</div>
        <?php else: ?>
          <?php foreach ($listaVideos as $vid): ?>
            <article class="item">
              <div class="video-player-box">
                <video src="<?php echo htmlspecialchars($vid['ds_video_publicacao']); ?>" controls preload="metadata"></video>
              </div>
              <h3><?php echo htmlspecialchars($vid['nm_titulo']); ?></h3>
              <div class="item-meta">
                <span>Por <?php echo htmlspecialchars($vid['nm_usuario']); ?></span>
                <span><?php echo date('d/m/Y', strtotime($vid['dt_publicacao'])); ?></span>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- SEÇÃO DÚVIDAS E RESPOSTAS CONECTADA AO BANCO -->
    <section id="duvidas" class="grid-7">
      <h2>Tem alguma dúvida? Coloque aqui.</h2>
      <form action="TelaHome.php" method="POST" class="card-box">
        <input type="hidden" name="acao" value="nova_duvida">
        <textarea class="input" name="ds_pergunta" rows="3" placeholder="Escreva sua dúvida aqui" maxlength="500" required></textarea>
        <button class="btn-gold" type="submit">Enviar dúvida</button>
      </form>

      <div class="list">
        <?php if (empty($listaPerguntas)): ?>
          <div class="empty-msg">Nenhuma dúvida publicada ainda.</div>
        <?php else: ?>
          <?php foreach ($listaPerguntas as $perg): ?>
            <?php
              $stmtRespostas->execute([':id_pergunta' => $perg['cd_pergunta']]);
              $respostas = $stmtRespostas->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <article class="item">
              <p>
                <b><?php echo htmlspecialchars($perg['ds_pergunta']); ?></b>
                <span class="badge-custom"><?php echo htmlspecialchars($perg['nm_usuario']); ?></span>
              </p>

              <?php foreach ($respostas as $resp): ?>
                <div class="reply">
                  <strong><?php echo htmlspecialchars($resp['nm_usuario']); ?>:</strong>
                  <?php echo htmlspecialchars($resp['ds_resposta']); ?>
                </div>
              <?php endforeach; ?>

              <form action="TelaHome.php" method="POST" class="reply-form">
                <input type="hidden" name="acao" value="nova_resposta">
                <input type="hidden" name="id_pergunta" value="<?php echo $perg['cd_pergunta']; ?>">
                <input class="input" name="ds_resposta" placeholder="Adicionar comentário..." maxlength="500" required>
                <button class="btn-ghost btn-small" type="submit">Responder</button>
              </form>

              <div class="item-meta">
                <span><?php echo count($respostas); ?> resposta(s)</span>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <section id="musicas" class="grid-5">
      <h2>Músicas</h2>
      <div class="list" id="musicList"></div>
    </section>

    <section id="curiosidades" class="grid-12">
      <h2>Curiosidades de nerds</h2>
      <form id="factForm" class="card-box">
        <textarea class="input" id="factBody" rows="2" placeholder="Compartilhe uma curiosidade nerd que você descobriu" required></textarea>
        <button class="btn-gold" type="submit">Enviar</button>
      </form>
      <div class="grid-cards" id="factList"></div>
    </section>

  </main>

  <footer class="footer">
    <div class="footer-content">
      <div class="footer-brand">
        <a class="footer-logo" href="#inicio">
          <svg width="22" height="28" viewBox="0 0 34 44" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M5 3h24M5 41h24" />
            <path d="M8 3c0 9 5 11 9 16-4 5-9 7-9 22M26 3c0 9-5 11-9 16 4 5 9 7 9 22" />
            <path d="M13 36c2-3 6-3 8 0" stroke="var(--gold-ink)" />
          </svg>
          <div>5Minutes<span>.</span></div>
        </a>
        <p class="muted" style="margin: 0;">Cinco minutos de foco todos os dias para transformar a sua rotina de estudos.</p>
      </div>

      <div class="footer-bottom">
        <span>© 2026 5Minutes. Feito para estudar</span>
        <a href="#inicio" class="btn-top">↑ </a>
      </div>
    </div>
  </footer>

  <div class="toast-custom" id="toast" hidden></div>

  <script>
    function getData(key, fallback) {
      const saved = localStorage.getItem('5m_' + key);
      return saved ? JSON.parse(saved) : fallback;
    }

    function saveData(key, value) {
      localStorage.setItem('5m_' + key, JSON.stringify(value));
    }

    function escapeHTML(str) {
      const div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    }

    function showToast(msg) {
      const toast = document.getElementById('toast');
      toast.textContent = msg;
      toast.hidden = false;
      setTimeout(() => { toast.hidden = true; }, 2500);
    }

    function getTodayKey() {
      const d = new Date();
      return d.toISOString().split('T')[0];
    }

    // Calendario
    const monthNames = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    const today = new Date();
    let currentView = new Date(today.getFullYear(), today.getMonth(), 1);

    document.getElementById('calToday').textContent = 'Hoje: ' + today.toLocaleDateString('pt-BR');

    function renderCalendar() {
      const year = currentView.getFullYear();
      const month = currentView.getMonth();
      document.getElementById('calMonth').textContent = `${monthNames[month]} ${year}`;

      const firstDay = (new Date(year, month, 1).getDay() + 6) % 7;
      const daysInMonth = new Date(year, month + 1, 0).getDate();

      let html = ['S', 'T', 'Q', 'Q', 'S', 'S', 'D'].map(d => `<div class="cal-dow">${d}</div>`).join('');

      for (let i = 0; i < firstDay; i++) {
        html += '<div class="cal-blank"></div>';
      }

      for (let day = 1; day <= daysInMonth; day++) {
        const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
        html += `<div class="${isToday ? 'cal-today' : ''}">${day}</div>`;
      }

      document.getElementById('calGrid').innerHTML = html;
    }

    document.getElementById('calPrev').addEventListener('click', () => {
      currentView.setMonth(currentView.getMonth() - 1);
      renderCalendar();
    });

    document.getElementById('calNext').addEventListener('click', () => {
      currentView.setMonth(currentView.getMonth() + 1);
      renderCalendar();
    });

    renderCalendar();

    // Tempo
    let studyLog = getData('log', {});
    let selectedMinutes = 5;
    let totalSeconds = 300;
    let remainingSeconds = 300;
    let timerInterval = null;

    function updateTimerUI() {
      const mins = String(Math.floor(remainingSeconds / 60)).padStart(2, '0');
      const secs = String(remainingSeconds % 60).padStart(2, '0');
      document.getElementById('timerClock').textContent = `${mins}:${secs}`;

      const percent = ((totalSeconds - remainingSeconds) / totalSeconds) * 100;
      document.getElementById('timerProgress').style.width = `${percent}%`;

      const btnToggle = document.getElementById('btnTimerToggle');
      if (timerInterval) {
        btnToggle.textContent = 'Pausar';
      } else if (remainingSeconds < totalSeconds && remainingSeconds > 0) {
        btnToggle.textContent = 'Continuar';
      } else {
        btnToggle.textContent = 'Começar';
      }

      document.getElementById('todayMinutes').textContent = studyLog[getTodayKey()] || 0;
    }

    document.getElementById('btnTimerToggle').addEventListener('click', () => {
      if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
        updateTimerUI();
        return;
      }

      timerInterval = setInterval(() => {
        remainingSeconds--;
        if (remainingSeconds <= 0) {
          clearInterval(timerInterval);
          timerInterval = null;
          const key = getTodayKey();
          studyLog[key] = (studyLog[key] || 0) + selectedMinutes;
          saveData('log', studyLog);
          remainingSeconds = totalSeconds;
          showToast(`Sessão de ${selectedMinutes} min concluída!`);
        }
        updateTimerUI();
      }, 1000);

      updateTimerUI();
    });

    document.getElementById('btnTimerReset').addEventListener('click', () => {
      clearInterval(timerInterval);
      timerInterval = null;
      remainingSeconds = totalSeconds;
      updateTimerUI();
    });

    document.getElementById('timerPresets').addEventListener('click', (e) => {
      if (!e.target.dataset.min) return;
      clearInterval(timerInterval);
      timerInterval = null;

      document.querySelectorAll('#timerPresets button').forEach(b => b.classList.remove('active'));
      e.target.classList.add('active');

      selectedMinutes = Number(e.target.dataset.min);
      totalSeconds = selectedMinutes * 60;
      remainingSeconds = totalSeconds;
      updateTimerUI();
    });

    updateTimerUI();

    // Anotações
    let notes = getData('notes', []);

    function renderNotes() {
      const list = document.getElementById('notesList');
      document.getElementById('notesCount').textContent = notes.length || '';

      if (notes.length == 0) {
        list.innerHTML = '<div class="empty-msg">Nenhuma anotação ainda.</div>';
        return;
      }

      list.innerHTML = notes.map((note, index) => `
        <article class="item">
          <h3>${escapeHTML(note.title || 'Sem título')}</h3>
          <p>${escapeHTML(note.body)}</p>
          <div class="item-meta">
            <span>${note.date}</span>
            <button type="button" class="btn-ghost btn-small" onclick="deleteNote(${index})">Apagar</button>
          </div>
        </article>
      `).join('');
    }

    document.getElementById('noteForm').addEventListener('submit', (e) => {
      e.preventDefault();
      const title = document.getElementById('noteTitle').value.trim();
      const body = document.getElementById('noteBody').value.trim();
      const date = new Date().toLocaleDateString('pt-BR');

      notes.unshift({title, body, date});
      saveData('notes', notes);
      e.target.reset();
      renderNotes();
      showToast('Anotação salva');
    });

    window.deleteNote = function (index) {
      notes.splice(index, 1);
      saveData('notes', notes);
      renderNotes();
    };

    renderNotes();

    // Músicas
    const playlists = [
      {title: "Sons da chuva", desc: "Musiquinha de chuva para estudar", query: "som%20de%20chuva%20para%20estudar"}
    ];

    document.getElementById('musicList').innerHTML = playlists.map(p => `
      <article class="item">
        <h3>${p.title}</h3>
        <p class="muted">${p.desc}</p>
        <div class="item-meta">
          <a class="btn-spotify" href="https://open.spotify.com/search/${p.query}" target="_blank" rel="noopener noreferrer">Ouvir no Spotify</a>
        </div>
      </article>
    `).join('');

    // Curiosidades
    let facts = getData('facts', [
      {author: "Equipe 5minutes", text: "O primeiro “bug” registrado foi uma mariposa presa num relé do computador Harvard Mark II, em 1947.", likes: 0, liked: false, example: true},
      {author: "Equipe 5minutes", text: "O Tetris foi criado em 1984 por Alexey Pajitnov, um engenheiro de computação soviético.", likes: 0, liked: false, example: true},
      {author: "Equipe 5minutes", text: "A palavra “bit” vem de “binary digit” (dígito binário) e foi popularizada por John Tukey nos anos 1940.", likes: 0, liked: false, example: true}
    ]);

    function renderSidebarFavorites() {
      const favContainer = document.getElementById('sidebarFavList');
      const favorites = facts
        .map((f, idx) => ({...f, originalIndex: idx}))
        .filter(f => f.liked);

      if (favorites.length === 0) {
        favContainer.innerHTML = '';
        return;
      }

      favContainer.innerHTML = favorites.map(f => `
        <button type="button" class="sidebar-fav-item" title="${escapeHTML(f.text)}" onclick="focusFact(${f.originalIndex})">
          ★ ${escapeHTML(f.text)}
        </button>
      `).join('');
    }

    window.focusFact = function (index) {
      const section = document.getElementById('curiosidades');
      section.scrollIntoView({behavior: 'smooth'});
      const cards = document.querySelectorAll('#factList .item');
      if (cards[index]) {
        cards[index].style.outline = '2px solid var(--gold-ink)';
        setTimeout(() => {
          cards[index].style.outline = 'none';
        }, 1600);
      }
    };

    function renderFacts() {
      const list = document.getElementById('factList');
      list.innerHTML = facts.map((f, index) => `
        <article class="item">
          <p>${escapeHTML(f.text)}</p>
          <div class="item-meta">
            <span>${escapeHTML(f.author)} ${f.example ? '<span class="badge-custom">exemplo</span>' : ''}</span>
            <div>
              <button type="button" class="btn-like ${f.liked ? 'liked' : ''}" onclick="toggleLike(${index})">★ ${f.likes}</button>
              ${!f.example ? `<button type="button" class="btn-ghost btn-small" onclick="deleteFact(${index})">Apagar</button>` : ''}
            </div>
          </div>
        </article>
      `).join('');

      renderSidebarFavorites();
    }

    document.getElementById('factForm').addEventListener('submit', (e) => {
      e.preventDefault();
      const text = document.getElementById('factBody').value.trim();

      facts.unshift({author: "Você", text, likes: 0, liked: false});
      saveData('facts', facts);
      e.target.reset();
      renderFacts();
      showToast('Curiosidade publicada');
    });

    window.toggleLike = function (index) {
      facts[index].liked = !facts[index].liked;
      facts[index].likes += facts[index].liked ? 1 : -1;
      saveData('facts', facts);
      renderFacts();
      showToast(facts[index].liked ? '★ Curiosidade salva na barra esquerda' : 'Removida das favoritas');
    };

    window.deleteFact = function (index) {
      facts.splice(index, 1);
      saveData('facts', facts);
      renderFacts();
    };

    renderFacts();
  </script>
</body>

</html>