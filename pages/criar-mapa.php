<?php
/**
 *  Após a página de login definir a sessão com os dados do usuario a página index lê a sessão e inicia a mesma
 *  Na navbar temos um if e else para cado o usuario esteja conectado ou não, mudando sendo que: 
 *  SE o usuário estiver logado irá mostrar a foto e o nome do usuário
 *  SE NÃO irá mostrar os botões para navegar até a página de login ou cadastro
 */
session_start();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Criar Mapa</title>
    <link rel="shortcut icon" href="../img/logo_icone.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/nav-footer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Montserrat:ital,wght@0,300;0,400;0,600;0,700;0,800;0,900;1,400&display=swap"
        rel="stylesheet">
    <style>
        /* ==========================================================
   VARIÁVEIS E RESET GLOBAL (CABEÇALHO, RODAPÉ E BOTÕES)
========================================================== */


        :root {
            --fundo-pagina-inicio: #492499;
            --fundo-pagina-fim: #190e35;
            --fundo-cartao: #ffffff;
            --fundo-cartao-escuro: #1F1A2C;
            --borda-escura: #4a4063;
            --cor-primaria: #5b2be0;
            --cor-primaria-hover: #7b4ff7;
            --cor-texto-suave: #ccc;
            --cor-destaque-claro: #9d7aff;
        }

        html {
            scroll-behavior: smooth;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Montserrat", sans-serif !important;
        }

        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #1f1a2c;
        }

        ::-webkit-scrollbar-thumb {
            background-color: #4a4063;
            border-radius: 10px;
            border: 2px solid #1f1a2c;
        }

        ::-webkit-scrollbar-thumb:hover {
            background-color: #5b507a;
        }


        /* Estilos de Header/Footer movidos para nav-footer.css */


        /* ==========================================================
    BOTÕES GLOBAIS
========================================================== */

        .botao {
            border: none;
            padding: 12px 28px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .botao-primario {
            background: linear-gradient(135deg, var(--cor-primaria), var(--cor-primaria-hover));
            color: #fff;
            box-shadow: 0 4px 15px rgba(91, 43, 224, 0.4);
        }

        .botao-primario:hover {
            background: linear-gradient(135deg, var(--cor-primaria-hover), var(--cor-primaria));
            box-shadow: 0 6px 20px rgba(123, 79, 247, 0.6);
            transform: translateY(-2px);
        }

        .botao-contorno {
            border: 2px solid #fff;
            background: transparent;
            color: #fff;
        }

        .botao-contorno:hover {
            background-color: #fff;
            color: #000;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.6);
        }


        /* Estilos de Perfil/Footer movidos para nav-footer.css */
        /* ================================================================
   TABLE RPG Map Editor — style.css (v3.1 — Professional)
   Alinhado com nav-footer.css do projeto TABLE
================================================================ */

        /* ── VARIÁVEIS ─────────────────────────────────────────────────── */
        :root {
            /* Cores do editor — harmonizadas com o tema TABLE */
            --bg-color: #0d091a;
            --panel-bg: rgba(30, 26, 50, 0.9);
            --panel-bg-light: rgba(44, 37, 61, 0.95);
            --border-color: rgba(139, 92, 246, 0.15);
            --border-glow: rgba(139, 92, 246, 0.35);
            --text-bright: #ffffff;
            --text-muted: #b4accb;
            --text-dim: #7a7096;
            --accent: #8b5cf6;
            --accent-hover: #a78bfa;
            --accent-glow: rgba(139, 92, 246, 0.25);
            --accent-light: #c4b5fd;
            --canvas-bg: #05030a;
            --danger: #ef4444;
            --success: #10b981;
            --glass-bg: rgba(25, 20, 45, 0.65);
            --glass-border: rgba(255, 255, 255, 0.06);

            /* Cores de Terreno RPG — Tons mais refinados e menos "neon" */
            --t-water: #24a0ed;
            --t-forest: #2d7a4d;
            --t-lava: #c53030;
            --t-road: #594a3a;
            --t-sand: #d4a24c;
            --t-snow: #e2e8f0;
            --t-swamp: #315a39;
            --t-darkforest: #0f2e1b;
            --t-mud: #402a1d;
            --t-blood: #821c1c;
            --t-ice: #8ecae6;
            --t-mushroom: #8a5cf6;
            --t-void: #1a103c;

            /* Profundidade */
            --shadow-premium: 0 12px 36px rgba(0, 0, 0, 0.6);
            --bevel-top: inset 0 1px 0 rgba(255, 255, 255, 0.12);
            --bevel-bottom: inset 0 -1px 0 rgba(0, 0, 0, 0.25);

            /* Layout */
            --header-h: 80px;
            --toolbar-h: 56px;
            --sidebar-w: 280px;
            --sidebar-pad: 12px;
            --transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 18px;
        }

        /* ── RESET EDITOR ──────────────────────────────────────────────── */
        /* Só dentro do escopo do editor para não afetar nav/footer */
        .editor-wrapper *,
        .action-toolbar *,
        .sidebar * {
            box-sizing: border-box;
        }

        /* ── WRAPPER PRINCIPAL ─────────────────────────────────────────── */
        .editor-wrapper {
            width: 100%;
            /* Exatamente abaixo do header fixo */
            height: calc(100vh - var(--header-h));
            margin-top: var(--header-h);
            display: flex;
            flex-direction: column;
            background: var(--bg-color);
            overflow: hidden;
            /* Linha decorativa que conecta com a borda inferior do header */
            border-top: 2px solid var(--border-color);
        }

        /* ── BARRA SUPERIOR ────────────────────────────────────────────── */
        .action-toolbar {
            height: var(--toolbar-h);
            background: var(--panel-bg);
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            padding: 0 16px;
            gap: 8px;
            flex-shrink: 0;
            z-index: 100;
        }

        /* Grupos de botões na toolbar */
        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 4px;
            background: rgba(255, 255, 255, 0.03);
            padding: 4px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Botões da toolbar */
        .action-btn {
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-muted);
            border: 1px solid var(--border-color);
            padding: 8px 14px;
            border-radius: var(--radius-sm);
            font-size: 0.78rem;
            font-weight: 700;
            font-family: "Montserrat", sans-serif;
            cursor: pointer;
            transition: all var(--transition);
            display: flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
            box-shadow: var(--bevel-top);
        }

        .action-btn i {
            font-size: 0.82rem;
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .action-btn.active {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent-light);
            box-shadow: 0 4px 15px var(--accent-glow), var(--bevel-top);
        }

        .action-btn.highlight {
            background: rgba(139, 92, 246, 0.15);
            color: var(--accent-light);
            border-color: rgba(139, 92, 246, 0.4);
        }

        .action-btn.highlight:hover {
            background: var(--accent);
            color: #fff;
        }

        .action-btn.text-red {
            color: #fca5a5;
            border-color: rgba(239, 68, 68, 0.4);
        }

        .action-btn.text-red:hover {
            background: var(--danger);
            color: #fff;
            border-color: var(--danger);
            box-shadow: 0 0 12px rgba(239, 68, 68, 0.3);
        }

        .action-btn.highlight {
            background: rgba(139, 92, 246, 0.12);
            color: var(--accent-light);
            border-color: rgba(139, 92, 246, 0.5);
        }

        .action-btn.highlight:hover {
            background: var(--accent);
            color: #fff;
        }

        /* Separador vertical */
        .sep {
            width: 1px;
            height: 22px;
            background: var(--border-color);
            margin: 0 3px;
            flex-shrink: 0;
            opacity: 0.7;
        }

        /* Slider de grade */
        .slider-group {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-muted);
            white-space: nowrap;
            flex-shrink: 0;
        }

        input[type="range"] {
            width: 90px;
            accent-color: var(--accent);
            cursor: pointer;
        }

        .undo-count {
            font-size: 0.72rem;
            color: var(--accent-light);
            font-weight: 400;
            min-width: 18px;
            opacity: 0.8;
        }

        /* AutoSave badge */
        .autosave-badge {
            display: flex;
            align-items: center;
            gap: 5px;
            background: rgba(34, 197, 94, 0.1);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.25);
            border-radius: var(--radius-sm);
            padding: 4px 9px;
            font-size: 0.73rem;
            font-weight: 700;
            opacity: 0;
            transition: opacity 0.4s ease;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .autosave-badge.visible {
            opacity: 1;
        }

        /* ── LAYOUT PRINCIPAL ──────────────────────────────────────────── */
        .main-area {
            display: flex;
            flex: 1;
            overflow: hidden;
            position: relative;
        }

        /* ── SIDEBARS ──────────────────────────────────────────────────── */
        .sidebar {
            position: absolute;
            top: var(--sidebar-pad);
            bottom: var(--sidebar-pad);
            width: var(--sidebar-w);
            background: var(--panel-bg);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            display: flex;
            flex-direction: column;
            overflow: visible;
            z-index: 50;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-premium), 0 0 0 1px var(--border-glow);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            user-select: none;
        }

        .sidebar.left {
            left: var(--sidebar-pad);
        }

        .sidebar.right {
            right: var(--sidebar-pad);
        }

        .sidebar.collapsed {
            /* Deixa exatamente 30px (largura da aba) visível dentro do container */
            transform: translateX(calc((var(--sidebar-w) - 30px) * var(--side-dir)));
            background: rgba(30, 26, 50, 0.6);
            border-color: rgba(139, 92, 246, 0.2);
            box-shadow: 10px 0 30px rgba(0, 0, 0, 0.5);
        }

        .sidebar.left.collapsed {
            --side-dir: -1;
        }

        .sidebar.right.collapsed {
            --side-dir: 1;
        }


        .sidebar.collapsed .sidebar-inner {
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
        }

        .sidebar-inner {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: var(--sidebar-pad);
            scrollbar-width: none;
            transition: opacity 0.3s;
        }

        /* Botão de recolher — Agora vira uma aba persistente */
        .sidebar-collapse-btn {
            position: absolute;
            top: 50%;
            width: 30px;
            height: 60px;
            background: var(--accent);
            color: #fff;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 100;
            transform: translateY(-50%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            transition: all var(--transition);
        }

        .sidebar.left .sidebar-collapse-btn {
            right: -15px;
            border-radius: 0 12px 12px 0;
        }

        .sidebar.right .sidebar-collapse-btn {
            left: -15px;
            border-radius: 12px 0 0 12px;
        }

        .sidebar.collapsed .sidebar-collapse-btn {
            background: var(--panel-bg-light);
            color: var(--accent-light);
            right: 0px;
            /* Alinha na borda visível */
        }

        .sidebar.right.collapsed .sidebar-collapse-btn {
            left: 0px;
        }

        .sidebar-collapse-btn:hover {
            background: var(--accent-hover);
            width: 35px;
        }


        .sidebar-inner {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            opacity: 1;
            transition: opacity var(--transition);
            scrollbar-width: thin;
            scrollbar-color: var(--border-color) transparent;
            min-width: var(--sidebar-w);
        }

        .sidebar-inner::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-inner::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-inner::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        /* ── BOTÃO COLAPSO — ABA VERTICAL ─────────────────────────────── */
        .sidebar-collapse-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 20;
            background: var(--accent);
            color: #fff;
            border: none;
            width: 20px;
            height: 68px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            transition: all var(--transition);
        }

        .sidebar-collapse-btn.left {
            right: 0;
            border-radius: 0 8px 8px 0;
            box-shadow: 4px 0 14px rgba(139, 92, 246, 0.35);
        }

        .sidebar-collapse-btn.right {
            left: 0;
            border-radius: 8px 0 0 8px;
            box-shadow: -4px 0 14px rgba(139, 92, 246, 0.35);
        }

        .sidebar-collapse-btn:hover {
            background: var(--accent-hover);
            width: 26px;
        }

        .sidebar-collapse-btn i {
            font-size: 0.6rem;
        }

        .collapse-label {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 0.48rem;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
            opacity: 0.85;
            transition: opacity var(--transition);
            max-height: 46px;
            overflow: hidden;
            font-family: "Montserrat", sans-serif;
        }

        /* ── SEÇÕES DO PAINEL ──────────────────────────────────────────── */
        .panel-section {
            padding: 16px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        .panel-section:last-child {
            border-bottom: none;
        }

        .panel-title {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--accent-light);
            margin-bottom: 14px;
            letter-spacing: 1.5px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: "Montserrat", sans-serif;
        }

        .panel-title i {
            font-size: 0.7rem;
        }

        .subtitle {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-dim);
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-family: "Montserrat", sans-serif;
        }

        .setting-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 9px;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-muted);
            font-family: "Montserrat", sans-serif;
        }

        /* ── FERRAMENTAS (TOOL CARDS) ──────────────────────────────────── */
        .tool-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }

        .tool-card {
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 14px 8px;
            cursor: pointer;
            text-align: center;
            transition: all var(--transition);
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            user-select: none;
            box-shadow: var(--bevel-top);
        }

        .tool-card:hover:not(.active) {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.15);
            color: #fff;
            transform: translateY(-2px);
        }

        .tool-icon {
            font-size: 19px;
            color: inherit;
        }

        .tool-name {
            font-size: 0.72rem;
            font-weight: 800;
            font-family: "Montserrat", sans-serif;
            letter-spacing: 0.4px;
        }

        /* ── ESTILOS (STYLE GRID) ──────────────────────────────────────── */
        .style-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
            margin-bottom: 4px;
        }

        .style-btn {
            border: 2px solid transparent;
            border-radius: var(--radius-sm);
            padding: 7px 4px;
            text-align: center;
            cursor: pointer;
            font-size: 0.66rem;
            font-weight: 700;
            transition: all 0.18s;
            color: #fff;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.95);
            min-height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "Montserrat", sans-serif;
            letter-spacing: 0.2px;
        }

        .style-btn:hover {
            transform: scale(1.04);
            filter: brightness(1.18);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
        }

        .style-btn.active {
            border-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 12px rgba(255, 255, 255, 0.35), 0 2px 10px rgba(0, 0, 0, 0.3);
            transform: scale(1.04);
        }

        /* ── GRUPO DE BOTÕES (SNAP) ────────────────────────────────────── */
        .btn-group {
            display: flex;
            background: rgba(0, 0, 0, 0.2);
            border-radius: var(--radius-sm);
            padding: 3px;
            width: 100%;
            gap: 3px;
            border: 1px solid var(--border-color);
        }

        .btn-toggle {
            flex: 1;
            border: none;
            background: transparent;
            padding: 8px;
            font-size: 0.76rem;
            font-weight: 700;
            border-radius: 4px;
            cursor: pointer;
            color: var(--text-muted);
            transition: all var(--transition);
            font-family: "Montserrat", sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .btn-toggle.active {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 2px 8px var(--accent-glow);
        }

        .btn-toggle:hover:not(.active) {
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
        }

        /* ── PREVIEW DE ESTILO ─────────────────────────────────────────── */
        .style-preview-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .style-preview-swatch {
            width: 38px;
            height: 26px;
            border-radius: 5px;
            border: 2px solid var(--border-color);
            transition: background 0.3s, transform 0.18s;
            cursor: pointer;
        }

        .style-preview-swatch.flash {
            transform: scale(1.18);
            border-color: var(--accent);
        }

        /* ── PROPS & UPLOAD ────────────────────────────────────────────── */
        .upload-area {
            border: 1.5px dashed var(--border-color);
            border-radius: var(--radius-md);
            padding: 11px 10px;
            text-align: center;
            cursor: pointer;
            background: rgba(0, 0, 0, 0.1);
            font-size: 0.77rem;
            color: var(--text-muted);
            font-weight: 600;
            display: block;
            margin-bottom: 11px;
            transition: all var(--transition);
            font-family: "Montserrat", sans-serif;
        }

        .upload-area:hover {
            border-color: var(--accent);
            background: var(--accent-glow);
            color: var(--accent-light);
        }

        .prop-list {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 5px;
        }

        .prop-item {
            font-size: 19px;
            text-align: center;
            padding: 6px 3px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: var(--radius-sm);
            cursor: pointer;
            border: 1px solid transparent;
            transition: all var(--transition);
            user-select: none;
        }

        .prop-item:hover {
            transform: translateY(-2px) scale(1.12);
            background: var(--accent-glow);
            border-color: var(--accent);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
        }

        .btn-magic {
            width: 100%;
            background: linear-gradient(135deg, #ec4899, #8b5cf6);
            color: #fff;
            border: none;
            padding: 9px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 700;
            font-size: 0.77rem;
            transition: all var(--transition);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.28);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-family: "Montserrat", sans-serif;
        }

        .btn-magic:hover {
            transform: translateY(-1px);
            filter: brightness(1.1);
            box-shadow: 0 6px 18px rgba(139, 92, 246, 0.4);
        }

        /* ── CANVAS ────────────────────────────────────────────────────── */
        .canvas-container {
            flex: 1;
            position: relative;
            overflow: hidden;
            background: var(--canvas-bg);
            min-width: 0;
        }

        canvas#mainCanvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        /* ── MINIMAP ───────────────────────────────────────────────────── */
        /* Minimapa — visão geral do mapa (posição fixa canto inferior direito) */
        canvas#minimapCanvas {
            position: absolute;
            bottom: 16px;
            right: 16px;
            top: auto;
            left: auto;
            transform: none;
            width: 200px;
            height: 134px;
            border: 2px solid rgba(139, 92, 246, 0.6);
            border-radius: 10px;
            background: #07040f;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(139, 92, 246, 0.2);
            display: none;
            pointer-events: auto;
            z-index: 200;
            cursor: crosshair;
            transition: opacity 0.2s;
            opacity: 0;
        }

        canvas#minimapCanvas.visible {
            display: block;
            opacity: 1;
        }

        /* Lupa — amplia área ao redor do cursor */
        #lupCanvas {
            position: absolute;
            pointer-events: none;
            z-index: 210;
            border-radius: 50%;
            border: 3px solid rgba(139, 92, 246, 0.8);
            box-shadow: 0 0 0 1px rgba(139, 92, 246, 0.3), 0 8px 32px rgba(0, 0, 0, 0.7);
            display: none;
        }

        #lupCanvas.visible {
            display: block;
        }

        #lupLabel {
            position: absolute;
            pointer-events: none;
            z-index: 211;
            background: rgba(13, 9, 26, 0.92);
            color: #c4b5fd;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 8px;
            border: 1px solid rgba(139, 92, 246, 0.4);
            display: none;
            font-family: Montserrat, sans-serif;
            white-space: nowrap;
        }


        /* ── INPUTS ────────────────────────────────────────────────────── */
        input[type="number"],
        .text-tool-input {
            background: rgba(0, 0, 0, 0.3);
            color: #fff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 8px 12px;
            font-family: inherit;
            font-size: 0.85rem;
            outline: none;
            transition: all 0.2s;
        }

        input[type="number"]:focus,
        .text-tool-input:focus {
            border-color: var(--accent);
            background: rgba(0, 0, 0, 0.4);
            box-shadow: 0 0 0 2px var(--accent-glow);
        }

        input[type="color"] {
            background: transparent;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            width: 44px;
            height: 32px;
            cursor: pointer;
            padding: 3px;
        }

        input[type="color"]:hover {
            border-color: var(--accent);
        }

        /* ── RENAME INPUT ────────────────────────────────────────────── */
        #renameInput {
            position: fixed;
            z-index: 10000;
            background: var(--panel-bg-light);
            color: var(--text-bright);
            border: 2px solid var(--accent);
            border-radius: var(--radius-md);
            padding: 8px 14px;
            font-size: 14px;
            font-family: 'Cinzel', serif;
            min-width: 180px;
            outline: none;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5), 0 0 0 1px var(--border-glow);
            backdrop-filter: blur(10px);
            animation: ctxFadeIn 0.15s ease;
        }

        /* ── STATUS DE EDIÇÃO ──────────────────────────────────────────── */
        .edit-status {
            padding: 8px;
            border-radius: var(--radius-sm);
            font-size: 0.77rem;
            font-weight: 700;
            margin-bottom: 4px;
            text-align: center;
            border: 1px solid transparent;
            transition: all 0.3s;
            font-family: "Montserrat", sans-serif;
            letter-spacing: 0.2px;
        }

        .edit-status.room {
            background: rgba(16, 185, 129, 0.15);
            color: #6ee7b7;
            border-color: rgba(16, 185, 129, 0.4);
        }

        .edit-status.prop {
            background: rgba(245, 158, 11, 0.15);
            color: #fcd34d;
            border-color: rgba(245, 158, 11, 0.4);
        }

        .edit-status.wall {
            background: rgba(236, 72, 153, 0.15);
            color: #f472b6;
            border-color: rgba(236, 72, 153, 0.4);
        }

        .edit-status.free {
            background: rgba(139, 92, 246, 0.1);
            color: #c4b5fd;
            border-color: rgba(139, 92, 246, 0.3);
        }

        /* ── DICAS / HINT ──────────────────────────────────────────────── */
        .hint {
            font-size: 0.71rem;
            color: var(--text-dim);
            line-height: 1.75;
            background: rgba(0, 0, 0, 0.18);
            padding: 11px;
            border-radius: var(--radius-sm);
            margin: 0;
            border: 1px solid rgba(255, 255, 255, 0.04);
            font-family: "Montserrat", sans-serif;
        }

        .hint b {
            color: var(--accent-light);
            font-weight: 700;
        }

        /* ── CAMADAS ───────────────────────────────────────────────────── */
        .layers-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .layer-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 7px 9px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
            background: rgba(0, 0, 0, 0.15);
            transition: all var(--transition);
            cursor: pointer;
        }

        .layer-item:hover {
            background: var(--accent-glow);
            border-color: var(--accent);
        }

        .layer-item.active {
            background: rgba(139, 92, 246, 0.18);
            border-color: var(--accent);
        }

        .layer-name {
            font-size: 0.79rem;
            font-weight: 600;
            color: var(--text-muted);
            font-family: "Montserrat", sans-serif;
        }

        .layer-item.active .layer-name {
            color: var(--accent-light);
        }

        .layer-controls {
            display: flex;
            gap: 4px;
            align-items: center;
        }

        .order-btns {
            display: flex;
            flex-direction: column;
            gap: 1px;
            margin-right: 2px;
        }

        .layer-btn-sm {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-dim);
            width: 18px;
            height: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 2px;
            font-size: 7px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .layer-btn-sm:hover {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent-light);
        }

        .layer-btn {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-dim);
            border-radius: 4px;
            width: 24px;
            height: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.62rem;
            transition: all var(--transition);
        }

        .layer-btn:hover {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }

        .layer-btn.off {
            opacity: 0.35;
        }

        .layer-btn.locked {
            color: #fbbf24;
            border-color: rgba(251, 191, 36, 0.5);
        }

        /* ── MENU DE CONTEXTO ──────────────────────────────────────────── */
        .context-menu {
            position: fixed;
            z-index: 99999;
            background: var(--panel-bg-light);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 5px;
            min-width: 185px;
            box-shadow:
                0 12px 40px rgba(0, 0, 0, 0.65),
                0 0 0 1px var(--border-glow),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
            animation: ctxFadeIn 0.11s ease;
        }

        @keyframes ctxFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-5px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .ctx-item {
            display: flex;
            align-items: center;
            gap: 9px;
            width: 100%;
            padding: 9px 11px;
            background: transparent;
            border: none;
            border-radius: var(--radius-sm);
            color: var(--text-muted);
            font-size: 0.81rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.13s;
            text-align: left;
            font-family: "Montserrat", sans-serif;
        }

        .ctx-item:hover {
            background: var(--accent-glow);
            color: #fff;
        }

        .ctx-item i {
            width: 15px;
            text-align: center;
            color: var(--accent);
            flex-shrink: 0;
            font-size: 0.75rem;
        }

        .ctx-item.danger {
            color: #fca5a5;
        }

        .ctx-item.danger i {
            color: var(--danger);
        }

        .ctx-item.danger:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #fff;
        }

        /* Divisor dentro do context menu */
        .ctx-divider {
            height: 1px;
            background: var(--border-color);
            margin: 4px 8px;
            opacity: 0.6;
        }

        /* ── MODAIS ────────────────────────────────────────────────────── */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.75);
            z-index: 99990;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(6px);
        }

        .modal-box {
            background: var(--panel-bg-light);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 32px;
            width: 92%;
            max-width: 440px;
            box-shadow: 0 32px 80px rgba(0, 0, 0, 0.65), 0 0 0 1px var(--border-glow);
            text-align: center;
            color: var(--text-muted);
            display: none;
            flex-direction: column;
            gap: 18px;
            backdrop-filter: blur(25px);
            animation: modalSlideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes modalSlideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-box h3 {
            font-size: 1.2rem;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: var(--text-bright);
            font-family: "Montserrat", sans-serif;
        }

        .modal-box p {
            font-size: 0.86rem;
            line-height: 1.55;
            font-family: "Montserrat", sans-serif;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 6px;
        }

        .modal-actions-col {
            display: flex;
            flex-direction: column;
            gap: 9px;
            margin-top: 6px;
        }

        .btn-cancel {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            transition: all var(--transition);
            font-family: "Montserrat", sans-serif;
            font-size: 0.85rem;
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.07);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-confirm {
            background: rgba(139, 92, 246, 0.1);
            border: 1.5px solid var(--accent);
            color: #fff;
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 700;
            transition: all var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            font-family: "Montserrat", sans-serif;
            font-size: 0.85rem;
        }

        .btn-confirm:hover {
            background: var(--accent);
            box-shadow: 0 0 16px var(--accent-glow);
        }

        .btn-confirm.light {
            border-color: rgba(255, 255, 255, 0.4);
            color: #e5e7eb;
        }

        .btn-confirm.light:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        .btn-danger-modal {
            border-color: var(--danger);
            color: var(--danger);
            background: rgba(239, 68, 68, 0.06);
        }

        .btn-danger-modal:hover {
            background: var(--danger);
            color: #fff;
            box-shadow: 0 0 16px rgba(239, 68, 68, 0.35);
        }

        /* ── TOAST ─────────────────────────────────────────────────────── */
        #toastContainer {
            position: fixed;
            bottom: 22px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 7px;
            align-items: center;
            pointer-events: none;
        }

        .toast {
            background: rgba(15, 10, 30, 0.95);
            color: var(--text-bright);
            border: 1px solid var(--border-color);
            padding: 14px 28px;
            border-radius: 16px;
            font-size: 0.88rem;
            font-weight: 700;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7), 0 0 0 1px var(--border-glow);
            opacity: 0;
            transform: translateY(20px) scale(0.9);
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            pointer-events: none;
            white-space: nowrap;
            font-family: "Montserrat", sans-serif;
            backdrop-filter: blur(20px);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        /* Variações de Toast */
        .toast-success {
            color: #4ade80;
            border-color: rgba(74, 222, 128, 0.4);
        }

        .toast-success i {
            color: #4ade80;
        }

        .toast-error {
            color: #fca5a5;
            border-color: rgba(239, 68, 68, 0.4);
        }

        .toast-error i {
            color: #ef4444;
        }

        .toast-info {
            color: #93c5fd;
            border-color: rgba(59, 130, 246, 0.4);
        }

        .toast-info i {
            color: #3b82f6;
        }

        .toast i {
            font-size: 1.1rem;
        }

        .toast.error {
            border-color: var(--danger);
            color: #fca5a5;
        }

        /* ── RESPONSIVIDADE ────────────────────────────────────────────── */
        @media (max-width: 1100px) {
            :root {
                --sidebar-w: 240px;
            }
        }

        @media (max-width: 900px) {
            :root {
                --sidebar-w: 210px;
            }
        }

        @media (max-width: 768px) {
            :root {
                --sidebar-w: 260px !important;
                --sidebar-pad: 0px !important;
            }

            .sidebar {
                display: flex !important;
                position: fixed !important;
                top: var(--header-h) !important;
                bottom: 0 !important;
                height: calc(100vh - var(--header-h)) !important;
                border-radius: 0 !important;
                z-index: 10000 !important;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8) !important;
                transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
            }

            .sidebar.left {
                left: 0 !important;
            }

            .sidebar.right {
                right: 0 !important;
            }

            /* No celular, sidebars recolhidas somem da tela para dar espaço total ao desenho do mapa */
            .sidebar.left.collapsed {
                transform: translateX(-100%) !important;
            }

            .sidebar.right.collapsed {
                transform: translateX(100%) !important;
            }

            /* Estilização das abas de clique no celular para ficarem fáceis de tocar */
            .sidebar.left .sidebar-collapse-btn {
                right: -30px !important;
                border-radius: 0 8px 8px 0 !important;
                background: var(--accent) !important;
                width: 30px !important;
                height: 60px !important;
            }

            .sidebar.right .sidebar-collapse-btn {
                left: -30px !important;
                border-radius: 8px 0 0 8px !important;
                background: var(--accent) !important;
                width: 30px !important;
                height: 60px !important;
            }

            .sidebar.left.collapsed .sidebar-collapse-btn {
                right: -30px !important;
            }

            .sidebar.right.collapsed .sidebar-collapse-btn {
                left: -30px !important;
            }

            /* Barra de ferramentas superior com scroll horizontal no celular */
            .action-toolbar {
                gap: 6px !important;
                padding: 0 10px !important;
                overflow-x: auto !important;
                flex-wrap: nowrap !important;
                display: flex !important;
                -webkit-overflow-scrolling: touch;
            }

            .toolbar-section {
                flex-shrink: 0 !important;
            }

            .action-btn {
                padding: 6px 12px !important;
                font-size: 0.76rem !important;
                flex-shrink: 0 !important;
            }
        }

        /* ── NÉVOA DE GUERRA ───────────────────────────────────────────── */
        .fog-btn.active {
            background: rgba(99, 102, 241, 0.25) !important;
            color: #a5b4fc !important;
            border-color: #6366f1 !important;
            box-shadow: 0 0 12px rgba(99, 102, 241, 0.35) !important;
        }

        .fog-btn:hover {
            background: #6366f1 !important;
            border-color: #6366f1 !important;
        }

        /* ── MODO APRESENTAÇÃO ─────────────────────────────────────────── */
        .presentation-btn {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(236, 72, 153, 0.15)) !important;
            color: #e879f9 !important;
            border-color: rgba(232, 121, 249, 0.5) !important;
        }

        .presentation-btn:hover {
            background: linear-gradient(135deg, #8b5cf6, #ec4899) !important;
            color: #fff !important;
            border-color: transparent !important;
            box-shadow: 0 0 18px rgba(236, 72, 153, 0.4) !important;
        }

        #presentationOverlay {
            position: fixed;
            inset: 0;
            z-index: 99998;
            pointer-events: none;
        }

        .pres-hud {
            position: absolute;
            top: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 16px;
            background: rgba(15, 10, 30, 0.88);
            border: 1px solid rgba(139, 92, 246, 0.4);
            border-radius: 50px;
            padding: 8px 18px;
            backdrop-filter: blur(12px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(139, 92, 246, 0.2);
            pointer-events: all;
            animation: hudSlideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes hudSlideIn {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }

        .pres-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pres-badge {
            font-size: 0.78rem;
            font-weight: 800;
            color: #e879f9;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-family: "Montserrat", sans-serif;
        }

        .pres-hint {
            font-size: 0.72rem;
            color: rgba(196, 181, 253, 0.7);
            font-family: "Montserrat", sans-serif;
            font-weight: 500;
        }

        .pres-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            border-left: 1px solid rgba(139, 92, 246, 0.3);
            padding-left: 14px;
        }

        .pres-btn {
            background: rgba(255, 255, 255, 0.07);
            color: #c4b5fd;
            border: 1px solid rgba(139, 92, 246, 0.3);
            padding: 5px 13px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.76rem;
            font-weight: 700;
            font-family: "Montserrat", sans-serif;
            transition: all 0.18s;
            white-space: nowrap;
        }

        .pres-btn:hover {
            background: rgba(139, 92, 246, 0.3);
            color: #fff;
            border-color: var(--accent);
        }

        .pres-exit {
            background: rgba(239, 68, 68, 0.12);
            color: #fca5a5;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .pres-exit:hover {
            background: #ef4444;
            color: #fff;
            border-color: #ef4444;
            box-shadow: 0 0 12px rgba(239, 68, 68, 0.4);
        }

        .tool-card.active {
            background: var(--accent);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 0 25px rgba(139, 92, 246, 0.5), inset 0 0 10px rgba(255, 255, 255, 0.3);
            transform: translateY(-3px) scale(1.02);
        }

        .tool-card.active i {
            transform: scale(1.15);
            filter: drop-shadow(0 0 5px rgba(255, 255, 255, 0.5));
        }


        /* ── MELHORIAS VISUAIS GLOBAIS ─────────────────────────────── */

        /* Toolbar: scroll horizontal sem barra visível */
        .action-toolbar {
            overflow-x: auto;
            overflow-y: hidden;
        }

        .action-toolbar::-webkit-scrollbar {
            height: 2px;
        }

        .action-toolbar::-webkit-scrollbar-thumb {
            background: var(--border-color);
        }

        /* Toolbar groups com label visual */
        .toolbar-section {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 3px 5px;
            background: rgba(255, 255, 255, 0.025);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-sm);
            position: relative;
        }

        .toolbar-section::before {
            content: attr(data-label);
            position: absolute;
            top: -16px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.48rem;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--text-dim);
            white-space: nowrap;
            pointer-events: none;
        }

        /* Tool cards melhores */
        .tool-card {
            position: relative;
            overflow: hidden;
        }

        .tool-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--accent);
            transform: scaleX(0);
            transition: transform 0.2s ease;
        }

        .tool-card:hover::after {
            transform: scaleX(1);
        }

        .tool-card.active::after {
            transform: scaleX(1);
            background: #fff;
        }

        /* Atalho de teclado visível nos tool cards */
        .tool-shortcut {
            font-size: 0.55rem;
            font-weight: 800;
            color: var(--accent-light);
            opacity: 0.7;
            background: rgba(139, 92, 246, 0.15);
            padding: 1px 4px;
            border-radius: 3px;
            letter-spacing: 0.5px;
        }

        /* Prop items melhores */
        .prop-item {
            transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .prop-item:hover {
            transform: translateY(-3px) scale(1.2) !important;
        }

        /* Style grid melhor */
        .style-btn {
            font-size: 0.62rem;
            letter-spacing: 0.3px;
        }

        /* Hint box melhor */
        .hint {
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-left: 3px solid rgba(139, 92, 246, 0.4);
        }

        /* Edit status indicador animado */
        .edit-status {
            position: relative;
            overflow: hidden;
        }

        .edit-status::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            border-radius: 2px;
        }

        .edit-status.room::before {
            background: #10b981;
        }

        .edit-status.prop::before {
            background: #f59e0b;
        }

        .edit-status.wall::before {
            background: #ec4899;
        }

        .edit-status.free::before {
            background: var(--accent);
        }

        /* Sidebar collapse btn mais suave */
        .sidebar-collapse-btn {
            box-shadow: none !important;
        }

        .sidebar.left .sidebar-collapse-btn {
            box-shadow: 2px 0 10px rgba(139, 92, 246, 0.3) !important;
        }

        .sidebar.right .sidebar-collapse-btn {
            box-shadow: -2px 0 10px rgba(139, 92, 246, 0.3) !important;
        }

        /* Panel section com separador visual */
        .panel-section {
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        /* Upload area mais bonita */
        .upload-area {
            background: repeating-linear-gradient(45deg,
                    rgba(139, 92, 246, 0.03),
                    rgba(139, 92, 246, 0.03) 5px,
                    transparent 5px,
                    transparent 15px);
        }

        /* Modal overlay mais bonita */
        .modal-overlay {
            background: rgba(0, 0, 0, 0.8) !important;
        }

        /* Toast melhor posicionado */
        #toastContainer {
            bottom: 24px !important;
        }

        /* Minimap com sombra mais forte */
        canvas#minimapCanvas {
            box-shadow: 0 16px 60px rgba(0, 0, 0, 0.9), 0 0 0 1px rgba(139, 92, 246, 0.3) !important;
        }

        /* Layers list melhor */
        .layer-item {
            cursor: pointer;
            background: rgba(0, 0, 0, 0.2);
        }

        .layer-item:hover {
            background: rgba(139, 92, 246, 0.1) !important;
        }

        /* Footer */
        .rodape-principal {
            flex-shrink: 0;
        }

        /* Barra de ferramentas — grupos com labels */
        .action-toolbar-inner {
            display: flex;
            align-items: center;
            gap: 4px;
            flex: 1;
            min-width: 0;
        }

        /* Scrollbar custom para sidebar */
        .sidebar-inner {
            scrollbar-width: thin;
            scrollbar-color: rgba(139, 92, 246, 0.2) transparent;
        }

        /* Kbd shortcuts badge */
        kbd {
            background: rgba(139, 92, 246, 0.15);
            border: 1px solid rgba(139, 92, 246, 0.25);
            border-radius: 4px;
            padding: 1px 5px;
            font-size: 0.72em;
            font-family: "Montserrat", sans-serif;
            font-weight: 800;
            color: var(--accent-light);
        }

        /* Style buttons com ícone */
        .style-btn i {
            margin-right: 3px;
        }

        /* Autosave badge animation */
        @keyframes pulse-green {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.4);
            }

            50% {
                box-shadow: 0 0 0 4px rgba(74, 222, 128, 0);
            }
        }

        .autosave-badge.visible {
            animation: pulse-green 1s ease-out;
        }

        /* Toolbar divider flex */
        .divider {
            flex: 1;
        }


        /* ── RÉGUA HTML ─────────────────────────────────────────────── */
        #rulerCorner {
            position: absolute;
            top: 0;
            left: 0;
            width: 26px;
            height: 26px;
            background: #8b5cf6;
            z-index: 201;
            pointer-events: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7px;
            font-weight: 900;
            color: rgba(255, 255, 255, 0.8);
        }

        #rulerH {
            position: absolute;
            top: 0;
            left: 26px;
            right: 0;
            height: 26px;
            background: #0f0c1e;
            border-bottom: 1px solid rgba(139, 92, 246, 0.3);
            z-index: 200;
            pointer-events: none;
            overflow: hidden;
        }

        #rulerV {
            position: absolute;
            top: 26px;
            left: 0;
            bottom: 0;
            width: 26px;
            background: #0f0c1e;
            border-right: 1px solid rgba(139, 92, 246, 0.3);
            z-index: 200;
            pointer-events: none;
            overflow: hidden;
        }

        .ruler-tick-h {
            position: absolute;
            top: 0;
            height: 26px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            transform: translateX(-50%);
            pointer-events: none;
        }

        .ruler-tick-h .tick-line {
            width: 1px;
            background: rgba(139, 92, 246, 0.5);
            flex-shrink: 0;
        }

        .ruler-tick-h .tick-label {
            position: absolute;
            top: 4px;
            font-size: 8px;
            font-weight: 700;
            color: #c4b5fd;
            font-family: Montserrat, sans-serif;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.9);
            white-space: nowrap;
            line-height: 1;
        }

        .ruler-tick-v {
            position: absolute;
            left: 0;
            width: 26px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .ruler-tick-v .tick-line {
            height: 1px;
            background: rgba(139, 92, 246, 0.5);
            flex-shrink: 0;
        }

        .ruler-tick-v .tick-label {
            position: absolute;
            font-size: 8px;
            font-weight: 700;
            color: #c4b5fd;
            font-family: Montserrat, sans-serif;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.9);
            white-space: nowrap;
            line-height: 1;
            left: 50%;
            transform: translateX(-50%) rotate(-90deg);
        }
    </style>
</head>

<body style="display:flex;flex-direction:column;min-height:100vh;background:#0d091a;color:#fff;">

    <!-- HEADER -->
    <header>
        <div class="logotipo">
            <a href="index.php"><img src="../img/logo_horizontal.png" alt="Logo TABLE"></a>
        </div>

        <!-- BOTÃO MENU MOBILE (HAMBURGER) -->
        <div class="menu-toggle" id="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </div>

        <nav id="nav-menu">
            <ul>
                <li><a href="index.php">Início</a></li>
                <li><a href="cm-jogar.php">Como Jogar</a></li>
                <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a>
                </li>
                <li><a href="criar-mapa.php" class="ativo">Mundos</a></li>
                <li><a href="rolagem-de-dados.php">Dados</a></li>
                <li><a href="sobre-nos.php">Sobre Nós</a></li>
            </ul>

            <!-- BOTÕES MOBILE -->
            <div class="nav-mobile-footer">
                <?php if (isset($_SESSION['usuario'])): ?>
                    <div class="usuario-logado-nav" onclick="window.location.href='perfil.php'">
                        <img src="<?= !empty($_SESSION['usuario']['foto']) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar1.png' ?>"
                            alt="Avatar Navbar" class="avatar-nav">
                        <span class="nome-nav"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="botao-entrar">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                    <a href="cadastro.php" class="botao-cadastrar">
                        <i class="fas fa-user-plus"></i> Cadastre-se
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <?php if (isset($_SESSION['usuario'])): ?>
            <div class="usuario-logado-nav desktop-only" id="nav-logado" onclick="window.location.href='perfil.php'"
                title="Ir para o Perfil">
                <img src="<?= !empty($_SESSION['usuario']['foto']) ? $_SESSION['usuario']['foto'] : '../img/uploads/perfil/avatar1.png' ?>"
                    alt="Avatar Navbar" class="avatar-nav">
                <span class="nome-nav"><?= htmlspecialchars($_SESSION['usuario']['nome']) ?></span>
            </div>
        <?php else: ?>
            <div class="botoes-navegacao desktop-only" id="nav-deslogado">
                <a href="login.php" class="botao-entrar">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="cadastro.php" class="botao-cadastrar">
                    <i class="fas fa-user-plus"></i> Cadastre-se
                </a>
            </div>
        <?php endif; ?>
    </header>

    <!-- MODAIS -->
    <div id="modalOverlay" class="modal-overlay">
        <div id="modalClear" class="modal-box">
            <h3><i class="fas fa-trash-alt" style="color:#ef4444;"></i> Limpar Mapa</h3>
            <p>Tem certeza que deseja apagar <b>todo o mapa</b>? Esta ação não pode ser desfeita.</p>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModals()">Cancelar</button>
                <button class="btn-confirm btn-danger-modal" onclick="confirmClear()"><i class="fas fa-trash"></i> Sim,
                    Apagar Tudo</button>
            </div>
        </div>
        <div id="modalExport" class="modal-box">
            <h3><i class="fas fa-image" style="color:#8b5cf6;"></i> Exportar como PNG</h3>
            <p>Escolha o tipo de fundo para a imagem exportada:</p>
            <div class="modal-actions-col">
                <button class="btn-confirm" onclick="confirmExport(true)"><i class="fas fa-moon"></i> Fundo Escuro
                    (Original)</button>
                <button class="btn-confirm light" onclick="confirmExport(false)"><i class="fas fa-sun"></i> Fundo Branco
                    Limpo</button>
                <button class="btn-cancel" style="margin-top:6px;" onclick="closeModals()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- EDITOR -->
    <div class="editor-wrapper">

        <!-- TOOLBAR SUPERIOR -->
        <div class="action-toolbar">
            <!-- Histórico -->
            <button class="action-btn" onclick="undo()" title="Desfazer (Ctrl+Z)">
                <i class="fas fa-undo"></i> Desfazer <span id="undoCount" class="undo-count"></span>
            </button>
            <button class="action-btn text-red" onclick="showModal('modalClear')" title="Limpar tudo">
                <i class="fas fa-trash"></i> Limpar
            </button>
            <div class="sep"></div>

            <!-- Zoom -->
            <div class="toolbar-group">
                <button onclick="doZoom(1.15)" class="action-btn" title="Ampliar [+]"><i
                        class="fas fa-search-plus"></i></button>
                <button onclick="doZoom(0.87)" class="action-btn" title="Reduzir [-]"><i
                        class="fas fa-search-minus"></i></button>
                <button onclick="resetView()" class="action-btn" title="Resetar câmera [Esc]"><i
                        class="fas fa-home"></i></button>
            </div>

            <div class="sep"></div>

            <!-- Utilitários -->
            <div class="toolbar-group">
                <button onclick="toggleRuler()" id="toggleRuler" class="action-btn active" title="Régua de medida">
                    <i class="fas fa-ruler-combined"></i> Régua
                </button>
                <button onclick="toggleMinimap()" id="minimapToggleBtn" class="action-btn active" title="Minimapa [M]">
                    <i class="fas fa-search-plus"></i> Lupa/Mapa
                </button>
                <button onclick="toggleGrid()" id="toggleGrid" class="action-btn" title="Grade [G]">
                    <i class="fas fa-border-all"></i> Grade
                </button>
            </div>

            <div class="sep"></div>

            <!-- Modos especiais -->
            <div class="toolbar-group">
                <button onclick="toggleFog()" id="btnFog" class="action-btn fog-btn" title="Névoa de Guerra [F]">
                    <i class="fas fa-cloud"></i> Névoa
                </button>
                <button onclick="togglePresentation()" class="action-btn presentation-btn"
                    title="Modo Apresentação [P]">
                    <i class="fas fa-play-circle"></i> Apresentação
                </button>
            </div>

            <div style="flex:1;"></div>

            <!-- Tamanho de célula -->
            <div class="slider-group">
                <i class="fas fa-th" style="color:var(--accent-light);"></i>
                <input type="range" id="cellSlider" min="20" max="100" value="40" oninput="setCellSize(this.value)">
                <span id="cellVal" class="undo-count">40px</span>
            </div>

            <div class="sep"></div>

            <!-- Arquivo -->
            <div class="toolbar-group">
                <button onclick="showModal('modalExport')" class="action-btn highlight" title="Exportar PNG">
                    <i class="fas fa-file-image"></i> PNG
                </button>
                <button onclick="saveJSON()" class="action-btn highlight" title="Salvar projeto (Ctrl+S)">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <button onclick="loadJSON()" class="action-btn" title="Abrir projeto (Ctrl+O)">
                    <i class="fas fa-folder-open"></i> Abrir
                </button>
            </div>

            <div id="autosaveBadge" class="autosave-badge" style="margin-left:6px;">
                <i class="fas fa-cloud-upload-alt"></i> Salvo
            </div>
        </div>

        <div class="main-area">

            <!-- SIDEBAR ESQUERDA — Ferramentas -->
            <div class="sidebar left" id="sidebarLeft">
                <button class="sidebar-collapse-btn left" onclick="toggleSidebar('left')" title="Recolher painel">
                    <i class="fas fa-chevron-left"></i>
                    <span class="collapse-label">FERRAMENTAS</span>
                </button>
                <div class="sidebar-inner">

                    <!-- FERRAMENTAS DE DESENHO -->
                    <div class="panel-section">
                        <div class="panel-title"><i class="fas fa-tools"></i> Ferramentas de Desenho</div>
                        <div class="tool-grid">
                            <div class="tool-card" id="btn-select" onclick="setTool('select')"
                                title="Selecionar e mover [V]">
                                <div class="tool-icon"><i class="fas fa-mouse-pointer"></i></div>
                                <div class="tool-name">Selecionar</div>
                                <div class="tool-shortcut">V</div>
                            </div>
                            <div class="tool-card active" id="btn-room" onclick="setTool('room')"
                                title="Pintar/Desenhar salas [R]">
                                <div class="tool-icon"><i class="fas fa-paint-brush"></i></div>
                                <div class="tool-name">Pintar/Sala</div>
                                <div class="tool-shortcut">R</div>
                            </div>
                            <div class="tool-card" id="btn-wall" onclick="setTool('wall')" title="Muros internos [W]">
                                <div class="tool-icon"><i class="fas fa-ruler-combined"></i></div>
                                <div class="tool-name">Linha/Muro</div>
                                <div class="tool-shortcut">W</div>
                            </div>
                            <div class="tool-card" id="btn-erase" onclick="setTool('erase')" title="Borracha [E]">
                                <div class="tool-icon"><i class="fas fa-eraser"></i></div>
                                <div class="tool-name">Borracha</div>
                                <div class="tool-shortcut">E</div>
                            </div>
                            <div class="tool-card" id="btn-text" onclick="setTool('text')" title="Texto livre [T]">
                                <div class="tool-icon"><i class="fas fa-font"></i></div>
                                <div class="tool-name">Texto</div>
                                <div class="tool-shortcut">T</div>
                            </div>
                        </div>
                    </div>

                    <!-- TEXTO LIVRE -->
                    <div class="panel-section">
                        <div class="panel-title">✍️ Texto Livre</div>
                        <input type="text" id="textInput" placeholder="Digite o rótulo do texto..."
                            class="text-tool-input" maxlength="80" style="width:100%;margin-bottom:10px;">
                        <div class="setting-row">
                            <span>Tamanho</span>
                            <input type="number" id="textSize" value="18" min="8" max="96" style="width:58px;">
                        </div>
                        <div class="setting-row">
                            <span>Cor do Texto</span>
                            <input type="color" id="textColor" value="#ffffff">
                        </div>
                        <div class="setting-row">
                            <span>Negrito</span>
                            <input type="checkbox" id="textBold"
                                style="width:18px;height:18px;accent-color:var(--accent);">
                        </div>
                        <button id="btnAddText" onclick="addTextLabel()"
                            style="width:100%;padding:9px;background:var(--accent);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:0.8rem;margin-top:6px;display:flex;align-items:center;justify-content:center;gap:6px;">
                            <i class="fas fa-plus"></i> Adicionar ao Mapa
                        </button>
                    </div>

                    <!-- SNAP À GRADE -->
                    <div class="panel-section">
                        <div class="panel-title"><i class="fas fa-magnet"></i> Snap à Grade</div>
                        <div class="btn-group">
                            <button id="btnSnapOn" class="btn-toggle active" onclick="toggleSnap(true)"><i
                                    class="fas fa-th"></i> Ativado</button>
                            <button id="btnSnapOff" class="btn-toggle" onclick="toggleSnap(false)"><i
                                    class="fas fa-times"></i> Livre</button>
                        </div>
                    </div>

                    <!-- CAMADAS -->
                    <div class="panel-section">
                        <div class="panel-title"><i class="fas fa-layer-group"></i> Camadas</div>
                        <div id="layersList" class="layers-list"></div>
                        <p class="hint" style="margin-top:8px;">
                            <b>👁</b> Ocultar/Mostrar &nbsp;·&nbsp; <b>🔒</b> Travar camada
                        </p>
                    </div>

                    <!-- NÉVOA DE GUERRA -->
                    <div class="panel-section" id="fogPanel" style="display:none;">
                        <div class="panel-title"><i class="fas fa-cloud"></i> Névoa de Guerra</div>
                        <div class="btn-group" style="margin-bottom:10px;">
                            <button id="btnFogReveal" class="btn-toggle active" onclick="setFogMode('reveal')">👁
                                Revelar</button>
                            <button id="btnFogCover" class="btn-toggle" onclick="setFogMode('cover')">🌫
                                Encobrir</button>
                        </div>
                        <div class="setting-row">
                            <span>Pincel</span>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <input type="range" id="fogBrushSlider" min="1" max="8" value="2" style="width:75px;"
                                    oninput="setFogBrush(this.value)">
                                <span id="fogBrushVal"
                                    style="font-size:0.78rem;color:var(--accent);min-width:14px;font-weight:700;">2</span>
                            </div>
                        </div>
                        <div style="display:flex;gap:5px;margin-top:6px;">
                            <button onclick="revealAll()"
                                style="flex:1;padding:7px;background:rgba(34,197,94,0.12);color:#4ade80;border:1px solid rgba(34,197,94,0.3);border-radius:6px;cursor:pointer;font-weight:700;font-size:0.72rem;font-family:Montserrat,sans-serif;">
                                <i class="fas fa-sun"></i> Revelar Tudo
                            </button>
                            <button onclick="coverAll()"
                                style="flex:1;padding:7px;background:rgba(99,102,241,0.12);color:#a5b4fc;border:1px solid rgba(99,102,241,0.3);border-radius:6px;cursor:pointer;font-weight:700;font-size:0.72rem;font-family:Montserrat,sans-serif;">
                                <i class="fas fa-moon"></i> Encobrir Tudo
                            </button>
                        </div>
                        <p class="hint" style="margin-top:8px;">Use a ferramenta <b>Névoa</b> no canvas para revelar ou
                            encobrir regiões arrastando o mouse.</p>
                    </div>

                    <!-- ATALHOS -->
                    <div class="panel-section">
                        <div class="panel-title"><i class="fas fa-keyboard"></i> Atalhos de Teclado</div>
                        <p class="hint">
                            <kbd>Ctrl+Z</kbd> Desfazer<br>
                            <kbd>Ctrl+D</kbd> Duplicar seleção<br>
                            <kbd>Delete</kbd> Apagar seleção<br>
                            <kbd>Espaço</kbd> Mover câmera<br>
                            <kbd>Scroll</kbd> Zoom in/out<br>
                            <kbd>Alt+Arr.</kbd> Duplicar item<br>
                            <kbd>Sh+Arr.</kbd> Seleção múltipla<br>
                            <kbd>2× Clique</kbd> Renomear sala<br>
                            <kbd>Btn Dir.</kbd> Menu contexto<br>
                            <kbd>F</kbd> Névoa &nbsp;<kbd>P</kbd> Apresentação
                        </p>
                    </div>

                </div>
            </div>

            <!-- CANVAS -->
            <div class="canvas-container" id="canvasWrap">
                <canvas id="mainCanvas"></canvas>
                <canvas id="minimapCanvas"></canvas>
                <canvas id="lupCanvas" width="160" height="160"></canvas>
                <div id="lupLabel"></div>
                <!-- Régua HTML -->
                <div id="rulerCorner">⊹</div>
                <div id="rulerH"></div>
                <div id="rulerV"></div>
            </div>

            <!-- SIDEBAR DIREITA — Estilos e Objetos -->
            <div class="sidebar right" id="sidebarRight">
                <button class="sidebar-collapse-btn right" onclick="toggleSidebar('right')" title="Recolher painel">
                    <i class="fas fa-chevron-right"></i>
                    <span class="collapse-label">ESTILOS</span>
                </button>
                <div class="sidebar-inner">

                    <!-- STATUS -->
                    <div class="panel-section">
                        <div id="editModeLabel" class="edit-status free">🔵 Ferramenta Livre</div>
                        <button id="btnRemoveBg" class="btn-magic" style="display:none;margin-top:8px;"
                            onclick="removeBackgroundFromSelected()">
                            <i class="fas fa-magic"></i> Remover Fundo da Imagem
                        </button>
                        <div class="setting-row" style="margin-top:10px;">
                            <span>Opacidade</span>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input id="opacitySlider" type="range" min="0" max="100" value="100" style="width:75px;"
                                    oninput="updateOpacity(this.value)">
                                <span id="opacityVal"
                                    style="font-size:0.75rem;color:var(--accent);min-width:34px;font-weight:700;">100%</span>
                            </div>
                        </div>
                    </div>

                    <!-- ESTILOS -->
                    <div class="panel-section">
                        <div class="panel-title"><i class="fas fa-palette"></i> Estilos de Terreno</div>
                        <div class="style-preview-row" style="margin-bottom:10px;">
                            <span>Estilo Ativo:</span>
                            <div id="stylePreview" class="style-preview-swatch" style="background:#4b5563;"></div>
                        </div>

                        <div class="subtitle" style="margin-bottom:7px;">🏛️ Masmorras</div>
                        <div class="style-grid">
                            <div class="style-btn active" id="style-solid" style="background:#4b5563;"
                                onclick="setStyle('solid','#4b5563')">Parede Sólida</div>
                            <div class="style-btn" id="style-cavern" style="background:#374151;"
                                onclick="setStyle('cavern','#374151')">Caverna</div>
                            <div class="style-btn" id="style-temple" style="background:#5c4a1e;"
                                onclick="setStyle('temple','#5c4a1e')">Templo</div>
                            <div class="style-btn" id="style-crypt" style="background:#1f2a1f;"
                                onclick="setStyle('crypt','#1f2a1f')">Cripta</div>
                            <div class="style-btn" id="style-fortress" style="background:#3d3528;"
                                onclick="setStyle('fortress','#3d3528')">Fortaleza</div>
                            <div class="style-btn" id="style-prison" style="background:#2a2020;"
                                onclick="setStyle('prison','#2a2020')">Prisão</div>
                            <div class="style-btn" id="style-crystal" style="background:#1a1a3a;"
                                onclick="setStyle('crystal','#1a1a3a')">Cristal</div>
                        </div>

                        <div class="subtitle" style="margin-top:14px;margin-bottom:7px;">🌿 Terrenos</div>
                        <div class="style-grid">
                            <div class="style-btn" onclick="setStyle('water','#24a0ed')" id="style-water"
                                style="background:#24a0ed"><i class="fas fa-tint"></i> Água</div>
                            <div class="style-btn" onclick="setStyle('foliage','#2d7a4d')" id="style-foliage"
                                style="background:#2d7a4d"><i class="fas fa-leaf"></i> Floresta</div>
                            <div class="style-btn" onclick="setStyle('lava','#c53030')" id="style-lava"
                                style="background:#c53030"><i class="fas fa-fire-alt"></i> Lava</div>
                            <div class="style-btn" onclick="setStyle('road','#594a3a')" id="style-road"
                                style="background:#594a3a"><i class="fas fa-road"></i> Estrada</div>
                            <div class="style-btn" onclick="setStyle('sand','#d4a24c')" id="style-sand"
                                style="background:#d4a24c"><i class="fas fa-umbrella-beach"></i> Areia</div>
                            <div class="style-btn" onclick="setStyle('snow','#e2e8f0')" id="style-snow"
                                style="background:#e2e8f0;color:#333"><i class="fas fa-snowflake"></i> Neve</div>
                            <div class="style-btn" onclick="setStyle('swamp','#315a39')" id="style-swamp"
                                style="background:#315a39"><i class="fas fa-frog"></i> Pântano</div>
                            <div class="style-btn" onclick="setStyle('darkforest','#0f2e1b')" id="style-darkforest"
                                style="background:#0f2e1b"><i class="fas fa-tree"></i> Fl. Sombria</div>
                            <div class="style-btn" onclick="setStyle('mud','#402a1d')" id="style-mud"
                                style="background:#402a1d"><i class="fas fa-shoe-prints"></i> Lama</div>
                            <div class="style-btn" onclick="setStyle('blood','#821c1c')" id="style-blood"
                                style="background:#821c1c"><i class="fas fa-skull-crossbones"></i> Sangue</div>
                            <div class="style-btn" onclick="setStyle('ice','#8ecae6')" id="style-ice"
                                style="background:#8ecae6"><i class="fas fa-icicles"></i> Gelo</div>
                            <div class="style-btn" onclick="setStyle('mushroom','#8a5cf6')" id="style-mushroom"
                                style="background:#8a5cf6"><i class="fas fa-seedling"></i> Cogumelos</div>
                            <div class="style-btn" onclick="setStyle('void','#1a103c')" id="style-void"
                                style="background:#1a103c"><i class="fas fa-ghost"></i> Vazio</div>
                        </div>

                        <div style="margin-top:14px;">
                            <div class="setting-row">
                                <span>Fundo / Textura</span>
                                <input type="color" value="#4b5563" id="fillColor"
                                    oninput="updateProp('fillColor',this.value)">
                            </div>
                            <div class="setting-row">
                                <span>Cor da Borda/Linha</span>
                                <input type="color" value="#000000" id="wallColor"
                                    oninput="updateProp('wallColor',this.value)">
                            </div>
                            <div class="setting-row">
                                <span>Espessura (px)</span>
                                <input type="number" value="4" min="0" id="wallWidth"
                                    oninput="updateProp('wallWidth',parseInt(this.value))" style="width:58px;">
                            </div>
                            <div class="setting-row">
                                <span>Chão Interno</span>
                                <input type="color" value="#15101f" id="floorColor"
                                    oninput="updateProp('floorColor',this.value)">
                            </div>
                        </div>
                    </div>

                    <!-- OBJETOS E IMAGENS -->
                    <div class="panel-section">
                        <div class="panel-title"><i class="fas fa-cube"></i> Objetos e Imagens</div>
                        <label class="upload-area">
                            <i class="fas fa-folder-open" style="color:#fcd34d;margin-right:6px;"></i>
                            Enviar imagem do PC
                            <input type="file" id="imageUploader" accept="image/*" style="display:none;">
                        </label>
                        <!-- EMOJI PERSONALIZADO -->
                        <div style="margin-bottom:12px;">
                            <div class="subtitle" style="margin-bottom:8px;">✨ Emoji Personalizado</div>

                            <!-- Campo + botão adicionar -->
                            <div style="display:flex;gap:6px;align-items:stretch;margin-bottom:8px;">
                                <div style="position:relative;flex:1;">
                                    <input type="text" id="customEmojiInput" placeholder="Cole qualquer emoji aqui..."
                                        style="width:100%;background:rgba(0,0,0,0.35);color:#fff;border:1px solid var(--border-color);border-radius:8px;padding:9px 12px;font-size:1.4rem;text-align:center;outline:none;font-family:inherit;transition:border-color 0.2s;"
                                        onfocus="this.style.borderColor='var(--accent)'"
                                        onblur="this.style.borderColor='var(--border-color)'"
                                        onkeydown="if(event.key==='Enter')addCustomEmoji()">
                                </div>
                                <button onclick="addCustomEmoji()" title="Adicionar emoji ao mapa (Enter)"
                                    style="padding:0 14px;background:var(--accent);color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:0.78rem;font-weight:700;font-family:Montserrat,sans-serif;transition:all 0.2s;white-space:nowrap;"
                                    onmouseover="this.style.background='var(--accent-hover)'"
                                    onmouseout="this.style.background='var(--accent)'">
                                    <i class="fas fa-plus"></i> Adicionar
                                </button>
                            </div>

                            <!-- Dica do seletor do sistema -->
                            <div
                                style="display:flex;align-items:center;gap:6px;background:rgba(139,92,246,0.08);border:1px solid rgba(139,92,246,0.2);border-radius:7px;padding:7px 10px;margin-bottom:8px;">
                                <i class="fas fa-lightbulb" style="color:#fcd34d;font-size:0.85rem;flex-shrink:0;"></i>
                                <span style="font-size:0.7rem;color:var(--text-muted);line-height:1.4;">
                                    Abra o <b style="color:#c4b5fd;">seletor de emojis</b> do seu sistema:<br>
                                    <b style="color:#fff;">Windows:</b> tecla <kbd
                                        style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:3px;padding:1px 4px;font-size:0.75em;">Win
                                        + .</kbd> &nbsp;
                                    <b style="color:#fff;">Mac:</b> <kbd
                                        style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:3px;padding:1px 4px;font-size:0.75em;">⌘
                                        Ctrl Espaço</kbd>
                                </span>
                            </div>

                            <!-- Picker rápido por categoria -->
                            <div
                                style="font-size:0.62rem;font-weight:700;color:var(--text-dim);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:5px;">
                                Clique para inserir no campo:</div>
                            <div id="emojiPickerGrid"
                                style="display:grid;grid-template-columns:repeat(8,1fr);gap:3px;max-height:140px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:var(--border-color) transparent;">
                            </div>
                        </div>

                        <div class="subtitle" style="margin-top:10px;">⚔️ Itens de Aventura</div>
                        <div class="prop-list">
                            <div class="prop-item" onclick="addEmojiProp('🚪')" title="Porta">🚪</div>
                            <div class="prop-item" onclick="addEmojiProp('🛏️')" title="Cama">🛏️</div>
                            <div class="prop-item" onclick="addEmojiProp('📦')" title="Baú">📦</div>
                            <div class="prop-item" onclick="addEmojiProp('🪑')" title="Cadeira">🪑</div>
                            <div class="prop-item" onclick="addEmojiProp('🔥')" title="Fogo">🔥</div>
                            <div class="prop-item" onclick="addEmojiProp('🩸')" title="Sangue">🩸</div>
                            <div class="prop-item" onclick="addEmojiProp('💀')" title="Caveira">💀</div>
                            <div class="prop-item" onclick="addEmojiProp('⚔️')" title="Espada">⚔️</div>
                        </div>

                        <div class="subtitle" style="margin-top:12px;">🧙 Personagens & Magia</div>
                        <div class="prop-list">
                            <div class="prop-item" onclick="addEmojiProp('🧙')" title="Mago">🧙</div>
                            <div class="prop-item" onclick="addEmojiProp('🐉')" title="Dragão">🐉</div>
                            <div class="prop-item" onclick="addEmojiProp('🗡️')" title="Adaga">🗡️</div>
                            <div class="prop-item" onclick="addEmojiProp('🛡️')" title="Escudo">🛡️</div>
                            <div class="prop-item" onclick="addEmojiProp('💎')" title="Gema">💎</div>
                            <div class="prop-item" onclick="addEmojiProp('🔮')" title="Bola de Cristal">🔮</div>
                            <div class="prop-item" onclick="addEmojiProp('📜')" title="Pergaminho">📜</div>
                            <div class="prop-item" onclick="addEmojiProp('🗺️')" title="Mapa">🗺️</div>
                        </div>

                        <div class="subtitle" style="margin-top:12px;">🏛️ Estruturas</div>
                        <div class="prop-list">
                            <div class="prop-item" onclick="addEmojiProp('🏰')" title="Castelo">🏰</div>
                            <div class="prop-item" onclick="addEmojiProp('⛪')" title="Igreja">⛪</div>
                            <div class="prop-item" onclick="addEmojiProp('🌲')" title="Árvore">🌲</div>
                            <div class="prop-item" onclick="addEmojiProp('⛰️')" title="Montanha">⛰️</div>
                            <div class="prop-item" onclick="addEmojiProp('🌋')" title="Vulcão">🌋</div>
                            <div class="prop-item" onclick="addEmojiProp('🏔️')" title="Pico nevado">🏔️</div>
                            <div class="prop-item" onclick="addEmojiProp('🕍')" title="Templo">🕍</div>
                            <div class="prop-item" onclick="addEmojiProp('🪤')" title="Armadilha">🪤</div>
                        </div>

                        <div class="subtitle" style="margin-top:12px;">💡 Iluminação & Perigo</div>
                        <div class="prop-list">
                            <div class="prop-item" onclick="addEmojiProp('🕯️')" title="Vela">🕯️</div>
                            <div class="prop-item" onclick="addEmojiProp('🪔')" title="Lampião">🪔</div>
                            <div class="prop-item" onclick="addEmojiProp('💣')" title="Bomba">💣</div>
                            <div class="prop-item" onclick="addEmojiProp('☠️')" title="Perigo">☠️</div>
                            <div class="prop-item" onclick="addEmojiProp('⚡')" title="Raio">⚡</div>
                            <div class="prop-item" onclick="addEmojiProp('❄️')" title="Gelo">❄️</div>
                            <div class="prop-item" onclick="addEmojiProp('🌀')" title="Vórtice">🌀</div>
                            <div class="prop-item" onclick="addEmojiProp('👁️')" title="Olho">👁️</div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>


    <div id="toastContainer"></div>

    <script>
        // =============================================================================
        // TABLE RPG MAP EDITOR — script.js (v3.0)
        // =============================================================================

        // ─── ESTADO GLOBAL ────────────────────────────────────────────────────────────
        const State = (() => {
            let _rooms = [], _internalWalls = [], _props = [], _terrainCells = {}, _textLabels = [];
            let _layers = [
                { id: 'terrain', name: 'Terrenos', visible: true, locked: false },
                { id: 'rooms', name: 'Salas', visible: true, locked: false },
                { id: 'walls', name: 'Muros', visible: true, locked: false },
                { id: 'props', name: 'Objetos', visible: true, locked: false },
                { id: 'text', name: 'Texto', visible: true, locked: false },
            ];
            let _activeLayer = '';
            return {
                get rooms() { return _rooms; }, set rooms(v) { _rooms = v; },
                get internalWalls() { return _internalWalls; }, set internalWalls(v) { _internalWalls = v; },
                get props() { return _props; }, set props(v) { _props = v; },
                get terrainCells() { return _terrainCells; }, set terrainCells(v) { _terrainCells = v; },
                get textLabels() { return _textLabels; }, set textLabels(v) { _textLabels = v; },
                get layers() { return _layers; }, set layers(v) { _layers = v; },
                get activeLayer() { return _activeLayer; }, set activeLayer(v) { _activeLayer = v; },
                isLayerVisible(id) { return _layers.find(l => l.id === id)?.visible ?? true; },
                isLayerLocked(id) { return _layers.find(l => l.id === id)?.locked ?? false; },
                moveLayer(id, dir) {
                    const idx = _layers.findIndex(l => l.id === id);
                    if (idx === -1) return;
                    const newIdx = idx + dir;
                    if (newIdx < 0 || newIdx >= _layers.length) return;
                    const item = _layers.splice(idx, 1)[0];
                    _layers.splice(newIdx, 0, item);
                    buildLayersPanel();
                    AutoSave.scheduleSave();
                },
                snapshot() {
                    return {
                        rooms: _rooms.map(r => ({ ...r })),
                        internalWalls: _internalWalls.map(w => ({ ...w })),
                        props: _props.map(p => ({ ...p })),
                        terrainCells: JSON.parse(JSON.stringify(_terrainCells)),
                        textLabels: _textLabels.map(t => ({ ...t })),
                        layers: _layers.map(l => ({ ...l })), // Salva ordem e travas
                    };
                },
                restore(snap) {
                    _rooms = snap.rooms; _internalWalls = snap.internalWalls;
                    _props = snap.props; _terrainCells = snap.terrainCells;
                    _textLabels = snap.textLabels || [];
                    if (snap.layers) _layers = snap.layers;
                    buildLayersPanel();
                }
            };
        })();

        // ─── HISTÓRICO ────────────────────────────────────────────────────────────────
        const History = (() => {
            const MAX = 100; let queue = [];
            function save() { queue.push(State.snapshot()); if (queue.length > MAX) queue.shift(); updateCounter(); }
            function undo() { if (!queue.length) return false; State.restore(queue.pop()); updateCounter(); return true; }
            function clear() { queue = []; updateCounter(); }
            function updateCounter() { const el = document.getElementById('undoCount'); if (el) el.textContent = queue.length ? `(${queue.length})` : ''; }
            return { save, undo, clear, get length() { return queue.length; } };
        })();

        // ─── AUTOSAVE ─────────────────────────────────────────────────────────────────
        const AutoSave = (() => {
            const KEY = 'table_rpg_v3'; let timer = null;
            function serialize() {
                const snap = State.snapshot();
                const safeProps = snap.props.map(p => {
                    if (p.type === 'image') { try { const c = document.createElement('canvas'); c.width = p.content.width; c.height = p.content.height; c.getContext('2d').drawImage(p.content, 0, 0); return { ...p, content: c.toDataURL() }; } catch { return null; } }
                    return p;
                }).filter(Boolean);
                return JSON.stringify({ CELL, panX, panY, scale, ...snap, props: safeProps });
            }
            function load() {
                try { const raw = localStorage.getItem(KEY); if (!raw) return false; const data = JSON.parse(raw); applyLoadedData(data); showToast('💾 Rascunho restaurado!'); return true; }
                catch (e) { return false; }
            }
            function scheduleSave() {
                clearTimeout(timer);
                timer = setTimeout(() => { try { localStorage.setItem(KEY, serialize()); showBadge(); } catch (e) { } }, 1500);
            }
            function showBadge() { const el = document.getElementById('autosaveBadge'); if (!el) return; el.classList.add('visible'); setTimeout(() => el.classList.remove('visible'), 2000); }
            function clear() { localStorage.removeItem(KEY); }
            return { load, scheduleSave, clear };
        })();

        // ─── CANVAS & VIEWPORT ───────────────────────────────────────────────────────
        const canvas = document.getElementById('mainCanvas');
        const ctx = canvas.getContext('2d');
        const wrap = document.getElementById('canvasWrap');

        let width, height;
        let CELL = 40;
        let panX = 0, panY = 0, scale = 1;
        let isPanning = false, startPanX, startPanY;
        let currentRoom = null, currentWall = null;
        let selectedItem = null;
        let multiSelection = [];
        let selectionRect = null;
        let isDrawing = false, isPainting = false, isErasing = false;
        let snapToGrid = true, currentTool = 'room', dragAction = 'move', spaceDown = false;
        let altDown = false;
        let showGridLines = false;
        let showRoomLabels = true;
        let animFrame = 0;
        let lastClickTime = 0;

        // ─── NÉVOA DE GUERRA ─────────────────────────────────────────────────────────
        // fogCells: Set de chaves "c,r" que estão reveladas (default = tudo coberto)
        let fogEnabled = false;
        let fogRevealed = new Set();   // células reveladas pelo mestre
        let isFogging = false;         // arrastar para revelar/encobrir
        let fogMode = 'reveal';        // 'reveal' | 'cover'
        let fogBrushSize = 2;          // raio em células de terreno (CELL/2)

        // ─── MODO APRESENTAÇÃO ───────────────────────────────────────────────────────
        let presentationMode = false;
        const emojiCache = {};
        let globals = { style: 'solid', fillColor: '#4b5563', wallColor: '#000000', wallWidth: 4, floorColor: '#15101f', opacity: 1 };

        function getEmojiCanvas(emoji, size) {
            const key = emoji + '|' + size;
            if (emojiCache[key]) return emojiCache[key];
            const ec = document.createElement('canvas');
            ec.width = size; ec.height = size;
            const ex = ec.getContext('2d');
            ex.font = Math.round(size * 0.82) + 'px Arial, sans-serif';
            ex.textAlign = 'center'; ex.textBaseline = 'middle';
            ex.fillText(emoji, size / 2, size / 2);
            emojiCache[key] = ec;
            return ec;
        }

        // ─── RESIZE ──────────────────────────────────────────────────────────────────
        function resize() {
            width = wrap.clientWidth; height = wrap.clientHeight;
            canvas.width = width; canvas.height = height;
        }
        window.addEventListener('resize', resize);
        resize(); setCursor();

        // ─── LOOP DE ANIMAÇÃO ────────────────────────────────────────────────────────
        function animLoop() {
            animFrame++;
            render(); renderMinimap();
            requestAnimationFrame(animLoop);
        }
        requestAnimationFrame(animLoop);
        render(); // Chamada inicial para evitar flicker

        // ─── TECLADO ─────────────────────────────────────────────────────────────────
        document.addEventListener('keydown', e => {
            // Atalhos de Usabilidade
            if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                if (e.code === 'KeyV') setTool('select');
                if (e.code === 'KeyR') setTool('room');
                if (e.code === 'KeyW') setTool('wall');
                if (e.code === 'KeyT') setTool('text');
                if (e.code === 'KeyP') setTool('room'); // Pintar/Salas compartilha atalho
                if (e.code === 'KeyE' || e.code === 'KeyB') setTool('erase');
                if (e.code === 'KeyG') toggleGrid();
                if (e.code === 'KeyM') toggleMinimap();

                // Atalhos Numéricos para Estilos de Masmorra
                if (e.code === 'Digit1') setStyle('solid', '#4b5563');
                if (e.code === 'Digit2') setStyle('cavern', '#374151');
                if (e.code === 'Digit3') setStyle('temple', '#5c4a1e');
                if (e.code === 'Digit4') setStyle('crypt', '#1f2a1f');
                if (e.code === 'Digit5') setStyle('fortress', '#3d3528');
                if (e.code === 'Digit6') setStyle('prison', '#2a2020');
                if (e.code === 'Digit7') setStyle('crystal', '#1a1a3a');
            }


            if (e.code === 'Space' && !e.repeat && document.activeElement.tagName !== 'INPUT') { e.preventDefault(); spaceDown = true; setCursor(); }

            if (e.altKey) altDown = true;
            if ((e.ctrlKey || e.metaKey) && e.code === 'KeyZ') { e.preventDefault(); undo(); }
            if ((e.ctrlKey || e.metaKey) && e.code === 'KeyD') { e.preventDefault(); duplicateSelected(); }
            if ((e.code === 'Delete' || e.code === 'Backspace') && document.activeElement.tagName !== 'INPUT') {
                if (multiSelection.length > 0) {
                    History.save();
                    const ids = new Set(multiSelection.map(s => s.obj.id));
                    State.rooms = State.rooms.filter(r => !ids.has(r.id));
                    State.props = State.props.filter(p => !ids.has(p.id));
                    State.textLabels = State.textLabels.filter(t => !ids.has(t.id));
                    multiSelection = []; selectedItem = null; loadInputsFromSelection(); AutoSave.scheduleSave(); return;
                }
                if (selectedItem) {
                    History.save();
                    if (selectedItem.type === 'room') State.rooms = State.rooms.filter(r => r.id !== selectedItem.obj.id);
                    if (selectedItem.type === 'prop') State.props = State.props.filter(p => p.id !== selectedItem.obj.id);
                    if (selectedItem.type === 'wall') State.internalWalls = State.internalWalls.filter(w => w !== selectedItem.obj);
                    if (selectedItem.type === 'text') State.textLabels = State.textLabels.filter(t => t.id !== selectedItem.obj.id);
                    selectedItem = null; loadInputsFromSelection(); AutoSave.scheduleSave();
                }
            }
            if (e.code === 'KeyF' && document.activeElement.tagName !== 'INPUT') { toggleFog(); return; }
            if (e.code === 'KeyP' && document.activeElement.tagName !== 'INPUT') { togglePresentation(); return; }
            if (e.code === 'Escape') { if (presentationMode) { exitPresentation(); return; } closeContextMenu(); selectedItem = null; multiSelection = []; selectionRect = null; loadInputsFromSelection(); isDrawing = false; isPainting = false; isErasing = false; isFogging = false; currentRoom = null; currentWall = null; }
        });
        document.addEventListener('keyup', e => {
            if (e.code === 'Space') { spaceDown = false; setCursor(); }
            if (!e.altKey) altDown = false;
        });

        // ─── ZOOM ────────────────────────────────────────────────────────────────────
        function doZoom(f) {
            const cx = width / 2, cy = height / 2;
            const ns = Math.max(0.2, Math.min(scale * f, 5));
            panX = cx - (cx - panX) * (ns / scale); panY = cy - (cy - panY) * (ns / scale); scale = ns;
        }
        function resetView() { scale = 1; panX = 0; panY = 0; }
        function setCellSize(val) {
            const ns = parseInt(val), ratio = ns / CELL; CELL = ns;
            document.getElementById('cellVal').textContent = CELL + 'px';
            State.rooms.forEach(r => { r.x *= ratio; r.y *= ratio; r.w *= ratio; r.h *= ratio; });
            State.internalWalls.forEach(w => { w.x1 *= ratio; w.y1 *= ratio; w.x2 *= ratio; w.y2 *= ratio; });
            State.props.forEach(p => { p.x *= ratio; p.y *= ratio; p.w *= ratio; p.h *= ratio; });
            State.textLabels.forEach(t => { t.x *= ratio; t.y *= ratio; });
        }

        // ─── JSON ────────────────────────────────────────────────────────────────────
        function saveJSON() {
            const snap = State.snapshot();
            const safeProps = snap.props.map(p => {
                if (p.type === 'image') { try { const c = document.createElement('canvas'); c.width = p.content.width; c.height = p.content.height; c.getContext('2d').drawImage(p.content, 0, 0); return { ...p, content: c.toDataURL() }; } catch { return null; } }
                return p;
            }).filter(Boolean);
            const data = JSON.stringify({ CELL, panX, panY, scale, ...snap, props: safeProps }, null, 2);
            const a = document.createElement('a');
            a.href = 'data:application/json;charset=utf-8,' + encodeURIComponent(data);
            a.download = 'mapa_table.json'; a.click();
            showToast('\u2705 Projeto salvo!');
        }
        function loadJSON() {
            const input = document.createElement('input'); input.type = 'file'; input.accept = '.json';
            input.onchange = e => {
                const file = e.target.files[0]; if (!file) return;
                const reader = new FileReader();
                reader.onload = ev => {
                    try { const data = JSON.parse(ev.target.result); History.save(); applyLoadedData(data); showToast('\ud83d\udcc2 Carregado!'); }
                    catch { showToast('\u274c JSON inv\u00e1lido!', true); }
                };
                reader.readAsText(file);
            };
            input.click();
        }
        function applyLoadedData(data) {
            if (data.CELL) CELL = data.CELL;
            if (data.panX != null) panX = data.panX;
            if (data.panY != null) panY = data.panY;
            if (data.scale) scale = data.scale;
            document.getElementById('cellSlider').value = CELL;
            document.getElementById('cellVal').textContent = CELL + 'px';
            const rawProps = (data.props || []).map(p => {
                if (p.type === 'image' && typeof p.content === 'string') { const img = new Image(); img.src = p.content; return { ...p, content: img }; }
                return p;
            });
            State.rooms = data.rooms || [];
            State.internalWalls = data.internalWalls || [];
            State.props = rawProps;
            // Converter terrenos legados (objeto simples) em listas (arrays)
            if (data.terrainCells) {
                const updatedCells = {};
                for (const k in data.terrainCells) {
                    const cell = data.terrainCells[k];
                    updatedCells[k] = Array.isArray(cell) ? cell : [cell];
                }
                State.terrainCells = updatedCells;
            } else {
                State.terrainCells = {};
            }

            State.textLabels = data.textLabels || [];
            selectedItem = null; multiSelection = [];
            loadInputsFromSelection();
        }

        // ─── ESTILOS ─────────────────────────────────────────────────────────────────
        const DUNGEON_DEFAULTS = {
            solid: { wall: '#2a2030', floor: '#15101f' },
            cavern: { wall: '#1a1520', floor: '#0e0b14' },
            temple: { wall: '#5c4a1e', floor: '#2a1f0a' },
            crypt: { wall: '#1f2a1f', floor: '#0a130a' },
            fortress: { wall: '#3d3528', floor: '#1a150e' },
            prison: { wall: '#2a2020', floor: '#0f0808' },
            crystal: { wall: '#1a1a3a', floor: '#0a0a20' },
        };

        function setStyle(newStyle, defaultColor) {
            document.querySelectorAll('.style-btn').forEach(b => b.classList.remove('active'));
            const btn = document.getElementById('style-' + newStyle); if (btn) btn.classList.add('active');
            globals.style = newStyle; globals.fillColor = defaultColor;
            document.getElementById('fillColor').value = defaultColor;
            if (DUNGEON_DEFAULTS[newStyle]) {
                globals.wallColor = DUNGEON_DEFAULTS[newStyle].wall;
                globals.floorColor = DUNGEON_DEFAULTS[newStyle].floor;
                document.getElementById('wallColor').value = globals.wallColor;
                document.getElementById('floorColor').value = globals.floorColor;
            }
            if (selectedItem && selectedItem.type === 'room') {
                History.save();
                selectedItem.obj.style = newStyle; selectedItem.obj.fillColor = defaultColor;
                if (DUNGEON_DEFAULTS[newStyle]) { selectedItem.obj.wallColor = DUNGEON_DEFAULTS[newStyle].wall; selectedItem.obj.floorColor = DUNGEON_DEFAULTS[newStyle].floor; }
            }
            loadInputsFromSelection();
            const p = document.getElementById('stylePreview');
            if (p) { p.style.background = defaultColor; p.classList.add('flash'); setTimeout(() => p.classList.remove('flash'), 400); }
        }

        function updateProp(propName, value) {
            if (selectedItem) {
                if (selectedItem.type === 'room') selectedItem.obj[propName] = value;
                else if (selectedItem.type === 'wall' && (propName === 'wallColor' || propName === 'wallWidth')) selectedItem.obj[propName] = value;
                else if (selectedItem.type === 'text' && propName === 'fillColor') selectedItem.obj.color = value;
            } else {
                globals[propName] = value;
            }
            AutoSave.scheduleSave();
        }

        function loadInputsFromSelection() {
            let src = globals;
            if (selectedItem && selectedItem.type === 'room') src = selectedItem.obj;
            else if (selectedItem && selectedItem.type === 'wall') src = selectedItem.obj;
            document.getElementById('fillColor').value = src.fillColor || globals.fillColor;
            document.getElementById('wallColor').value = src.wallColor || globals.wallColor;
            document.getElementById('wallWidth').value = src.wallWidth || globals.wallWidth;
            document.getElementById('floorColor').value = src.floorColor || globals.floorColor;
            const oe = document.getElementById('opacitySlider');
            const ov = document.getElementById('opacityVal');
            const opVal = Math.round((src.opacity ?? 1) * 100);
            if (oe) oe.value = opVal; if (ov) ov.textContent = opVal + '%';
            document.querySelectorAll('.style-btn').forEach(b => b.classList.remove('active'));
            if (src.style) { const btn = document.getElementById('style-' + src.style); if (btn) btn.classList.add('active'); }
            const label = document.getElementById('editModeLabel');
            const btnMagic = document.getElementById('btnRemoveBg');
            if (btnMagic) btnMagic.style.display = 'none';
            if (selectedItem && selectedItem.type === 'room') { label.innerHTML = '🟢 Editando: Sala'; label.className = 'edit-status room'; }
            else if (selectedItem && selectedItem.type === 'prop') { label.innerHTML = '🟡 Editando: Objeto'; label.className = 'edit-status prop'; if (selectedItem.obj.type === 'image' && btnMagic) btnMagic.style.display = 'block'; }
            else if (selectedItem && selectedItem.type === 'wall') { label.innerHTML = '🟣 Editando: Muro'; label.className = 'edit-status wall'; }
            else if (selectedItem && selectedItem.type === 'text') {
                label.innerHTML = '🔤 Editando: Texto'; label.className = 'edit-status prop';
                const t = selectedItem.obj;
                const ti = document.getElementById('textInput');
                const ts = document.getElementById('textSize');
                const tc2 = document.getElementById('textColor');
                const tb = document.getElementById('textBold');
                if (ti) ti.value = t.content || '';
                if (ts) ts.value = t.fontSize || 18;
                if (tc2) tc2.value = t.color || '#ffffff';
                if (tb) tb.checked = t.bold || false;
                const btnT = document.getElementById('btnAddText');
                if (btnT) { btnT.innerHTML = '<i class="fas fa-pen"></i> Atualizar Texto'; btnT.style.background = '#7c3aed'; }
            }
            else if (multiSelection.length > 0) { label.innerHTML = `🔵 ${multiSelection.length} selecionados`; label.className = 'edit-status room'; }
            else {
                label.innerHTML = '🔵 Ferramenta Livre'; label.className = 'edit-status free';
                const btnT = document.getElementById('btnAddText');
                if (btnT) { btnT.innerHTML = '<i class="fas fa-plus"></i> Adicionar ao Mapa'; btnT.style.background = 'var(--accent)'; }
            }
        }

        function updateOpacity(val) {
            const v = parseInt(val) / 100;
            document.getElementById('opacityVal').textContent = val + '%';
            if (selectedItem) { selectedItem.obj.opacity = v; }
            else { globals.opacity = v; }
            AutoSave.scheduleSave();
        }

        // ─── UPLOAD ─────────────────────────────────────────────────────────────────
        document.getElementById('imageUploader').addEventListener('change', function (e) {
            const file = e.target.files[0]; if (!file) return;
            const reader = new FileReader();
            reader.onload = ev => {
                const img = new Image();
                img.onload = () => {
                    History.save();
                    const aspect = img.width / img.height, h = CELL * 2, w = h * aspect;
                    const center = { x: (width / 2 - panX) / scale, y: (height / 2 - panY) / scale };
                    const p = { type: 'image', content: img, x: center.x - w / 2, y: center.y - h / 2, w, h, opacity: 1, id: Date.now() };
                    State.props.push(p); selectedItem = null; loadInputsFromSelection(); AutoSave.scheduleSave();
                };
                img.src = ev.target.result;
            };
            reader.readAsDataURL(file); e.target.value = '';
        });

        function removeBackgroundFromSelected() {
            if (!selectedItem || selectedItem.type !== 'prop' || selectedItem.obj.type !== 'image') return;
            History.save();
            const img = selectedItem.obj.content;
            const tC = document.createElement('canvas'); tC.width = img.width; tC.height = img.height;
            const tX = tC.getContext('2d'); tX.drawImage(img, 0, 0);
            const d2 = tX.getImageData(0, 0, tC.width, tC.height); const d = d2.data;
            const r0 = d[0], g0 = d[1], b0 = d[2];
            for (let i = 0; i < d.length; i += 4) { if (Math.abs(d[i] - r0) <= 35 && Math.abs(d[i + 1] - g0) <= 35 && Math.abs(d[i + 2] - b0) <= 35) d[i + 3] = 0; }
            tX.putImageData(d2, 0, 0);
            const ni = new Image(); ni.onload = () => { selectedItem.obj.content = ni; }; ni.src = tC.toDataURL();
        }

        function addCustomEmoji() {
            const input = document.getElementById('customEmojiInput');
            if (!input) return;
            // Pegar o primeiro emoji/caractere do campo (ignora texto puro)
            const raw = input.value;
            if (!raw.trim()) { showToast('⚠️ Cole ou selecione um emoji primeiro!', 'error'); return; }
            // Usar o valor como está (suporte a emojis compostos multi-codepoint)
            addEmojiProp(raw.trim());
            input.value = '';
            input.focus();
            showToast('✅ Emoji adicionado ao mapa!', 'success');
        }

        // Lista de emojis do picker rápido
        const EMOJI_PICKER_LIST = [
            // Ação/combate
            '⚔️', '🗡️', '🛡️', '🏹', '🪃', '🔱', '⚡', '🔥', '💥', '❄️', '🌊', '🌀',
            // Personagens
            '🧙', '🧝', '🧛', '🧟', '🧜', '🦸', '🦹', '🗣️', '👤', '👥', '🧑‍🤝‍🧑', '👿',
            // Criaturas
            '🐉', '🦄', '🐺', '🦅', '🐍', '🦂', '🕷️', '🐗', '🦁', '🐻', '🦊', '🐲',
            // Itens
            '📦', '🗝️', '💎', '🏆', '📜', '📖', '🧪', '⚗️', '🔮', '🪄', '💰', '🪙',
            // Estruturas
            '🏰', '⛩️', '🕍', '🏯', '⛪', '🗼', '🗽', '🏛️', '🌁', '🌉', '🗺️', '🧭',
            // Natureza
            '🌲', '🌳', '🌴', '🍄', '🌾', '🌿', '🍀', '🌵', '⛰️', '🌋', '🏔️', '🗻',
            // Perigo/masmorra
            '💀', '☠️', '👻', '🕯️', '🪔', '🔦', '💣', '🪤', '⛓️', '🔒', '🚪', '🪦',
            // Clima/magia
            '🌙', '⭐', '✨', '🌟', '💫', '🌈', '🌩️', '🌪️', '🌫️', '☁️', '🌑', '🔵',
            // Misc RPG
            '🎲', '🎯', '🎭', '🎪', '🎠', '🏟️', '⚙️', '🔧', '⚖️', '🧲', '💡', '🔑',
        ];

        function initEmojiPicker() {
            const grid = document.getElementById('emojiPickerGrid');
            if (!grid) return;
            grid.innerHTML = '';
            EMOJI_PICKER_LIST.forEach(em => {
                const btn = document.createElement('button');
                btn.textContent = em;
                btn.title = 'Inserir ' + em + ' no campo';
                btn.style.cssText = 'background:rgba(255,255,255,0.04);border:1px solid transparent;border-radius:5px;cursor:pointer;font-size:1.1rem;padding:3px 2px;transition:all 0.15s;line-height:1.3;';
                btn.onmouseover = () => { btn.style.background = 'rgba(139,92,246,0.25)'; btn.style.borderColor = 'rgba(139,92,246,0.5)'; btn.style.transform = 'scale(1.2)'; };
                btn.onmouseout = () => { btn.style.background = 'rgba(255,255,255,0.04)'; btn.style.borderColor = 'transparent'; btn.style.transform = ''; };
                btn.onclick = () => {
                    const inp = document.getElementById('customEmojiInput');
                    if (inp) { inp.value = em; inp.focus(); }
                };
                grid.appendChild(btn);
            });
        }

        function addEmojiProp(emoji) {
            History.save();
            const center = { x: (width / 2 - panX) / scale, y: (height / 2 - panY) / scale };
            const p = { type: 'emoji', content: emoji, x: center.x - CELL / 2, y: center.y - CELL / 2, w: CELL, h: CELL, opacity: 1, id: Date.now() };
            State.props.push(p); selectedItem = null; loadInputsFromSelection(); AutoSave.scheduleSave();
        }

        function addTextLabel() {
            const ti = document.getElementById('textInput');
            const text = ti ? ti.value.trim() : '';
            if (!text) { showToast('⚠️ Digite algo!', true); return; }
            History.save();
            // Se já há texto selecionado, EDITAR em vez de criar novo
            if (selectedItem && selectedItem.type === 'text') {
                const t = selectedItem.obj;
                t.content = text;
                t.fontSize = parseInt(document.getElementById('textSize')?.value || 18);
                t.color = document.getElementById('textColor')?.value || '#ffffff';
                t.bold = document.getElementById('textBold')?.checked || false;
                showToast('✏️ Texto atualizado!', 'success');
                AutoSave.scheduleSave(); return;
            }
            const center = { x: (width / 2 - panX) / scale, y: (height / 2 - panY) / scale };
            const t = {
                type: 'label', content: text, x: center.x, y: center.y,
                fontSize: parseInt(document.getElementById('textSize')?.value || 18),
                color: document.getElementById('textColor')?.value || '#ffffff',
                bold: document.getElementById('textBold')?.checked || false,
                opacity: 1, id: Date.now()
            };
            State.textLabels.push(t); selectedItem = null; loadInputsFromSelection();
            AutoSave.scheduleSave(); if (ti) ti.value = '';
            showToast('✅ Texto adicionado!', 'success');
        }

        // ─── RENOMEAR ────────────────────────────────────────────────────────────────
        function startRenameRoom(room, clientX, clientY) {
            closeRenameInput();
            const inp = document.createElement('input');
            inp.type = 'text'; inp.value = room.label || ''; inp.placeholder = 'Nome da sala...';
            inp.id = 'renameInput';
            const rect = canvas.getBoundingClientRect();
            inp.style.left = `${clientX - 90}px`;
            inp.style.top = `${clientY - 20}px`;
            document.body.appendChild(inp); inp.focus(); inp.select();
            inp.addEventListener('keydown', ev => {
                if (ev.key === 'Enter') { room.label = inp.value.trim(); closeRenameInput(); AutoSave.scheduleSave(); }
                if (ev.key === 'Escape') closeRenameInput();
                ev.stopPropagation();
            });
            inp.addEventListener('blur', () => { room.label = inp.value.trim(); closeRenameInput(); AutoSave.scheduleSave(); });
        }
        function closeRenameInput() { const e = document.getElementById('renameInput'); if (e) e.remove(); }

        // ─── DUPLICAR / ORDEM ────────────────────────────────────────────────────────
        function duplicateSelected() {
            if (!selectedItem) return;
            History.save(); const off = CELL;
            if (selectedItem.type === 'room') { const r = selectedItem.obj; const nr = { ...r, x: r.x + off, y: r.y + off, id: Date.now() }; State.rooms.push(nr); selectItem('room', nr); }
            else if (selectedItem.type === 'prop') { const p = selectedItem.obj; const np = { ...p, x: p.x + off, y: p.y + off, id: Date.now() }; State.props.push(np); selectItem('prop', np); }
            else if (selectedItem.type === 'wall') { const w = selectedItem.obj; const nw = { ...w, x1: w.x1 + off, y1: w.y1 + off, x2: w.x2 + off, y2: w.y2 + off }; State.internalWalls.push(nw); selectItem('wall', nw); }
            else if (selectedItem.type === 'text') { const t = selectedItem.obj; const nt = { ...t, x: t.x + off, y: t.y + off, id: Date.now() }; State.textLabels.push(nt); selectItem('text', nt); }
            showToast('\ud83d\udccb Duplicado!'); AutoSave.scheduleSave();
        }
        function bringForward() {
            if (!selectedItem) return; History.save();
            if (selectedItem.type === 'room') { const i = State.rooms.indexOf(selectedItem.obj); if (i < State.rooms.length - 1) [State.rooms[i], State.rooms[i + 1]] = [State.rooms[i + 1], State.rooms[i]]; }
            if (selectedItem.type === 'prop') { const i = State.props.indexOf(selectedItem.obj); if (i < State.props.length - 1) [State.props[i], State.props[i + 1]] = [State.props[i + 1], State.props[i]]; }
        }
        function sendBackward() {
            if (!selectedItem) return; History.save();
            if (selectedItem.type === 'room') { const i = State.rooms.indexOf(selectedItem.obj); if (i > 0) [State.rooms[i], State.rooms[i - 1]] = [State.rooms[i - 1], State.rooms[i]]; }
            if (selectedItem.type === 'prop') { const i = State.props.indexOf(selectedItem.obj); if (i > 0) [State.props[i], State.props[i - 1]] = [State.props[i - 1], State.props[i]]; }
        }

        // ─── MENU CONTEXTO ───────────────────────────────────────────────────────────
        function showContextMenu(e, item) {
            closeContextMenu(); e.preventDefault();
            const menu = document.createElement('div');
            menu.id = 'contextMenu'; menu.className = 'context-menu';
            const acts = [
                { icon: 'fa-copy', label: 'Duplicar', fn: () => { selectItem(item.type, item.obj); duplicateSelected(); } },
                { icon: 'fa-arrow-up', label: 'Trazer para Frente', fn: () => { selectItem(item.type, item.obj); bringForward(); } },
                { icon: 'fa-arrow-down', label: 'Mandar para Tr\u00e1s', fn: () => { selectItem(item.type, item.obj); sendBackward(); } },
            ];
            if (item.type === 'room') acts.push({ icon: 'fa-tag', label: 'Renomear Sala', fn: () => startRenameRoom(item.obj, e.clientX, e.clientY) });
            if (item.type === 'text') acts.push({ icon: 'fa-pen', label: 'Editar Texto', fn: () => { const nc = prompt('Editar texto:', item.obj.content); if (nc !== null) { History.save(); item.obj.content = nc.trim() || item.obj.content; } } });
            acts.push({
                icon: 'fa-trash', label: 'Apagar', cls: 'danger', fn: () => {
                    History.save();
                    if (item.type === 'room') State.rooms = State.rooms.filter(r => r !== item.obj);
                    if (item.type === 'prop') State.props = State.props.filter(p => p !== item.obj);
                    if (item.type === 'wall') State.internalWalls = State.internalWalls.filter(w => w !== item.obj);
                    if (item.type === 'text') State.textLabels = State.textLabels.filter(t => t !== item.obj);
                    selectedItem = null; loadInputsFromSelection(); AutoSave.scheduleSave();
                }
            });
            acts.forEach(a => {
                const btn = document.createElement('button');
                btn.className = 'ctx-item' + (a.cls ? ' ' + a.cls : '');
                btn.innerHTML = `<i class="fas ${a.icon}"></i><span>${a.label}</span>`;
                btn.onclick = () => { a.fn(); closeContextMenu(); };
                menu.appendChild(btn);
            });
            menu.style.left = `${e.clientX}px`;
            menu.style.top = `${e.clientY}px`;
            document.body.appendChild(menu);
            requestAnimationFrame(() => {
                const r = menu.getBoundingClientRect();
                if (r.right > window.innerWidth) menu.style.left = (e.clientX - r.width) + 'px';
                if (r.bottom > window.innerHeight) menu.style.top = (e.clientY - r.height) + 'px';
            });
            setTimeout(() => document.addEventListener('click', closeContextMenu, { once: true }), 10);
        }
        function closeContextMenu() { const m = document.getElementById('contextMenu'); if (m) m.remove(); }

        // ─── TERRENOS ────────────────────────────────────────────────────────────────
        function paintTerrain(x, y) {
            if (State.isLayerLocked('terrain')) return; // TRAVA DE CAMADA
            const pc = CELL / 2, c = Math.floor(x / pc), r = Math.floor(y / pc), key = `${c},${r}`;
            const newLayer = { style: globals.style, color: globals.fillColor };
            if (!State.terrainCells[key]) State.terrainCells[key] = [];
            const currentStack = State.terrainCells[key];
            const last = currentStack[currentStack.length - 1];
            if (last && last.style === newLayer.style && last.color === newLayer.color) return;
            currentStack.push(newLayer);
            AutoSave.scheduleSave();
        }
        function eraseTerrain(x, y) {
            if (State.isLayerLocked('terrain')) return; // TRAVA DE CAMADA
            const pc = CELL / 2, c = Math.floor(x / pc), r = Math.floor(y / pc);
            if (State.terrainCells[`${c},${r}`]) {
                delete State.terrainCells[`${c},${r}`];
                AutoSave.scheduleSave();
            }
        }


        // ─── UTILITÁRIOS ─────────────────────────────────────────────────────────────
        function distToSegSq(p, v, w) { const l2 = (v.x - w.x) ** 2 + (v.y - w.y) ** 2; if (l2 === 0) return (p.x - v.x) ** 2 + (p.y - v.y) ** 2; const t = ((p.x - v.x) * (w.x - v.x) + (p.y - v.y) * (w.y - v.y)) / l2; return (p.x - (v.x + Math.max(0, Math.min(1, t)) * (w.x - v.x))) ** 2 + (p.y - (v.y + Math.max(0, Math.min(1, t)) * (w.y - v.y))) ** 2; }
        function isLightColor(hex) { const h = hex.replace('#', ''); return ((parseInt(h.substr(0, 2), 16) * 299) + (parseInt(h.substr(2, 2), 16) * 587) + (parseInt(h.substr(4, 2), 16) * 114)) / 1000 > 150; }
        function adj(color, amount) {
            // Strip to 6 hex chars only — ignore any alpha suffix
            let h = (color || '#808080').replace(/^#/, '').slice(0, 6);
            if (h.length < 6) h = h.padEnd(6, '0');
            return '#' + h.replace(/../g, c => {
                const v = parseInt(c, 16);
                if (isNaN(v)) return '80';
                return ('0' + Math.min(255, Math.max(0, v + amount)).toString(16)).substr(-2);
            });
        }
        function hitRoom(r, x, y) { const p = 15; return x >= r.x - p && x <= r.x + r.w + p && y >= r.y - p && y <= r.y + r.h + p; }
        function hitText(t, x, y) { const fs = t.fontSize || 14; return x >= t.x - fs && x <= t.x + 350 && y >= t.y - fs && y <= t.y + fs; }
        function getItemAtPos(rx, ry) {
            // Ordem reversa para pegar o que está visível no topo
            const layers = State.layers.slice().reverse();
            for (const l of layers) {
                if (!l.visible || l.locked) continue;
                if (l.id === 'text' && State.textLabels.slice().reverse().find(t => hitText(t, rx, ry))) return true;
                if (l.id === 'props' && State.props.slice().reverse().find(p => rx >= p.x && rx <= p.x + p.w && ry >= p.y && ry <= p.y + p.h)) return true;
                if (l.id === 'walls' && State.internalWalls.slice().reverse().find(w => Math.sqrt(distToSegSq({ x: rx, y: ry }, { x: w.x1, y: w.y1 }, { x: w.x2, y: w.y2 })) <= 12 / scale)) return true;
                if (l.id === 'rooms' && State.rooms.slice().reverse().find(r => hitRoom(r, rx, ry))) return true;
            }
            return false;
        }
        function getMousePos(e) {
            const rect = canvas.getBoundingClientRect();
            const mx = e.clientX - rect.left, my = e.clientY - rect.top;
            const rx = (mx - panX) / scale, ry = (my - panY) / scale;
            const sx = snapToGrid ? Math.round(rx / CELL) * CELL : rx;
            const sy = snapToGrid ? Math.round(ry / CELL) * CELL : ry;
            return { x: sx, y: sy, rawX: rx, rawY: ry, screenX: mx, screenY: my };
        }
        function selectItem(type, obj) { selectedItem = { type, obj }; multiSelection = []; loadInputsFromSelection(); }

        // ─── RENDERIZAÇÃO ────────────────────────────────────────────────────────────
        function drawDimTooltip(c, x, y, w, h) {
            const text = `${Math.round(w / CELL)} x ${Math.round(h / CELL)}`;
            c.save();
            c.font = 'bold 12px "Montserrat", sans-serif';
            const tw = c.measureText(text).width;
            const padding = 10;

            // Sombra e Vidro
            c.shadowColor = 'rgba(0,0,0,0.4)';
            c.shadowBlur = 12;
            c.fillStyle = 'rgba(15, 10, 30, 0.85)';
            c.beginPath();
            c.roundRect(x, y - 30, tw + (padding * 2), 26, 6);
            c.fill();

            // Borda Neon
            c.strokeStyle = 'rgba(139, 92, 246, 0.5)';
            c.lineWidth = 1;
            c.stroke();

            // Texto
            c.shadowBlur = 4;
            c.shadowColor = '#4ade80';
            c.fillStyle = '#4ade80';
            c.textAlign = 'left';
            c.textBaseline = 'middle';
            c.fillText(text, x + padding, y - 17);
            c.restore();
        }

        function render() {
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#15101f';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            ctx.save();
            ctx.translate(panX, panY);
            ctx.scale(scale, scale);

            // Destaque da célula sob o mouse (Preview de HUD)
            if (!presentationMode && (currentTool === 'room' || currentTool === 'wall') && window._mousePos) {
                ctx.fillStyle = 'rgba(139, 92, 246, 0.15)';
                const m = window._mousePos;
                ctx.fillRect(m.x - 2, m.y - 2, CELL + 4, CELL + 4);
                ctx.strokeStyle = 'rgba(139, 92, 246, 0.4)';
                ctx.setLineDash([4, 2]);
                ctx.strokeRect(m.x, m.y, CELL, CELL);
                ctx.setLineDash([]);
            }

            renderMap(ctx, false);

            // Efeitos de Desenho Premium (Brilho e HUD)
            if (isDrawing) {
                if (currentTool === 'room' && currentRoom) {
                    ctx.shadowColor = 'rgba(74, 222, 128, 0.5)';
                    ctx.shadowBlur = 15;
                    ctx.strokeStyle = '#4ade80';
                    ctx.setLineDash([8, 4]);
                    ctx.strokeRect(currentRoom.x, currentRoom.y, currentRoom.w, currentRoom.h);
                    ctx.restore(); // restore global scale for tooltip
                    ctx.save();
                    const tx = panX + (currentRoom.x + currentRoom.w) * scale;
                    const ty = panY + (currentRoom.y + currentRoom.h) * scale;
                    drawDimTooltip(ctx, tx + 10, ty + 10, currentRoom.w, currentRoom.h);
                } else if (currentTool === 'wall' && currentWall) {
                    ctx.shadowColor = 'rgba(139, 92, 246, 0.6)';
                    ctx.shadowBlur = 10;
                    ctx.strokeStyle = '#a78bfa';
                    ctx.setLineDash([5, 5]);
                    ctx.beginPath();
                    ctx.moveTo(currentWall.x1, currentWall.y1);
                    ctx.lineTo(currentWall.x2, currentWall.y2);
                    ctx.stroke();
                }
            }
            ctx.restore();

            // Névoa de guerra
            if (fogEnabled) renderFog(ctx);
            if (document.getElementById('toggleRuler')?.classList.contains('active')) drawRuler();

            if (selectionRect) {
                ctx.save();
                const sr = selectionRect;
                const sx = panX + sr.x * scale, sy = panY + sr.y * scale, sw = sr.w * scale, sh = sr.h * scale;
                ctx.strokeStyle = '#60a5fa'; ctx.lineWidth = 2;
                ctx.shadowColor = 'rgba(96, 165, 250, 0.5)'; ctx.shadowBlur = 10;
                ctx.setLineDash([6, 4]);
                ctx.fillStyle = 'rgba(96,165,250,0.12)';
                ctx.fillRect(sx, sy, sw, sh);
                ctx.strokeRect(sx, sy, sw, sh);
                ctx.restore();
            }
        }

        function renderMap(ctxD, isExport = false) {
            if (!isExport) {
                if (showGridLines) {
                    ctxD.strokeStyle = 'rgba(139,92,246,0.1)'; ctxD.lineWidth = 0.5;
                    const sx = Math.floor(-panX / scale / CELL) * CELL, sy = Math.floor(-panY / scale / CELL) * CELL;
                    for (let x = sx; x < sx + (width / scale) + CELL; x += CELL) { ctxD.beginPath(); ctxD.moveTo(x, -50000); ctxD.lineTo(x, 50000); ctxD.stroke(); }
                    for (let y = sy; y < sy + (height / scale) + CELL; y += CELL) { ctxD.beginPath(); ctxD.moveTo(-50000, y); ctxD.lineTo(50000, y); ctxD.stroke(); }
                } else {
                    ctxD.fillStyle = 'rgba(139,92,246,0.18)';
                    const sx = Math.floor(-panX / scale / CELL) * CELL, sy = Math.floor(-panY / scale / CELL) * CELL;
                    for (let x = sx; x < sx + (width / scale) + CELL; x += CELL)
                        for (let y = sy; y < sy + (height / scale) + CELL; y += CELL) { ctxD.beginPath(); ctxD.arc(x, y, 1.5, 0, Math.PI * 2); ctxD.fill(); }
                }
            }

            // RENDERIZAÇÃO DINÂMICA POR CAMADAS
            State.layers.forEach(layer => {
                if (!layer.visible) return;

                switch (layer.id) {
                    case 'terrain':
                        renderTerrains(ctxD);
                        break;
                    case 'rooms':
                        // Sub-passos das salas (Pisos, depois Sombras, depois Labels, depois Paredes)
                        const allR = State.rooms.slice(); if (currentRoom) allR.push(currentRoom);

                        // 1. Pisos
                        allR.forEach(r => {
                            ctxD.save();
                            ctxD.globalAlpha = (r.opacity ?? 1);
                            ctxD.fillStyle = r.floorColor; ctxD.fillRect(r.x, r.y, r.w, r.h);
                            ctxD.beginPath(); ctxD.rect(r.x, r.y, r.w, r.h); ctxD.clip();
                            ctxD.strokeStyle = isLightColor(r.floorColor) ? 'rgba(0,0,0,0.1)' : 'rgba(255,255,255,0.05)';
                            ctxD.lineWidth = 1;
                            const sX = Math.floor(r.x / CELL) * CELL, sY = Math.floor(r.y / CELL) * CELL;
                            for (let x = sX; x <= r.x + r.w; x += CELL) { ctxD.beginPath(); ctxD.moveTo(x, r.y); ctxD.lineTo(x, r.y + r.h); ctxD.stroke(); }
                            for (let y = sY; y <= r.y + r.h; y += CELL) { ctxD.beginPath(); ctxD.moveTo(r.x, y); ctxD.lineTo(r.x + r.w, y); ctxD.stroke(); }
                            ctxD.restore();
                        });

                        // 2. Sombras/Outer
                        allR.forEach(r => { ctxD.save(); ctxD.globalAlpha = r.opacity ?? 1; drawRoomOuter(ctxD, r); ctxD.restore(); });

                        // 3. Labels
                        allR.forEach(r => {
                            if (r.label && showRoomLabels) {
                                ctxD.save();
                                ctxD.beginPath(); ctxD.rect(r.x + 3, r.y + 3, r.w - 6, r.h - 6); ctxD.clip();
                                const _maxW = r.w - 14, _maxH = r.h - 8;
                                ctxD.textAlign = 'center'; ctxD.textBaseline = 'middle';
                                let _fs = Math.min(17, Math.max(7, Math.floor(r.h * 0.22)));
                                for (let _t = 0; _t < 14; _t++) {
                                    ctxD.font = 'bold ' + _fs + 'px Cinzel,Georgia,serif';
                                    if (ctxD.measureText(r.label).width <= _maxW || _fs <= 7) break;
                                    _fs = Math.max(7, _fs - 1);
                                }
                                const _words = r.label.split(' '), _lines = [];
                                if (ctxD.measureText(r.label).width > _maxW && _words.length > 1) {
                                    let _cur = '';
                                    for (const _w of _words) {
                                        const _test = _cur ? _cur + ' ' + _w : _w;
                                        if (ctxD.measureText(_test).width > _maxW && _cur) { _lines.push(_cur); _cur = _w; }
                                        else { _cur = _test; }
                                    }
                                    if (_cur) _lines.push(_cur);
                                } else { _lines.push(r.label); }
                                const _lh = _fs * 1.28, _totalH = _lines.length * _lh;
                                if (_totalH > _maxH && _fs > 7) {
                                    _fs = Math.max(7, Math.floor(_fs * (_maxH / _totalH)));
                                    ctxD.font = 'bold ' + _fs + 'px Cinzel,Georgia,serif';
                                }
                                const _cx = r.x + r.w / 2, _startY = r.y + _lh * 0.5 + 5;
                                const _bgW = Math.min(r.w - 6, _lines.reduce((mx, l) => Math.max(mx, ctxD.measureText(l).width), 0) + 14);
                                const _bgH = _lines.length * _lh + 7;
                                ctxD.fillStyle = 'rgba(0,0,0,0.5)';
                                ctxD.beginPath(); ctxD.roundRect(_cx - _bgW / 2, r.y + 3, _bgW, _bgH, Math.min(6, _fs * 0.4)); ctxD.fill();
                                ctxD.fillStyle = 'rgba(255,255,255,0.95)'; ctxD.shadowColor = 'rgba(0,0,0,0.95)'; ctxD.shadowBlur = 4;
                                _lines.forEach((_line, _i) => { ctxD.fillText(_line, _cx, _startY + _i * _lh); });
                                ctxD.restore();
                            }
                        });

                        // 4. Paredes
                        allR.forEach(r => {
                            if (r.wallWidth > 0) {
                                ctxD.save(); ctxD.globalAlpha = r.opacity ?? 1;
                                ctxD.strokeStyle = r.wallColor; ctxD.lineWidth = r.wallWidth;
                                ctxD.lineJoin = 'miter'; ctxD.lineCap = 'square';
                                ctxD.strokeRect(r.x, r.y, r.w, r.h);
                                drawRoomDetail(ctxD, r);
                                ctxD.restore();
                            }
                        });
                        break;
                    case 'walls':
                        const allW = State.internalWalls.slice(); if (currentWall) allW.push(currentWall);
                        allW.forEach(w => { ctxD.strokeStyle = w.wallColor; ctxD.lineWidth = w.wallWidth; ctxD.lineCap = 'round'; ctxD.beginPath(); ctxD.moveTo(w.x1, w.y1); ctxD.lineTo(w.x2, w.y2); ctxD.stroke(); });
                        break;
                    case 'props':
                        State.props.forEach(p => {
                            ctxD.save(); ctxD.globalAlpha = p.opacity ?? 1;
                            if (p.type === 'image') { ctxD.drawImage(p.content, p.x, p.y, p.w, p.h); }
                            else { const ec = getEmojiCanvas(p.content, 128); ctxD.drawImage(ec, p.x, p.y, p.w, p.h); }
                            ctxD.restore();
                        });
                        break;
                    case 'text':
                        State.textLabels.forEach(t => {
                            ctxD.save(); ctxD.globalAlpha = t.opacity ?? 1;
                            ctxD.font = `${t.bold ? 'bold ' : ''} ${t.fontSize}px 'Cinzel', Georgia, serif`;
                            ctxD.fillStyle = t.color; ctxD.textAlign = 'left'; ctxD.textBaseline = 'middle';
                            ctxD.shadowColor = 'rgba(0,0,0,0.9)'; ctxD.shadowBlur = 8;
                            ctxD.fillText(t.content, t.x, t.y); ctxD.shadowColor = 'transparent'; ctxD.shadowBlur = 0; ctxD.restore();
                        });
                        break;
                }
            });

            if (!isExport) {
                multiSelection.forEach(sel => {
                    const o = sel.obj; ctxD.save();
                    ctxD.strokeStyle = 'rgba(96,165,250,0.8)'; ctxD.lineWidth = 2 / scale; ctxD.setLineDash([6, 4]);
                    if (sel.type === 'room' || sel.type === 'prop') ctxD.strokeRect(o.x, o.y, o.w, o.h);
                    ctxD.restore();
                });
                if (currentTool === 'select' && selectedItem) {
                    const o = selectedItem.obj; ctxD.strokeStyle = '#3b82f6'; ctxD.lineWidth = 2 / scale;
                    if (selectedItem.type === 'room') { ctxD.setLineDash([8, 6]); ctxD.strokeRect(o.x, o.y, o.w, o.h); ctxD.setLineDash([]); }
                    else if (selectedItem.type === 'wall') { ctxD.setLineDash([6, 4]); ctxD.beginPath(); ctxD.moveTo(o.x1, o.y1); ctxD.lineTo(o.x2, o.y2); ctxD.stroke(); ctxD.setLineDash([]); }
                    else if (selectedItem.type === 'text') { ctxD.setLineDash([4, 4]); ctxD.strokeRect(o.x - 4, o.y - o.fontSize * 0.7, 350, o.fontSize * 1.4); ctxD.setLineDash([]); }
                    else { ctxD.setLineDash([8, 6]); ctxD.strokeRect(o.x, o.y, o.w, o.h); ctxD.setLineDash([]); }
                    if (selectedItem.type !== 'wall' && selectedItem.type !== 'text') {
                        ctxD.fillStyle = '#fff';
                        [{ x: o.x, y: o.y }, { x: o.x + o.w, y: o.y }, { x: o.x, y: o.y + o.h }, { x: o.x + o.w, y: o.y + o.h }].forEach(c => { ctxD.beginPath(); ctxD.arc(c.x, c.y, 5 / scale, 0, Math.PI * 2); ctxD.fill(); ctxD.stroke(); });
                        if (isDrawing && dragAction.startsWith('resize')) drawDimTooltip(ctxD, o.x + o.w + 15, o.y + o.h + 15, o.w, o.h);
                    }
                }
                if (currentTool === 'room' && isDrawing && currentRoom) drawDimTooltip(ctxD, currentRoom.x + currentRoom.w + 15, currentRoom.y + currentRoom.h + 15, currentRoom.w, currentRoom.h);
            }
        }

        // ─── TERRENOS REDESENHADOS ───────────────────────────────────────────────────
        function renderTerrains(ctxD) {
            const t = animFrame; const pc = CELL / 2;
            for (const k in State.terrainCells) {
                const stack = State.terrainCells[k];
                if (!Array.isArray(stack)) continue;

                stack.forEach(tc => {
                    const [c, r] = k.split(',').map(Number);
                    const cx = c * pc + pc / 2, cy = r * pc + pc / 2, seed = Math.abs(c * 137 + r * 73) || 1;
                    ctxD.save();
                    try {
                        switch (tc.style) {
                            case 'water': drawWater(ctxD, cx, cy, pc, tc.color, t, seed); break;
                            case 'foliage': drawFoliage(ctxD, cx, cy, pc, tc.color, seed); break;
                            case 'lava': drawLava(ctxD, cx, cy, pc, tc.color, t, seed); break;
                            case 'road': drawRoad(ctxD, cx, cy, pc, tc.color, seed); break;
                            case 'sand': drawSand(ctxD, cx, cy, pc, tc.color, seed); break;
                            case 'snow': drawSnow(ctxD, cx, cy, pc, tc.color, seed); break;
                            case 'swamp': drawSwamp(ctxD, cx, cy, pc, tc.color, seed); break;
                            case 'darkforest': drawDarkForest(ctxD, cx, cy, pc, tc.color, seed, t); break;
                            case 'mud': drawMud(ctxD, cx, cy, pc, tc.color, seed); break;
                            case 'blood': drawBlood(ctxD, cx, cy, pc, tc.color, t, seed); break;
                            case 'ice': drawIce(ctxD, cx, cy, pc, tc.color, seed, t); break;
                            case 'mushroom': drawMushroom(ctxD, cx, cy, pc, tc.color, seed); break;
                            case 'void': drawVoid(ctxD, cx, cy, pc, tc.color, t, seed); break;
                            default: drawGeneric(ctxD, cx, cy, pc, tc.color, seed);
                        }
                    } catch (e) {
                        ctxD.fillStyle = tc.color;
                        ctxD.fillRect(cx - pc * 0.8, cy - pc * 0.8, pc * 1.6, pc * 1.6);
                    }
                    ctxD.restore();
                });
            }
        }


        function drawWater(c, cx, cy, pc, col, t, s) {
            const w = Math.sin(t * 0.03 + s * 0.5) * 2;
            // Base profunda
            const g = c.createRadialGradient(cx, cy, 2, cx, cy, pc * 1.1);
            g.addColorStop(0, adj(col, 20));
            g.addColorStop(0.7, col);
            g.addColorStop(1, 'transparent');
            c.fillStyle = g;
            c.beginPath(); c.arc(cx, cy + w, pc, 0, Math.PI * 2); c.fill();

            // Brilho de superfície (Ondas)
            c.strokeStyle = 'rgba(255,255,255,0.3)';
            c.lineWidth = 1;
            c.beginPath();
            c.moveTo(cx - pc * 0.5, cy + w - 4);
            c.bezierCurveTo(cx - pc * 0.2, cy + w - 8, cx + pc * 0.2, cy + w, cx + pc * 0.5, cy + w - 4);
            c.stroke();
        }

        function drawFoliage(c, cx, cy, pc, col, s) {
            const baseCol = adj(col, -20);
            c.fillStyle = baseCol;
            c.beginPath(); c.arc(cx, cy, pc, 0, Math.PI * 2); c.fill();

            // Pequenas "folhas" ou texturas orgânicas
            const light = adj(col, 30);
            for (let i = 0; i < 3; i++) {
                const ox = (Math.sin(s * i) * pc * 0.4);
                const oy = (Math.cos(s * i) * pc * 0.4);
                c.fillStyle = i % 2 === 0 ? col : light;
                c.beginPath();
                c.ellipse(cx + ox, cy + oy, pc * 0.5, pc * 0.3, s * i, 0, Math.PI * 2);
                c.fill();
            }
        }

        function drawLava(c, cx, cy, pc, col, t, s) {
            const heat = 0.8 + Math.sin(t * 0.05 + s) * 0.2;
            const g = c.createRadialGradient(cx, cy, 1, cx, cy, pc * 1.2);
            g.addColorStop(0, '#ffeb3b');
            g.addColorStop(0.2, '#ff9800');
            g.addColorStop(0.5, col);
            g.addColorStop(1, 'transparent');

            c.fillStyle = g;
            c.beginPath(); c.arc(cx, cy, pc * heat, 0, Math.PI * 2); c.fill();

            // Rachaduras térmicas
            c.strokeStyle = 'rgba(255, 200, 50, 0.4)';
            c.lineWidth = 2;
            c.beginPath();
            c.moveTo(cx - pc * 0.4, cy - pc * 0.4);
            c.lineTo(cx + pc * 0.4, cy + pc * 0.4);
            c.stroke();
        }

        function drawRoad(c, cx, cy, pc, col, s) {
            c.fillStyle = adj(col, -15); c.fillRect(cx - pc * 0.7, cy - pc * 0.7, pc * 1.4, pc * 1.4);
            c.fillStyle = 'rgba(0,0,0,0.2)';
            if (pc > 0) { for (let i = 0; i < 4; i++) { c.beginPath(); c.arc(cx + (s * i * 7) % pc - pc / 2, cy + (s * i * 13) % pc - pc / 2, 1.5, 0, Math.PI * 2); c.fill(); } }
            c.strokeStyle = adj(col, -35); c.lineWidth = 2; c.strokeRect(cx - pc * 0.7, cy - pc * 0.7, pc * 1.4, pc * 1.4);
        }
        function drawSand(c, cx, cy, pc, col, s) {
            const g = c.createLinearGradient(cx - pc, cy - pc, cx + pc, cy + pc);
            g.addColorStop(0, adj(col, 15));
            g.addColorStop(1, adj(col, -10));
            c.fillStyle = g;
            c.beginPath(); c.arc(cx, cy, pc, 0, Math.PI * 2); c.fill();

            // Dunas/Ondulações
            c.strokeStyle = 'rgba(0,0,0,0.08)';
            c.lineWidth = 1;
            c.beginPath();
            c.arc(cx, cy + pc, pc * 1.1, 3.5, 5.8);
            c.stroke();
        }

        function drawSnow(c, cx, cy, pc, col, s) {
            const g = c.createRadialGradient(cx - 2, cy - 2, 0, cx, cy, pc);
            g.addColorStop(0, '#fff');
            g.addColorStop(0.7, col);
            g.addColorStop(1, adj(col, -10));
            c.fillStyle = g;
            c.beginPath(); c.arc(cx, cy, pc, 0, Math.PI * 2); c.fill();

            // Pequenos flocos/irregularidade
            c.fillStyle = 'rgba(255,255,255,0.7)';
            for (let i = 0; i < 3; i++) {
                c.beginPath();
                c.arc(cx + (s * i % 6) - 3, cy + (s * i % 4) - 2, 1.5, 0, Math.PI * 2);
                c.fill();
            }
        }

        function drawSwamp(c, cx, cy, pc, col, s) {
            const dark = adj(col, -40);
            c.fillStyle = dark;
            c.beginPath(); c.arc(cx, cy, pc, 0, Math.PI * 2); c.fill();

            // Bolhas e lodo
            c.fillStyle = col;
            for (let i = 0; i < 2; i++) {
                c.beginPath();
                c.arc(cx + (s * i % 10) - 5, cy + (s * (i + 1) % 8) - 4, pc * 0.4, 0, Math.PI * 2);
                c.fill();
            }
        }
        function drawDarkForest(c, cx, cy, pc, col, s, t) {
            // Troncos e sombras
            c.fillStyle = '#100c0a';
            c.fillRect(cx - pc * 0.8, cy - pc * 0.8, pc * 1.6, pc * 1.6);

            const glow = 0.5 + Math.sin(t * 0.04 + s) * 0.3;
            const g = c.createRadialGradient(cx, cy, 0, cx, cy, pc);
            g.addColorStop(0, `rgba(${parseInt(col.slice(1, 3), 16)}, ${parseInt(col.slice(3, 5), 16)}, ${parseInt(col.slice(5, 7), 16)}, ${glow})`);
            g.addColorStop(1, 'transparent');
            c.fillStyle = g;
            c.beginPath(); c.arc(cx, cy, pc * 1.2, 0, Math.PI * 2); c.fill();
        }
        function drawMud(c, cx, cy, pc, col, s) {
            c.fillStyle = col;
            c.beginPath(); c.arc(cx, cy, pc, 0, Math.PI * 2); c.fill();
            // Pequenos sulcos
            c.strokeStyle = adj(col, -30);
            c.lineWidth = 2;
            c.beginPath();
            c.moveTo(cx - 5, cy - 2); c.lineTo(cx + 5, cy + 2);
            c.stroke();
        }
        function drawBlood(c, cx, cy, pc, col, t, s) {
            const drip = Math.sin(t * 0.04 + s) * pc * 0.3;
            const br = Math.max(1, pc);
            const g = c.createRadialGradient(cx, cy, 0, cx, cy, br);
            g.addColorStop(0, '#cc0000'); g.addColorStop(0.5, col); g.addColorStop(1, '#1a0000');
            c.fillStyle = g; c.beginPath(); c.arc(cx, cy + drip * 0.3, Math.max(1, br * 0.85), 0, Math.PI * 2); c.fill();
            c.fillStyle = '#990000';
            for (let i = 0; i < 3; i++) { c.beginPath(); c.arc(cx + (s * i * 7) % br - br / 2, cy + (s * i * 11) % br - br / 2 + drip, 2 + (i % 3), 0, Math.PI * 2); c.fill(); }
        }
        function drawIce(c, cx, cy, pc, col, s, t) {
            const sh = 0.7 + Math.sin(t * 0.07 + s * 0.6) * 0.3;
            const sg = Math.max(1, pc); const g = c.createLinearGradient(cx - sg, cy - sg, cx + sg, cy + sg);
            g.addColorStop(0, `rgba(200,240,255,${sh})`); g.addColorStop(0.4, col); g.addColorStop(1, adj(col, -30));
            c.fillStyle = g; c.fillRect(cx - pc * 0.9, cy - pc * 0.9, pc * 1.8, pc * 1.8);
            c.strokeStyle = `rgba(255,255,255,${0.3 + sh * 0.2})`; c.lineWidth = 1;
            c.beginPath(); c.moveTo(cx - (s % 12) + 3, cy - (s % 8) + 2); c.lineTo(cx + (s % 10) - 4, cy + (s % 12) - 5); c.stroke();
            c.beginPath(); c.moveTo(cx + (s % 8) - 2, cy - (s % 10) + 4); c.lineTo(cx - (s % 10) + 3, cy + (s % 8) - 2); c.stroke();
        }
        function drawMushroom(c, cx, cy, pc, col, s) {
            if (pc <= 0) return;
            c.fillStyle = '#2d1a0a'; c.fillRect(cx - pc * 0.8, cy - pc * 0.8, pc * 1.6, pc * 1.6);
            const nm = 1 + (s % 3);
            for (let i = 0; i < nm; i++) {
                const mx = cx + (pc > 0 ? (s * (i + 1) * 13) % pc : 0) - (pc / 2), my = cy + (pc > 0 ? (s * (i + 1) * 7) % (pc / 2) : 0);
                c.fillStyle = '#e8d5b0'; c.fillRect(mx - 3, my - 8, 6, 12);
                c.fillStyle = col; c.beginPath(); c.arc(mx, my - 8, (s % (6 + i)) + 6, Math.PI, 0); c.fill();
                c.fillStyle = 'rgba(255,255,255,0.5)';
                for (let j = 0; j < 3; j++) { c.beginPath(); c.arc(mx + (j * 4 - 4), my - (8 + j * 2), 1.5, 0, Math.PI * 2); c.fill(); }
            }
        }
        function drawVoid(c, cx, cy, pc, col, t, s) {
            const pulse = 0.6 + Math.sin(t * 0.05 + s * 0.7) * 0.4;
            const vr = Math.max(1, pc * 1.2);
            const g = c.createRadialGradient(cx, cy, 0, cx, cy, vr);
            g.addColorStop(0, `rgba(60,0,120,${pulse})`); g.addColorStop(0.5, col); g.addColorStop(1, 'rgba(0,0,0,0)');
            c.fillStyle = g; c.beginPath(); c.arc(cx, cy, pc * 1.1, 0, Math.PI * 2); c.fill();
            c.fillStyle = `rgba(180,120,255,${0.3 + pulse * 0.3})`;
            for (let i = 0; i < 4; i++) {
                const px = cx + Math.sin(t * 0.08 + i * 1.57 + s) * pc * 0.6;
                const py = cy + Math.cos(t * 0.08 + i * 1.57 + s) * pc * 0.6;
                c.beginPath(); c.arc(px, py, 1.5, 0, Math.PI * 2); c.fill();
            }
        }
        function drawGeneric(c, cx, cy, pc, col, s) {
            c.save(); c.globalAlpha = 0.5; c.fillStyle = adj(col, 25); c.beginPath(); c.arc(cx, cy, Math.max(0.1, pc * 0.9), 0, Math.PI * 2); c.fill(); c.restore();
            c.fillStyle = col; c.beginPath(); c.arc(cx, cy, pc * 0.6, 0, Math.PI * 2); c.fill();
        }

        // ─── MASMORRAS REDESENHADAS ──────────────────────────────────────────────────
        function drawRoomOuter(ctxD, r) {
            const style = r.style || 'solid', color = r.fillColor, s = r.id || 99;

            // NOVO: Proteção contra "chão tampando". Criamos um recorte que impede 
            // que o preenchimento externo invada o interior da sala.
            ctxD.save();
            ctxD.beginPath();
            ctxD.rect(r.x - 40, r.y - 40, r.w + 80, r.h + 80);
            ctxD.rect(r.x, r.y, r.w, r.h);
            ctxD.clip("evenodd");

            if (style === 'cavern') {
                // Revertido: Não preenche o fundo grosseiramente, mantém foco nos detalhes/arcos
                ctxD.fillStyle = adj(color, -10); // Cor mais escura para o contorno
                ctxD.beginPath(); let seed = s;
                for (let x = r.x - 15; x <= r.x + r.w + 15; x += 15) { ctxD.arc(x, r.y - 15, 10 + (seed % 15), 0, 7); ctxD.arc(x, r.y + r.h + 15, 10 + (seed % 15), 0, 7); seed++; }
                for (let y = r.y - 15; y <= r.y + r.h + 15; y += 15) { ctxD.arc(r.x - 15, y, 10 + (seed % 15), 0, 7); ctxD.arc(r.x + r.w + 15, y, 10 + (seed % 15), 0, 7); seed++; }
                ctxD.fill();
            } else if (style === 'temple') {
                ctxD.fillStyle = color; ctxD.fillRect(r.x - 12, r.y - 12, r.w + 24, r.h + 24);
                ctxD.fillStyle = adj(color, 20);
                [[r.x - 12, r.y - 12], [r.x + r.w - 4, r.y - 12], [r.x - 12, r.y + r.h - 4], [r.x + r.w - 4, r.y + r.h - 4]].forEach(([px, py]) => ctxD.fillRect(px, py, 16, 16));
            } else if (style === 'crypt') {
                ctxD.fillStyle = color; ctxD.fillRect(r.x - 10, r.y - 10, r.w + 20, r.h + 20);
                ctxD.fillStyle = 'rgba(30,80,20,0.45)';
                ctxD.fillRect(r.x - 10, r.y - 10, r.w + 20, 8); ctxD.fillRect(r.x - 10, r.y + r.h + 2, r.w + 20, 8);
            } else if (style === 'fortress') {
                ctxD.fillStyle = color; ctxD.fillRect(r.x - 14, r.y - 14, r.w + 28, r.h + 28);
                ctxD.fillStyle = adj(color, -15);
                for (let x = r.x - 14; x < r.x + r.w + 14; x += 12) { ctxD.fillRect(x, r.y - 20, 8, 8); ctxD.fillRect(x, r.y + r.h + 12, 8, 8); }
                for (let y = r.y - 14; y < r.y + r.h + 14; y += 12) { ctxD.fillRect(r.x - 20, y, 8, 8); ctxD.fillRect(r.x + r.w + 12, y, 8, 8); }
            } else if (style === 'prison') {
                ctxD.fillStyle = color; ctxD.fillRect(r.x - 10, r.y - 10, r.w + 20, r.h + 20);
                ctxD.strokeStyle = adj(color, 30); ctxD.lineWidth = 2;
                for (let x = r.x - 8; x < r.x + r.w + 8; x += 8) {
                    ctxD.beginPath(); ctxD.moveTo(x, r.y - 10); ctxD.lineTo(x, r.y); ctxD.stroke();
                    ctxD.beginPath(); ctxD.moveTo(x, r.y + r.h); ctxD.lineTo(x, r.y + r.h + 10); ctxD.stroke();
                }
            } else if (style === 'crystal') {
                ctxD.fillStyle = color; ctxD.fillRect(r.x - 12, r.y - 12, r.w + 24, r.h + 24);
                ctxD.globalAlpha = 0.27; ctxD.fillStyle = adj(color, 60);
                ctxD.beginPath(); ctxD.moveTo(r.x, r.y - 12); ctxD.lineTo(r.x + r.w, r.y - 12); ctxD.lineTo(r.x + r.w + 12, r.y); ctxD.lineTo(r.x + r.w + 12, r.y + r.h); ctxD.lineTo(r.x + r.w, r.y + r.h + 12); ctxD.lineTo(r.x, r.y + r.h + 12); ctxD.lineTo(r.x - 12, r.y + r.h); ctxD.lineTo(r.x - 12, r.y); ctxD.closePath(); ctxD.fill();
            } else {
                ctxD.fillStyle = color; ctxD.fillRect(r.x - 15, r.y - 15, r.w + 30, r.h + 30);
            }
            ctxD.restore();
        }
        function drawRoomDetail(ctxD, r) {
            if (r.style === 'temple') {
                ctxD.strokeStyle = adj(r.wallColor, 30); ctxD.lineWidth = 1; ctxD.setLineDash([4, 4]); ctxD.strokeRect(r.x + 4, r.y + 4, r.w - 8, r.h - 8); ctxD.setLineDash([]);
            } else if (r.style === 'crystal') {
                ctxD.globalAlpha = 0.33; ctxD.strokeStyle = adj(r.wallColor, 80); ctxD.lineWidth = 1;
                ctxD.beginPath(); ctxD.moveTo(r.x, r.y + r.h / 3); ctxD.lineTo(r.x + r.w / 3, r.y); ctxD.stroke();
                ctxD.beginPath(); ctxD.moveTo(r.x + r.w * 2 / 3, r.y); ctxD.lineTo(r.x + r.w, r.y + r.h / 3); ctxD.stroke();
                ctxD.globalAlpha = 1;
            }
        }

        // ─── ESCALA DE REFERÊNCIA ────────────────────────────────────────────────────
        function drawScaleRef(ctxD) {
            // Recalculate full bounding box same as export
            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
            State.rooms.forEach(r => { minX = Math.min(minX, r.x - 30); minY = Math.min(minY, r.y - 30); maxX = Math.max(maxX, r.x + r.w + 30); maxY = Math.max(maxY, r.y + r.h + 30); });
            State.props.forEach(p => { minX = Math.min(minX, p.x); minY = Math.min(minY, p.y); maxX = Math.max(maxX, p.x + p.w); maxY = Math.max(maxY, p.y + p.h); });
            State.textLabels.forEach(t => { minX = Math.min(minX, t.x - 10); minY = Math.min(minY, t.y - (t.fontSize || 14)); maxX = Math.max(maxX, t.x + 300); maxY = Math.max(maxY, t.y + (t.fontSize || 14)); });
            for (const k in State.terrainCells) { const [c, r] = k.split(','); const tx = c * (CELL / 2), ty = r * (CELL / 2); minX = Math.min(minX, tx - 40); minY = Math.min(minY, ty - 40); maxX = Math.max(maxX, tx + 60); maxY = Math.max(maxY, ty + 60); }
            if (minX === Infinity) return;
            // Place scale bar at bottom-left of content area (in world coords, offset from export origin)
            const bw = CELL * 5, bh = 8;
            const x = minX + 16, y = maxY + 24;
            ctxD.save();
            ctxD.fillStyle = 'rgba(0,0,0,0.65)'; ctxD.fillRect(x - 8, y - 8, bw + 16, bh + 26);
            ctxD.fillStyle = '#fff'; ctxD.fillRect(x, y, bw, bh);
            ctxD.fillStyle = '#888'; ctxD.fillRect(x + bw / 2, y, bw / 2, bh);
            ctxD.fillStyle = '#fff'; ctxD.font = 'bold 10px monospace';
            ctxD.textAlign = 'left'; ctxD.textBaseline = 'top'; ctxD.fillText('0', x, y + bh + 3);
            ctxD.textAlign = 'center'; ctxD.fillText('2.5', x + bw / 2, y + bh + 3);
            ctxD.textAlign = 'right'; ctxD.fillText('5 unid.', x + bw, y + bh + 3);
            ctxD.restore();
        }


        // ─── NÉVOA DE GUERRA — RENDER ────────────────────────────────────────────────
        function renderFog(c) {
            // Cobre tudo com névoa escura
            c.save();
            c.translate(panX, panY);
            c.scale(scale, scale);

            const pc = CELL / 2;
            // Calcular bounding das células visíveis na tela
            const startC = Math.floor((-panX / scale) / pc) - 2;
            const startR = Math.floor((-panY / scale) / pc) - 2;
            const endC = startC + Math.ceil(width / scale / pc) + 4;
            const endR = startR + Math.ceil(height / scale / pc) + 4;

            c.fillStyle = 'rgba(0,0,0,0.82)';

            for (let col = startC; col <= endC; col++) {
                for (let row = startR; row <= endR; row++) {
                    if (!fogRevealed.has(`${col},${row}`)) {
                        c.fillRect(col * pc, row * pc, pc + 0.5, pc + 0.5);
                    }
                }
            }

            // Borda suave nas células reveladas (blur effect via multiple rects)
            c.fillStyle = 'rgba(0,0,0,0.35)';
            for (let col = startC; col <= endC; col++) {
                for (let row = startR; row <= endR; row++) {
                    if (!fogRevealed.has(`${col},${row}`)) continue;
                    // Verificar vizinhos — se tem vizinho encoberto, pintar meia borda
                    const neighbors = [`${col - 1},${row}`, `${col + 1},${row}`, `${col},${row - 1}`, `${col},${row + 1}`];
                    const hasFogNeighbor = neighbors.some(n => !fogRevealed.has(n));
                    if (hasFogNeighbor) {
                        c.beginPath();
                        c.arc(col * pc + pc / 2, row * pc + pc / 2, pc * 0.6, 0, Math.PI * 2);
                        c.fill();
                    }
                }
            }

            c.restore();

            // Cursor de pincel da névoa
            if ((currentTool === 'fog') && !presentationMode) {
                // drawn no espaço de tela via lastMousePos
                if (window._fogMousePos) {
                    const { sx, sy } = window._fogMousePos;
                    c.save();
                    c.strokeStyle = fogMode === 'reveal' ? 'rgba(250,220,60,0.8)' : 'rgba(80,80,200,0.7)';
                    c.lineWidth = 2;
                    c.setLineDash([4, 4]);
                    const r = fogBrushSize * pc * scale;
                    c.beginPath();
                    c.arc(sx, sy, r, 0, Math.PI * 2);
                    c.stroke();
                    c.setLineDash([]);
                    c.restore();
                }
            }
        }

        function applyFogBrush(rawX, rawY) {
            const pc = CELL / 2;
            const cc = Math.floor(rawX / pc);
            const rr = Math.floor(rawY / pc);
            for (let dc = -fogBrushSize; dc <= fogBrushSize; dc++) {
                for (let dr = -fogBrushSize; dr <= fogBrushSize; dr++) {
                    if (dc * dc + dr * dr <= fogBrushSize * fogBrushSize) {
                        const key = `${cc + dc},${rr + dr}`;
                        if (fogMode === 'reveal') fogRevealed.add(key);
                        else fogRevealed.delete(key);
                    }
                }
            }
        }

        function revealAll() {
            // Revela todas as células que têm conteúdo
            const pc = CELL / 2;
            let minC = 999, minR = 999, maxC = -999, maxR = -999;
            State.rooms.forEach(r => {
                minC = Math.min(minC, Math.floor(r.x / pc) - 2); minR = Math.min(minR, Math.floor(r.y / pc) - 2);
                maxC = Math.max(maxC, Math.ceil((r.x + r.w) / pc) + 2); maxR = Math.max(maxR, Math.ceil((r.y + r.h) / pc) + 2);
            });
            for (const k in State.terrainCells) { const [c, r] = k.split(',').map(Number); minC = Math.min(minC, c - 1); minR = Math.min(minR, r - 1); maxC = Math.max(maxC, c + 1); maxR = Math.max(maxR, r + 1); }
            if (minC === 999) { minC = -10; minR = -10; maxC = 40; maxR = 40; }
            for (let c = minC; c <= maxC; c++) for (let r = minR; r <= maxR; r++) fogRevealed.add(`${c},${r}`);
        }

        function coverAll() { fogRevealed.clear(); }

        function toggleFog() {
            fogEnabled = !fogEnabled;
            const btn = document.getElementById('btnFog');
            if (btn) btn.classList.toggle('active', fogEnabled);
            if (fogEnabled && fogRevealed.size === 0) revealAll(); // começa revelado
            updateFogPanel();
            showToast(fogEnabled ? '🌫️ Névoa de guerra ativada!' : '☀️ Névoa desativada');
        }

        function updateFogPanel() {
            const panel = document.getElementById('fogPanel');
            if (panel) panel.style.display = fogEnabled ? 'block' : 'none';
        }

        function setFogMode(mode) {
            fogMode = mode;
            document.getElementById('btnFogReveal').classList.toggle('active', mode === 'reveal');
            document.getElementById('btnFogCover').classList.toggle('active', mode === 'cover');
            if (fogEnabled) setTool('fog');
        }

        function setFogBrush(val) {
            fogBrushSize = parseInt(val);
            const el = document.getElementById('fogBrushVal');
            if (el) el.textContent = val;
        }

        // ─── MODO APRESENTAÇÃO ───────────────────────────────────────────────────────
        function togglePresentation() {
            presentationMode = !presentationMode;
            const wrapper = document.querySelector('.editor-wrapper');
            const toolbar = document.querySelector('.action-toolbar');
            const sidebarL = document.getElementById('sidebarLeft');
            const sidebarR = document.getElementById('sidebarRight');
            const header = document.querySelector('header');
            const footer = document.querySelector('footer');
            const minimap = document.getElementById('minimapCanvas');

            if (presentationMode) {
                // Entrar em modo apresentação
                [toolbar, sidebarL, sidebarR, header, footer].forEach(el => {
                    if (el) { el.dataset.pdisplay = el.style.display || ''; el.style.display = 'none'; }
                });
                wrapper.style.marginTop = '0';
                wrapper.style.height = '100vh';
                wrapper.style.borderTop = 'none';
                document.body.style.overflow = 'hidden';
                // Overlay de apresentação
                createPresentationOverlay();
                showToast('🎭 Modo Apresentação — Esc para sair');
                selectedItem = null; multiSelection = [];
                setTool('select');
                if (minimap) minimap.style.display = 'none';
            } else {
                exitPresentation();
            }
            setTimeout(() => resize(), 50);
        }

        function createPresentationOverlay() {
            const ov = document.createElement('div');
            ov.id = 'presentationOverlay';
            ov.innerHTML = `
        <div class="pres-hud">
            <div class="pres-info">
                <span class="pres-badge">🎭 APRESENTAÇÃO</span>
                <span class="pres-hint">Scroll = zoom · Arrastar = mover · Esc = sair</span>
            </div>
            <div class="pres-actions">
                ${fogEnabled ? `<button class="pres-btn" onclick="setFogMode('reveal');setTool('fog')">👁 Revelar</button>
                <button class="pres-btn" onclick="setFogMode('cover');setTool('fog')">🌫 Encobrir</button>` : ''}
                <button class="pres-btn pres-exit" onclick="exitPresentation()">✕ Sair</button>
            </div>
        </div>`;
            document.body.appendChild(ov);
        }

        function exitPresentation() {
            presentationMode = false;
            const wrapper = document.querySelector('.editor-wrapper');
            const ov = document.getElementById('presentationOverlay');
            const header = document.querySelector('header');
            const footer = document.querySelector('footer');
            const toolbar = document.querySelector('.action-toolbar');
            const sidebarL = document.getElementById('sidebarLeft');
            const sidebarR = document.getElementById('sidebarRight');

            [toolbar, sidebarL, sidebarR, header, footer].forEach(el => {
                if (el) el.style.display = el.dataset.pdisplay || '';
            });
            wrapper.style.marginTop = '';
            wrapper.style.height = '';
            wrapper.style.borderTop = '';
            document.body.style.overflow = '';
            if (ov) ov.remove();
            setTool('select');
            setTimeout(() => resize(), 50);
        }

        // ─── RÉGUA ───────────────────────────────────────────────────────────────────
        function drawRuler() {
            const rulerH = document.getElementById('rulerH');
            const rulerV = document.getElementById('rulerV');
            const corner = document.getElementById('rulerCorner');
            if (!rulerH || !rulerV) return;

            const visible = document.getElementById('toggleRuler')?.classList.contains('active');
            if (!visible) {
                rulerH.style.display = 'none';
                rulerV.style.display = 'none';
                if (corner) corner.style.display = 'none';
                return;
            }

            rulerH.style.display = '';
            rulerV.style.display = '';
            if (corner) corner.style.display = 'flex';

            // OTMIZAÇÃO: Só re-renderiza se a posição ou escala mudaram significativamente
            const stateH = rulerH._lastState;
            const stateV = rulerV._lastState;
            if (stateH && stateH.panX === panX && stateH.scale === scale &&
                stateV && stateV.panY === panY && stateV.scale === scale) return;

            const rW = rulerH.offsetWidth;
            const rH = rulerV.offsetHeight;

            rulerH.innerHTML = '';
            rulerV.innerHTML = '';

            const minPxBetween = 36;
            let step = 1;
            while (CELL * scale * step < minPxBetween) step = step < 5 ? 5 : step + 5;

            // ── Ticks HORIZONTAIS
            const sxWorld = Math.floor(-panX / scale / CELL) * CELL;
            for (let wx = sxWorld; wx < (rW - panX) / scale + CELL * step; wx += CELL * step) {
                const px = panX + wx * scale;
                if (px < 0 || px > rW + 10) continue;
                const isMajor = (Math.round(wx / CELL) % (step * 2) === 0);
                const tickH = isMajor ? 10 : 6;
                const div = document.createElement('div');
                div.className = 'ruler-tick-h';
                div.style.left = px + 'px';
                div.innerHTML = `<span class="tick-label">${Math.round(wx / CELL)}</span>
            <span class="tick-line" style="height:${tickH}px"></span>`;
                rulerH.appendChild(div);
            }

            // ── Ticks VERTICAIS
            const syWorld = Math.floor(-panY / scale / CELL) * CELL;
            for (let wy = syWorld; wy < (rH - panY) / scale + CELL * step; wy += CELL * step) {
                const py = panY + wy * scale;
                if (py < 0 || py > rH + 10) continue;
                const isMajor = (Math.round(wy / CELL) % (step * 2) === 0);
                const tickW = isMajor ? 10 : 6;
                const div = document.createElement('div');
                div.className = 'ruler-tick-v';
                div.style.top = py + 'px';
                div.innerHTML = `<span class="tick-line" style="width:${tickW}px"></span>
            <span class="tick-label" style="left:${26 / 2}px">${Math.round(wy / CELL)}</span>`;
                rulerV.appendChild(div);
            }

            rulerH._lastState = { panX, scale };
            rulerV._lastState = { panY, scale };
        }



        // ─── MINIMAP ─────────────────────────────────────────────────────────────────
        function renderMinimap() {
            const mel = document.getElementById('minimapCanvas');
            if (!mel || !mel.classList.contains('visible')) return;
            const mW = mel.width = mel.offsetWidth, mH = mel.height = mel.offsetHeight;
            const mc = mel.getContext('2d');
            mc.fillStyle = '#07040f'; mc.fillRect(0, 0, mW, mH);
            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
            State.rooms.forEach(r => { minX = Math.min(minX, r.x); minY = Math.min(minY, r.y); maxX = Math.max(maxX, r.x + r.w); maxY = Math.max(maxY, r.y + r.h); });
            State.props.forEach(p => { minX = Math.min(minX, p.x); minY = Math.min(minY, p.y); maxX = Math.max(maxX, p.x + p.w); maxY = Math.max(maxY, p.y + p.h); });
            State.textLabels.forEach(t => { minX = Math.min(minX, t.x); minY = Math.min(minY, t.y); maxX = Math.max(maxX, t.x + 100); maxY = Math.max(maxY, t.y + t.fontSize); });
            for (const k in State.terrainCells) { const [c, r] = k.split(','); const p2 = CELL / 2; minX = Math.min(minX, c * p2); minY = Math.min(minY, r * p2); maxX = Math.max(maxX, c * p2 + p2); maxY = Math.max(maxY, r * p2 + p2); }
            if (minX === Infinity) { mc.fillStyle = 'rgba(255,255,255,0.08)'; mc.font = '10px Montserrat,sans-serif'; mc.textAlign = 'center'; mc.fillText('vazio', mW / 2, mH / 2); return; }
            const pad = 16, cW = maxX - minX + pad * 2, cH = maxY - minY + pad * 2;
            const ms = Math.min(mW / cW, mH / cH) * 0.9;
            const ox = (mW - cW * ms) / 2 - (minX - pad) * ms, oy = (mH - cH * ms) / 2 - (minY - pad) * ms;
            mel._minimap = { ms, ox, oy };
            mc.save(); mc.translate(ox, oy); mc.scale(ms, ms);
            // Terrenos
            for (const k in State.terrainCells) { const [c, r] = k.split(','); const p2 = CELL / 2; const _tc = State.terrainCells[k]; const _col = Array.isArray(_tc) ? (_tc[_tc.length - 1]?.color || '#888') : _tc.color; mc.globalAlpha = 0.8; mc.fillStyle = _col || '#888'; mc.fillRect(c * p2, r * p2, p2, p2); mc.globalAlpha = 1; }
            // Salas
            State.rooms.forEach(r => { mc.fillStyle = r.fillColor + 'cc'; mc.fillRect(r.x - 15, r.y - 15, r.w + 30, r.h + 30); mc.fillStyle = r.floorColor; mc.fillRect(r.x, r.y, r.w, r.h); if (r.wallWidth > 0) { mc.strokeStyle = r.wallColor; mc.lineWidth = r.wallWidth / ms; mc.strokeRect(r.x, r.y, r.w, r.h); } });
            State.internalWalls.forEach(w => { mc.strokeStyle = w.wallColor; mc.lineWidth = w.wallWidth / ms; mc.beginPath(); mc.moveTo(w.x1, w.y1); mc.lineTo(w.x2, w.y2); mc.stroke(); });
            State.props.forEach(p => {
                if (p.type === 'emoji') { mc.save(); mc.font = `${Math.max(8, p.w * 0.7)}px Arial`; mc.textAlign = 'center'; mc.textBaseline = 'middle'; mc.fillText(p.content, p.x + p.w / 2, p.y + p.h / 2); mc.restore(); }
                else if (p.type === 'image') { try { mc.drawImage(p.content, p.x, p.y, p.w, p.h); } catch (e) { } }
            });
            State.textLabels.forEach(t => { mc.font = `${t.bold ? 'bold ' : ''} ${Math.max(6, t.fontSize * 0.7)}px Cinzel,sans-serif`; mc.globalAlpha = 0.8; mc.fillStyle = t.color; mc.textAlign = 'left'; mc.textBaseline = 'middle'; mc.fillText(t.content, t.x, t.y); mc.globalAlpha = 1; });
            // Viewport atual
            const vx = -panX / scale, vy = -panY / scale, vw = width / scale, vh = height / scale;
            mc.strokeStyle = 'rgba(139,92,246,0.9)'; mc.lineWidth = 3 / ms; mc.setLineDash([6 / ms, 4 / ms]); mc.strokeRect(vx, vy, vw, vh); mc.setLineDash([]);
            mc.restore();
        }


        // Clicar no minimapa para teletransportar
        document.getElementById('minimapCanvas').addEventListener('click', function (e) {
            const mm = this._minimap; if (!mm) return;
            const rect = this.getBoundingClientRect();
            const mx = e.clientX - rect.left, my = e.clientY - rect.top;
            panX = width / 2 - (mx - mm.ox) / mm.ms * scale;
            panY = height / 2 - (my - mm.oy) / mm.ms * scale;
        });
        document.getElementById('minimapCanvas').style.pointerEvents = 'auto';

        // ── LUPA (Magnifier) ─────────────────────────────────────────
        let lupActive = false;
        let lupZoom = 3;    // fator de ampliação padrão
        const LUP_SIZE = 160; // diâmetro em px

        function toggleMinimap() {
            lupActive = !lupActive;
            const el = document.getElementById('lupCanvas');
            const lbl = document.getElementById('lupLabel');
            const btn = document.getElementById('minimapToggleBtn');
            // Também togla o minimapa de visão geral
            const mel = document.getElementById('minimapCanvas');
            if (lupActive) {
                if (el) { el.classList.add('visible'); }
                if (lbl) { lbl.style.display = 'block'; }
                if (btn) { btn.classList.add('active'); }
                if (mel) { mel.classList.add('visible'); }
                showToast('🔍 Lupa + Minimapa ativados');
            } else {
                if (el) { el.classList.remove('visible'); el.style.display = 'none'; }
                if (lbl) { lbl.style.display = 'none'; }
                if (btn) { btn.classList.remove('active'); }
                if (mel) { mel.classList.remove('visible'); }
            }
            renderMinimap();
        }

        // Roda do mouse sobre o canvas: quando lupa ativa, Ctrl+Scroll muda o zoom da lupa
        canvas.addEventListener('wheel', function lupZoomHandler(e) {
            if (!lupActive || !e.ctrlKey) return;
            e.preventDefault();
            lupZoom = Math.max(1.5, Math.min(8, lupZoom + (e.deltaY < 0 ? 0.3 : -0.3)));
            const lbl = document.getElementById('lupLabel');
            if (lbl) lbl.textContent = `🔍 ${lupZoom.toFixed(1)}×`;
        }, { passive: false });

        canvas.addEventListener('mousemove', function updateLupa(e) {
            if (!lupActive) return;
            const rect = canvas.getBoundingClientRect();
            const mx = e.clientX - rect.left;
            const my = e.clientY - rect.top;

            const lupEl = document.getElementById('lupCanvas');
            const lblEl = document.getElementById('lupLabel');
            if (!lupEl) return;

            const half = LUP_SIZE / 2;
            // Posicionar a lupa ao redor do cursor (deslocada para não tampar)
            let lx = mx + 20;
            let ly = my - LUP_SIZE - 20;
            // Se sair para cima, colocar abaixo
            if (ly < 0) ly = my + 20;
            // Se sair para direita, colocar à esquerda
            if (lx + LUP_SIZE > canvas.width) lx = mx - LUP_SIZE - 20;

            lupEl.style.left = lx + 'px';
            lupEl.style.top = ly + 'px';
            lupEl.style.width = LUP_SIZE + 'px';
            lupEl.style.height = LUP_SIZE + 'px';

            if (lblEl) {
                lblEl.style.left = (lx + LUP_SIZE / 2 - 24) + 'px';
                lblEl.style.top = (ly + LUP_SIZE + 4) + 'px';
                lblEl.textContent = `🔍 ${lupZoom.toFixed(1)}×`;
            }

            // Desenhar a lupa
            const lc = lupEl.getContext('2d');
            const lW = lupEl.width = LUP_SIZE;
            const lH = lupEl.height = LUP_SIZE;

            // Limpar
            lc.clearRect(0, 0, lW, lH);
            // Clip circular
            lc.save();
            lc.beginPath();
            lc.arc(half, half, half, 0, Math.PI * 2);
            lc.clip();

            // Fundo
            lc.fillStyle = '#07040f';
            lc.fillRect(0, 0, lW, lH);

            // Calcular a região do canvas a ampliar
            // Centro do mundo sob o cursor
            const worldX = (mx - panX) / scale;
            const worldY = (my - panY) / scale;

            // Transformação: lupa mostra uma janela de tamanho (LUP_SIZE/lupZoom) centrada em worldX,worldY
            const viewW = LUP_SIZE / lupZoom;
            const viewH = LUP_SIZE / lupZoom;
            const newPanX = half - worldX * lupZoom;
            const newPanY = half - worldY * lupZoom;

            lc.translate(newPanX, newPanY);
            lc.scale(lupZoom, lupZoom);

            // Grade de pontos
            lc.fillStyle = 'rgba(139,92,246,0.12)';
            const gsx = Math.floor((0 - newPanX) / lupZoom / CELL) * CELL;
            const gsy = Math.floor((0 - newPanY) / lupZoom / CELL) * CELL;
            for (let gx = gsx; gx < gsx + (lW / lupZoom) + CELL; gx += CELL)
                for (let gy = gsy; gy < gsy + (lH / lupZoom) + CELL; gy += CELL) {
                    lc.beginPath(); lc.arc(gx, gy, 1.2, 0, Math.PI * 2); lc.fill();
                }

            // Renderizar o mapa nesse contexto
            renderMap(lc, false);

            lc.restore();

            // Borda interna com crosshair central
            lc.save();
            lc.strokeStyle = 'rgba(139,92,246,0.5)';
            lc.lineWidth = 1;
            lc.setLineDash([4, 4]);
            lc.beginPath(); lc.moveTo(half, half - 12); lc.lineTo(half, half + 12); lc.stroke();
            lc.beginPath(); lc.moveTo(half - 12, half); lc.lineTo(half + 12, half); lc.stroke();
            lc.setLineDash([]);
            lc.restore();
        });

        canvas.addEventListener('mouseleave', function hideLupa() {
            if (!lupActive) return;
            const lupEl = document.getElementById('lupCanvas');
            const lblEl = document.getElementById('lupLabel');
            if (lupEl) lupEl.style.display = 'none';
            if (lblEl) lblEl.style.display = 'none';
        });
        canvas.addEventListener('mouseenter', function showLupa() {
            if (!lupActive) return;
            const lupEl = document.getElementById('lupCanvas');
            const lblEl = document.getElementById('lupLabel');
            if (lupEl) { lupEl.style.display = 'block'; }
            if (lblEl) { lblEl.style.display = 'block'; }
        });


        // ─── CAMADAS ─────────────────────────────────────────────────────────────────
        function buildLayersPanel() {
            const cont = document.getElementById('layersList'); if (!cont) return; cont.innerHTML = '';
            State.layers.slice().reverse().forEach((layer, idx, arr) => {
                const div = document.createElement('div');
                div.className = 'layer-item' + (State.activeLayer === layer.id ? ' active' : '');
                div.onclick = (e) => { if (!e.target.closest('button')) setActiveLayer(layer.id); };
                const eyeIcon = layer.visible ? 'fa-eye' : 'fa-eye-slash';
                const lockIcon = layer.locked ? 'fa-lock' : 'fa-lock-open';
                const lockCls = layer.locked ? 'locked' : '';
                const visCls = layer.visible ? 'on' : 'off';

                div.innerHTML = `<span class="layer-name">${layer.name}</span>
        <div class="layer-controls">
            <div class="order-btns">
                <button onclick="State.moveLayer('${layer.id}', 1);event.stopPropagation();" class="layer-btn-sm" title="Subir"><i class="fas fa-chevron-up"></i></button>
                <button onclick="State.moveLayer('${layer.id}', -1);event.stopPropagation();" class="layer-btn-sm" title="Descer"><i class="fas fa-chevron-down"></i></button>
            </div>
            <button onclick="toggleLayerVisible('${layer.id}');event.stopPropagation();" class="layer-btn ${visCls}" title="${layer.visible ? 'Ocultar' : 'Mostrar'}"><i class="fas ${eyeIcon}"></i></button>
            <button onclick="toggleLayerLock('${layer.id}');event.stopPropagation();" class="layer-btn ${lockCls}" title="${layer.locked ? 'Destravar' : 'Travar'}"><i class="fas ${lockIcon}"></i></button>
        </div>`;
                cont.appendChild(div);
            });
        }
        function setActiveLayer(id) { State.activeLayer = id; buildLayersPanel(); }
        function toggleLayerVisible(id) { const l = State.layers.find(x => x.id === id); if (l) l.visible = !l.visible; buildLayersPanel(); }
        function toggleLayerLock(id) { const l = State.layers.find(x => x.id === id); if (l) l.locked = !l.locked; buildLayersPanel(); }

        // ─── SIDEBAR ─────────────────────────────────────────────────────────────────
        function toggleSidebar(side) {
            const sb = document.querySelector('.sidebar.' + side); if (!sb) return;
            sb.classList.toggle('collapsed'); setTimeout(() => resize(), 300);
        }

        // ─── TOGGLES ─────────────────────────────────────────────────────────────────
        function toggleRoomLabels() {
            showRoomLabels = !showRoomLabels;
            const btn = document.getElementById('toggleRoomLabels');
            if (btn) btn.classList.toggle('active', showRoomLabels);
        }
        function toggleGrid() {
            showGridLines = !showGridLines;
            const btn = document.getElementById('toggleGrid'); if (btn) btn.classList.toggle('active', showGridLines);
        }
        // toggleMinimap movido para bloco da lupa
        function toggleRuler() {
            const btn = document.getElementById('toggleRuler');
            if (btn) btn.classList.toggle('active');
            drawRuler(); // Atualizar visibilidade imediatamente
        }

        // ─── TOAST ───────────────────────────────────────────────────────────────────
        /**
         * Exibe uma notificação flutuante elegante.
         * @param {string} msg Mensagem a ser exibida.
         * @param {boolean|string} type 'success' | 'error' | 'info' ou boolean.
         */
        function showToast(msg, type = 'info') {
            let cont = document.getElementById('toastContainer');
            if (!cont) { cont = document.createElement('div'); cont.id = 'toastContainer'; document.body.appendChild(cont); }

            const t = document.createElement('div');
            const toastType = (type === true || type === 'error') ? 'error' : (type === 'success' ? 'success' : 'info');
            t.className = `toast toast-${toastType}`;

            // Ícones automáticos
            let icon = 'info-circle';
            if (toastType === 'success') icon = 'check-circle';
            if (toastType === 'error') icon = 'exclamation-triangle';

            t.innerHTML = `<i class="fas fa-${icon}"></i> <span>${msg}</span>`;

            cont.appendChild(t);
            requestAnimationFrame(() => t.classList.add('show'));

            setTimeout(() => {
                t.classList.remove('show');
                setTimeout(() => t.remove(), 500);
            }, 3500);
        }

        // ─── FERRAMENTAS ─────────────────────────────────────────────────────────────
        function setTool(tool) {
            currentTool = tool;
            document.querySelectorAll('.tool-card').forEach(c => c.classList.remove('active'));
            const btn = document.getElementById('btn-' + tool); if (btn) btn.classList.add('active');
            setCursor();
            if (tool !== 'select') { selectedItem = null; multiSelection = []; loadInputsFromSelection(); }
        }
        function setCursor() {
            if (spaceDown) canvas.style.cursor = 'grab';
            else if (currentTool === 'fog') canvas.style.cursor = 'crosshair';
            else if (currentTool === 'select') canvas.style.cursor = 'default';
            else if (currentTool === 'text') canvas.style.cursor = 'text';
            else if (currentTool === 'erase') canvas.style.cursor = 'url("data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'24\' height=\'24\' viewport=\'0 0 24 24\' fill=\'%23ff4444\'><path d=\'M3 6h18v2H3V6zm2 4h14v11c0 1.1-.9 2-2 2H7c-1.1 0-2-.9-2-2V10zm3 2v7h2v-7H8zm4 0v7h2v-7h-2zm4 0v7h2v-7h-2zM9 4V2.5C9 2.2 9.2 2 9.5 2h5c.3 0 .5.2.5.5V4h-6z\'/></svg>") 12 12, auto';
            else canvas.style.cursor = 'crosshair';
        }
        function toggleSnap(s) {
            snapToGrid = s; document.getElementById('btnSnapOn').classList.toggle('active', s); document.getElementById('btnSnapOff').classList.toggle('active', !s);
        }
        function undo() { if (History.undo()) { selectedItem = null; multiSelection = []; loadInputsFromSelection(); AutoSave.scheduleSave(); } }

        // ─── MODAIS ──────────────────────────────────────────────────────────────────
        function showModal(id) { document.getElementById('modalOverlay').style.display = 'flex'; document.querySelectorAll('.modal-box').forEach(m => m.style.display = 'none'); document.getElementById(id).style.display = 'flex'; }
        function closeModals() { document.getElementById('modalOverlay').style.display = 'none'; }
        function confirmClear() { History.save(); State.rooms = []; State.internalWalls = []; State.props = []; State.terrainCells = {}; State.textLabels = []; selectedItem = null; multiSelection = []; closeModals(); AutoSave.scheduleSave(); }

        // ─── EXPORT PNG ──────────────────────────────────────────────────────────────
        function confirmExport(isDark) {
            closeModals();
            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
            State.rooms.forEach(r => { minX = Math.min(minX, r.x - 30); minY = Math.min(minY, r.y - 30); maxX = Math.max(maxX, r.x + r.w + 30); maxY = Math.max(maxY, r.y + r.h + 30); });
            State.props.forEach(p => { minX = Math.min(minX, p.x); minY = Math.min(minY, p.y); maxX = Math.max(maxX, p.x + p.w); maxY = Math.max(maxY, p.y + p.h); });
            State.internalWalls.forEach(w => { minX = Math.min(minX, w.x1, w.x2); minY = Math.min(minY, w.y1, w.y2); maxX = Math.max(maxX, w.x1, w.x2); maxY = Math.max(maxY, w.y1, w.y2); });
            State.textLabels.forEach(t => { minX = Math.min(minX, t.x - 10); minY = Math.min(minY, t.y - t.fontSize); maxX = Math.max(maxX, t.x + 300); maxY = Math.max(maxY, t.y + t.fontSize); });
            for (const k in State.terrainCells) { const [c, r] = k.split(','); const x = c * (CELL / 2), y = r * (CELL / 2); minX = Math.min(minX, x - 40); minY = Math.min(minY, y - 40); maxX = Math.max(maxX, x + 60); maxY = Math.max(maxY, y + 60); }
            if (minX === Infinity) return showToast('Nada para exportar!', true);
            const pad = 80, ew = (maxX - minX) + pad * 2, eh = (maxY - minY) + pad * 2;
            const ec = document.createElement('canvas'); ec.width = ew; ec.height = eh;
            const ex = ec.getContext('2d');
            ex.fillStyle = isDark ? '#15101f' : '#ffffff'; ex.fillRect(0, 0, ew, eh);
            if (!isDark) { ex.fillStyle = 'rgba(0,0,0,0.08)'; for (let x = 0; x < ew; x += CELL)for (let y = 0; y < eh; y += CELL) { ex.beginPath(); ex.arc(x, y, 1.5, 0, Math.PI * 2); ex.fill(); } }
            // Freeze animFrame so terrain animations are consistent during export
            const savedAnimFrame = animFrame;
            ex.translate(-minX + pad, -minY + pad);
            renderMap(ex, true);
            // (animFrame restored automatically since it's a let, not modified during export)
            const link = document.createElement('a'); link.download = 'mapa_rpg_table.png'; link.href = ec.toDataURL('image/png'); link.click();
            showToast('\ud83d\uddbc\ufe0f Imagem exportada!');
        }

        // ─── CONTEXT MENU no canvas ──────────────────────────────────────────────────
        canvas.addEventListener('contextmenu', e => {
            e.preventDefault();
            const pos = getMousePos(e);
            const ct = State.textLabels.slice().reverse().find(t => hitText(t, pos.rawX, pos.rawY));
            if (ct) { selectItem('text', ct); showContextMenu(e, { type: 'text', obj: ct }); return; }
            const cp = State.props.slice().reverse().find(p => pos.rawX >= p.x && pos.rawX <= p.x + p.w && pos.rawY >= p.y && pos.rawY <= p.y + p.h);
            if (cp) { selectItem('prop', cp); showContextMenu(e, { type: 'prop', obj: cp }); return; }
            const cw = State.internalWalls.slice().reverse().find(w => Math.sqrt(distToSegSq({ x: pos.rawX, y: pos.rawY }, { x: w.x1, y: w.y1 }, { x: w.x2, y: w.y2 })) <= 12 / scale);
            if (cw) { selectItem('wall', cw); showContextMenu(e, { type: 'wall', obj: cw }); return; }
            const cr = State.rooms.slice().reverse().find(r => hitRoom(r, pos.rawX, pos.rawY));
            if (cr) { selectItem('room', cr); showContextMenu(e, { type: 'room', obj: cr }); }
        });

        // ─── MOUSEDOWN ───────────────────────────────────────────────────────────────
        canvas.addEventListener('mousedown', e => {
            closeContextMenu();
            const pos = getMousePos(e);
            if (e.button === 1 || spaceDown) { isPanning = true; startPanX = e.clientX - panX; startPanY = e.clientY - panY; canvas.style.cursor = 'grabbing'; return; }
            if (e.button !== 0) return;

            if (currentTool === 'text') {
                const ti = document.getElementById('textInput'); const text = ti?.value.trim();
                if (text) { History.save(); const t = { type: 'label', content: text, x: pos.rawX, y: pos.rawY, fontSize: parseInt(document.getElementById('textSize')?.value || 18), color: document.getElementById('textColor')?.value || '#ffffff', bold: document.getElementById('textBold')?.checked || false, opacity: 1, id: Date.now() }; State.textLabels.push(t); AutoSave.scheduleSave(); }
                return;
            }

            if (currentTool === 'room') {
                if (State.isLayerLocked('terrain') && !['solid', 'cavern', 'temple', 'crypt', 'fortress', 'prison', 'crystal'].includes(globals.style)) return;
                if (State.isLayerLocked('rooms') && ['solid', 'cavern', 'temple', 'crypt', 'fortress', 'prison', 'crystal'].includes(globals.style)) return;

                History.save();
                const dungeonStyles = ['solid', 'cavern', 'temple', 'crypt', 'fortress', 'prison', 'crystal'];
                if (dungeonStyles.includes(globals.style)) {
                    isDrawing = true;
                    currentRoom = { startX: pos.x, startY: pos.y, x: pos.x, y: pos.y, w: 0, h: 0, style: globals.style, fillColor: globals.fillColor, wallColor: globals.wallColor, wallWidth: globals.wallWidth, floorColor: globals.floorColor, opacity: globals.opacity ?? 1, id: Date.now() };
                    selectedItem = null; loadInputsFromSelection();
                } else { isPainting = true; paintTerrain(pos.rawX, pos.rawY); }
                return;
            }

            if (currentTool === 'wall') {
                if (State.isLayerLocked('walls')) return;
                History.save(); isDrawing = true; currentWall = { x1: pos.x, y1: pos.y, x2: pos.x, y2: pos.y, wallColor: globals.wallColor, wallWidth: globals.wallWidth }; return;
            }

            if (currentTool === 'select') {
                let found = false;
                if (selectedItem && (selectedItem.type === 'prop' || selectedItem.type === 'room')) {
                    const o = selectedItem.obj; const hs = { nw: { x: o.x, y: o.y }, ne: { x: o.x + o.w, y: o.y }, sw: { x: o.x, y: o.y + o.h }, se: { x: o.x + o.w, y: o.y + o.h } };
                    for (const h in hs) { if (Math.hypot(pos.rawX - hs[h].x, pos.rawY - hs[h].y) <= 12 / scale) { History.save(); dragAction = 'resize-' + h; isDrawing = true; found = true; break; } }
                }
                if (!found) {
                    if (!State.isLayerLocked('text')) {
                        const ct = State.textLabels.slice().reverse().find(t => hitText(t, pos.rawX, pos.rawY));
                        if (ct) { History.save(); selectItem('text', ct); isDrawing = true; dragAction = 'move'; selectedItem.dragOffsetX = pos.rawX - ct.x; selectedItem.dragOffsetY = pos.rawY - ct.y; found = true; }
                    }
                }
                if (!found) {
                    if (!State.isLayerLocked('props')) {
                        const cp = State.props.slice().reverse().find(p => pos.rawX >= p.x && pos.rawX <= p.x + p.w && pos.rawY >= p.y && pos.rawY <= p.y + p.h);
                        if (cp) {
                            if (altDown) { History.save(); const np = { ...cp, x: cp.x + CELL, y: cp.y + CELL, id: Date.now() }; State.props.push(np); selectItem('prop', np); }
                            else { History.save(); selectItem('prop', cp); isDrawing = true; dragAction = 'move'; selectedItem.dragOffsetX = pos.rawX - cp.x; selectedItem.dragOffsetY = pos.rawY - cp.y; State.props = State.props.filter(p => p.id !== cp.id); State.props.push(cp); }
                            found = true;
                        }
                    }
                }
                if (!found) {
                    if (!State.isLayerLocked('walls')) {
                        const cw = State.internalWalls.slice().reverse().find(w => Math.sqrt(distToSegSq({ x: pos.rawX, y: pos.rawY }, { x: w.x1, y: w.y1 }, { x: w.x2, y: w.y2 })) <= 12 / scale);
                        if (cw) { History.save(); selectItem('wall', cw); isDrawing = true; dragAction = 'move'; selectedItem.dragOffsetX = pos.rawX - cw.x1; selectedItem.dragOffsetY = pos.rawY - cw.y1; found = true; }
                    }
                }
                if (!found) {
                    if (!State.isLayerLocked('rooms')) {
                        const cr = State.rooms.slice().reverse().find(r => hitRoom(r, pos.rawX, pos.rawY));
                        if (cr) {
                            const now = Date.now();
                            if (now - lastClickTime < 380) { startRenameRoom(cr, e.clientX, e.clientY); lastClickTime = 0; }
                            else {
                                lastClickTime = now;
                                if (altDown) { History.save(); const nr = { ...cr, x: cr.x + CELL, y: cr.y + CELL, id: Date.now() }; State.rooms.push(nr); selectItem('room', nr); }
                                else { History.save(); selectItem('room', cr); isDrawing = true; dragAction = 'move'; selectedItem.dragOffsetX = pos.rawX - cr.x; selectedItem.dragOffsetY = pos.rawY - cr.y; }
                            }
                            found = true;
                        }
                    }
                }
                if (!found && e.shiftKey) { selectionRect = { startX: pos.rawX, startY: pos.rawY, x: pos.rawX, y: pos.rawY, w: 0, h: 0 }; isDrawing = true; found = true; }
                if (!found) { isPanning = true; startPanX = e.clientX - panX; startPanY = e.clientY - panY; canvas.style.cursor = 'grabbing'; selectedItem = null; multiSelection = []; loadInputsFromSelection(); }
                return;
            }

            if (currentTool === 'erase') {
                History.save(); isErasing = true;
                eraseTerrain(pos.rawX, pos.rawY);
                State.props = State.props.filter(p => !(pos.rawX >= p.x && pos.rawX <= p.x + p.w && pos.rawY >= p.y && pos.rawY <= p.y + p.h));
                State.rooms = State.rooms.filter(r => !hitRoom(r, pos.rawX, pos.rawY));
                State.internalWalls = State.internalWalls.filter(w => Math.sqrt(distToSegSq({ x: pos.rawX, y: pos.rawY }, { x: w.x1, y: w.y1 }, { x: w.x2, y: w.y2 })) > 12 / scale);
                State.textLabels = State.textLabels.filter(t => !hitText(t, pos.rawX, pos.rawY));
                if (selectedItem) { selectedItem = null; loadInputsFromSelection(); }
            }
            if (currentTool === 'fog' && fogEnabled) {
                isFogging = true; applyFogBrush(pos.rawX, pos.rawY);
            }
        });

        // ─── MOUSEMOVE ───────────────────────────────────────────────────────────────
        canvas.addEventListener('mousemove', e => {
            if (isPanning) { panX = e.clientX - startPanX; panY = e.clientY - startPanY; return; }
            const pos = getMousePos(e);
            window._mousePos = pos; // Usado para o destaque do HUD no render()

            if (currentTool === 'select' && !isDrawing) canvas.style.cursor = getItemAtPos(pos.rawX, pos.rawY) ? 'pointer' : 'default';
            if (isPainting) { paintTerrain(pos.rawX, pos.rawY); return; }
            if (isErasing) { eraseTerrain(pos.rawX, pos.rawY); return; }
            if (isFogging && currentTool === 'fog') { applyFogBrush(pos.rawX, pos.rawY); return; }
            // Track mouse for fog brush cursor
            if (currentTool === 'fog') { window._fogMousePos = { sx: e.clientX - canvas.getBoundingClientRect().left, sy: e.clientY - canvas.getBoundingClientRect().top }; }
            if (!isDrawing) return;

            if (currentTool === 'room' && currentRoom) {
                currentRoom.x = Math.min(pos.x, currentRoom.startX); currentRoom.y = Math.min(pos.y, currentRoom.startY);
                currentRoom.w = Math.abs(pos.x - currentRoom.startX); currentRoom.h = Math.abs(pos.y - currentRoom.startY);
            } else if (currentTool === 'wall' && currentWall) {
                currentWall.x2 = pos.x; currentWall.y2 = pos.y;
            } else if (currentTool === 'select' && selectionRect) {
                selectionRect.x = Math.min(pos.rawX, selectionRect.startX); selectionRect.y = Math.min(pos.rawY, selectionRect.startY);
                selectionRect.w = Math.abs(pos.rawX - selectionRect.startX); selectionRect.h = Math.abs(pos.rawY - selectionRect.startY);
            } else if (currentTool === 'select' && selectedItem) {
                const o = selectedItem.obj, nx = pos.rawX, ny = pos.rawY;
                if (dragAction === 'move') {
                    if (selectedItem.type === 'wall') { const dx = nx - o.x1 - selectedItem.dragOffsetX, dy = ny - o.y1 - selectedItem.dragOffsetY; o.x1 += dx; o.y1 += dy; o.x2 += dx; o.y2 += dy; }
                    else if (selectedItem.type === 'text') { o.x = nx - selectedItem.dragOffsetX; o.y = ny - selectedItem.dragOffsetY; }
                    else { const ss = selectedItem.type === 'room' ? CELL : CELL / 2; if (snapToGrid) { o.x = Math.round((nx - selectedItem.dragOffsetX) / ss) * ss; o.y = Math.round((ny - selectedItem.dragOffsetY) / ss) * ss; } else { o.x = nx - selectedItem.dragOffsetX; o.y = ny - selectedItem.dragOffsetY; } }
                } else if (dragAction.startsWith('resize')) {
                    let rx = snapToGrid ? Math.round(nx / CELL) * CELL : nx, ry = snapToGrid ? Math.round(ny / CELL) * CELL : ny; const ms = 40;
                    if (dragAction === 'resize-se') { o.w = Math.max(ms, rx - o.x); o.h = Math.max(ms, ry - o.y); }
                    if (dragAction === 'resize-sw') { const w = o.x + o.w - rx; if (w > ms) { o.x = rx; o.w = w; } o.h = Math.max(ms, ry - o.y); }
                    if (dragAction === 'resize-ne') { o.w = Math.max(ms, rx - o.x); const h = o.y + o.h - ry; if (h > ms) { o.y = ry; o.h = h; } }
                    if (dragAction === 'resize-nw') { const w = o.x + o.w - rx, h = o.y + o.h - ry; if (w > ms) { o.x = rx; o.w = w; } if (h > ms) { o.y = ry; o.h = h; } }
                }
            }
        });

        // ─── MOUSEUP ─────────────────────────────────────────────────────────────────
        canvas.addEventListener('mouseup', e => {
            if (isPanning) { isPanning = false; setCursor(); return; }
            isPainting = false; isErasing = false; isFogging = false;
            if (selectionRect && currentTool === 'select') {
                const sr = selectionRect; multiSelection = [];
                State.rooms.forEach(r => { if (r.x >= sr.x && r.x + r.w <= sr.x + sr.w && r.y >= sr.y && r.y + r.h <= sr.y + sr.h) multiSelection.push({ type: 'room', obj: r }); });
                State.props.forEach(p => { if (p.x >= sr.x && p.x + p.w <= sr.x + sr.w && p.y >= sr.y && p.y + p.h <= sr.y + sr.h) multiSelection.push({ type: 'prop', obj: p }); });
                State.textLabels.forEach(t => { if (t.x >= sr.x && t.x <= sr.x + sr.w && t.y >= sr.y && t.y <= sr.y + sr.h) multiSelection.push({ type: 'text', obj: t }); });
                if (multiSelection.length > 0) showToast(`\u2705 ${multiSelection.length} item(s) selecionado(s)`);
                selectionRect = null; isDrawing = false; loadInputsFromSelection(); AutoSave.scheduleSave(); return;
            }
            if (!isDrawing) return; isDrawing = false;
            if (currentTool === 'room' && currentRoom) { if (currentRoom.w > 0 && currentRoom.h > 0) State.rooms.push(currentRoom); currentRoom = null; }
            else if (currentTool === 'wall' && currentWall) { if (currentWall.x1 !== currentWall.x2 || currentWall.y1 !== currentWall.y2) State.internalWalls.push(currentWall); currentWall = null; }
            dragAction = 'move'; AutoSave.scheduleSave();
        });
        canvas.addEventListener('mouseleave', () => {
            if (isPanning) { isPanning = false; setCursor(); }
            isPainting = false; isErasing = false; isFogging = false;
            if (selectionRect) { selectionRect = null; }
            if (isDrawing) {
                isDrawing = false;
                if (currentTool === 'room' && currentRoom) { if (currentRoom.w > 0 && currentRoom.h > 0) State.rooms.push(currentRoom); currentRoom = null; }
                else if (currentTool === 'wall' && currentWall) { if (currentWall.x1 !== currentWall.x2 || currentWall.y1 !== currentWall.y2) State.internalWalls.push(currentWall); currentWall = null; }
                dragAction = 'move'; AutoSave.scheduleSave();
            }
        });
        canvas.addEventListener('wheel', e => {
            e.preventDefault();
            const rect = canvas.getBoundingClientRect(), mx = e.clientX - rect.left, my = e.clientY - rect.top;
            const wz = Math.exp((e.deltaY < 0 ? 1 : -1) * 0.1), ns = Math.max(0.2, Math.min(scale * wz, 5));
            panX = mx - (mx - panX) * (ns / scale); panY = my - (my - panY) * (ns / scale); scale = ns;
        }, { passive: false });

        // ─── INIT ────────────────────────────────────────────────────────────────────
        setTimeout(() => {
            const restored = AutoSave.load();
            if (!restored) {
                panX = width / 2 - 200; panY = height / 2 - 150;
                for (let i = 0; i < 8; i++) { State.terrainCells[`${4 + i},${4 + i}`] = { style: 'water', color: '#3b82f6' }; State.terrainCells[`${5 + i},${4 + i}`] = { style: 'water', color: '#3b82f6' }; }
                State.rooms.push({ x: 80, y: 80, w: 240, h: 160, style: 'cavern', fillColor: '#374151', wallColor: '#1a1520', wallWidth: 4, floorColor: '#0e0b14', opacity: 1, id: 1005, label: 'Caverna Inicial' });
                State.internalWalls.push({ x1: 200, y1: 80, x2: 200, y2: 240, wallColor: '#000000', wallWidth: 4 });
                State.textLabels.push({ type: 'label', content: 'Masmorra das Sombras', x: 60, y: 50, fontSize: 22, color: '#c4b5fd', bold: true, opacity: 1, id: 1 });
                addEmojiProp('\ud83d\udeb6'); State.props[0].x = 180; State.props[0].y = 140;
            }
            setTool('select'); buildLayersPanel(); initEmojiPicker();
        }, 100);

    </script>

    <footer class="rodape-principal">
        <div class="rodape-conteudo">
            <div class="rodape-logo-area">
                <div class="rodape-marca">
                    <img src="../img/logo_branco.png" alt="Logo TABLE">
                    <span>TABLE</span>
                </div>
                <p>Acompanhe uma experiência imersiva nos mundos de RPG. Aprenda e jogue com seus amigos!</p>
            </div>
            <div class="rodape-links">
                <h4>Navegação</h4>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="cm-jogar.php" class="ativo">Como Jogar</a></li>
                    <li><a
                            href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a>
                    </li>
                    <li><a href="criar-mapa.php">Mundos</a></li>
                    <li><a href="rolagem-de-dados.php">Dados</a></li>
                    <li><a href="sobre-nos.php">Sobre Nós</a></li>
                </ul>
            </div>
            <div class="rodape-links">
                <h4>Jogar</h4>
                <ul>
                    <li><a href="cm-jogador.php">Como Player</a></li>
                    <li><a href="cm-mestre.php">Como Mestre</a></li>
                    <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Campanhas</a>
                    </li>
                    <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Meu Perfil</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="rodape-inferior">
            <p>© 2026 TABLE. Todos os direitos reservados.</p>
            <div class="redes-sociais">
                <a href="#"><i class="fa-brands fa-discord"></i></a>
                <a href="#"><i class="fa-brands fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <script src="../js/script.js" defer></script>
    <script src="../js/nav-global.js" defer></script>

</body>

</html>

