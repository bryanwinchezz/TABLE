<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../app/config/database.php';
$pdo = Database::getConexao();

// Foto do avatar do usuário ativo na barra de navegação
$fotoNavbar = '../img/uploads/perfil/avatar1.png';
if (isset($_SESSION['usuario'])) {
    $fotoUsuario = $_SESSION['usuario']['foto'] ?? '';
    if (!empty($fotoUsuario)) {
        $fotoNavbar = $fotoUsuario;
    }
}

// ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
// CONFIGURAÇÃO DOS METADADOS RICOS DOS SISTEMAS DE RPG (ESTÁTICO)
// ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
$metadadosColecoes = [
    'D&D 5E' => [
        'titulo' => 'Dungeons & Dragons 5E',
        'subtitulo' => 'O maior RPG de fantasia medieval do mundo',
        'descricao' => 'Aventure-se em mundos de monstros lendários, masmorras escuras e magia antiga. D&D 5ª Edição traz um sistema moderno de RPG focado em narrativa compartilhada e combates táticos refinados.',
        'tags' => ['Livro Base', 'Fantasia', 'D&D 5E', 'Magia', 'Aventura'],
        'produtor' => 'Wizards of the Coast',
        'lancamento' => '19/08/2014',
        'origem' => 'Estados Unidos',
        'jogadores' => '3 a 6 Jogadores',
        'complexidade' => 'Média',
        'classificacao' => 'A12',
        'paginas' => 320,
        'livro_principal_nome' => 'D&D 5E - Livro do Jogador (Fundo Colorido) - Biblioteca Élfica.pdf',
        'capa' => '../pdf/BibliotecaElfica/D&D 5E/ded5-capa.jpg',
        'cor' => '#ff1a1a'
    ],
    'GURPS' => [
        'titulo' => 'GURPS 3E / 4E',
        'subtitulo' => 'Generic Universal RolePlaying System',
        'descricao' => 'O Sistema de RPG Genérico e Universal permite que você jogue em qualquer cenário imaginável de forma realista ou cinematográfica. Use as mesmas regras para simular combates futuristas, feitiçaria medieval ou artes marciais intensas.',
        'tags' => ['Livro Base', 'Genérico', 'Universal', 'Simulação', 'Modular'],
        'produtor' => 'Steve Jackson Games',
        'lancamento' => '21/08/2004',
        'origem' => 'Estados Unidos',
        'jogadores' => '2 a 6 Jogadores',
        'complexidade' => 'Avançada',
        'classificacao' => 'A14',
        'paginas' => 274, // Páginas físicas reais do Módulo Básico GURPS 3E
        'livro_principal_nome' => 'GURPS 3E - Módulo Básico - Biblioteca Élfica.pdf',
        'capa' => '',
        'cor' => '#2980b9'
    ],
    'Brabo RPG' => [
        'titulo' => 'Brabo RPG',
        'subtitulo' => 'O RPG brutal e ultraveloz',
        'descricao' => 'Um sistema de regras minimalista projetado para sessões focadas na ação imediata e diversão. Ideal para one-shots dinâmicas e campanhas rápidas cheias de desafios colossais.',
        'tags' => ['Livro Base', 'Minimalista', 'Ação Rápida', 'Nacional'],
        'produtor' => 'Indie Nacional',
        'lancamento' => '08/10/2022',
        'origem' => 'Brasil',
        'jogadores' => '2 a 5 Jogadores',
        'complexidade' => 'Iniciante',
        'classificacao' => 'A12',
        'paginas' => 34, // Páginas físicas reais das regras
        'livro_principal_nome' => 'Brabo RPG 2.0 - Biblioteca Élfica.pdf',
        'capa' => '../pdf/BibliotecaElfica/Brabo RPG/brabo-rpg-capa.jpg',
        'cor' => '#d35400'
    ],
    'Ordem Paranormal' => [
        'titulo' => 'Outros',
        'subtitulo' => 'Investigue o Sobrenatural e Combata o Inexplicável',
        'descricao' => 'Em Outros de Ordem Paranormal, você assume o papel de um agente da Ordo Realitas em um mundo onde o medo e o sobrenatural espreitam nas sombras. Desvende mistérios, enfrente monstros paranormais e proteja a realidade.',
        'tags' => ['Livro Base', 'Investigação', 'Horror', 'Sobrenatural', 'Nacional'],
        'produtor' => 'Jambô Editora',
        'lancamento' => '15/09/2022',
        'origem' => 'Brasil',
        'jogadores' => '3 a 6 Jogadores',
        'complexidade' => 'Média',
        'classificacao' => 'A16',
        'paginas' => 320,
        'livro_principal_nome' => 'outros - ordem-paranormal.pdf',
        'livro_principal_titulo' => 'Outros',
        'capa' => '../pdf/BibliotecaElfica/Ordem Paranormal/ordem-outros-capa.jpg',
        'cor' => '#eebf12'
    ]
];

// Função auxiliar para formatar o tamanho dos arquivos
function formatarTamanhoBytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' Bytes';
    }
}

// ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
// FUNÇÃO DE LEITURA RECURSIVA PROFUNDA (PARA INCLUIR SUBPASTAS DE MAPAS/TOKENS)
// ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
function lerPastaDeRpgRecursivo($diretorio, $pastaPrincipal, &$arquivosEncontrados, &$capaFisica, $subpastaRelativa = '') {
    if (!is_dir($diretorio)) return;
    $itens = scandir($diretorio);
    
    foreach ($itens as $item) {
        if ($item === '.' || $item === '..' || strpos($item, '~') === 0 || strpos($item, '.') === 0) {
            continue;
        }
        
        $caminhoCompleto = $diretorio . '/' . $item;
        
        if (is_dir($caminhoCompleto)) {
            // Entra na subpasta recursivamente, acumulando o caminho relativo
            $novoCaminho = empty($subpastaRelativa) ? $item : $subpastaRelativa . '/' . $item;
            lerPastaDeRpgRecursivo($caminhoCompleto, $pastaPrincipal, $arquivosEncontrados, $capaFisica, $novoCaminho);
        } elseif (is_file($caminhoCompleto)) {
            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            
            // Suporta PDFs, Imagens (Fichas/Mapas/Tokens) e Arquivos Compactados
            if (in_array($ext, ['pdf', 'zip', 'rar', 'jpg', 'png', 'jpeg', 'gif'])) {
                
                // Mapeia se o arquivo de imagem serve de capa física (se contiver a palavra 'capa' ou similar)
                if (in_array($ext, ['jpg', 'png', 'jpeg'])) {
                    if (empty($capaFisica) && (stripos($item, 'capa') !== false || stripos($item, 'cover') !== false)) {
                        // Converte o caminho completo em relativo para o site
                        $capaFisica = str_replace(realpath(__DIR__ . '/../'), '..', realpath($caminhoCompleto));
                        $capaFisica = str_replace('\\', '/', $capaFisica);
                    }
                }
                
                // Monta o caminho relativo seguro para o download do arquivo a partir de pages/
                $caminhoRelativo = str_replace(realpath(__DIR__ . '/../'), '..', realpath($caminhoCompleto));
                $caminhoRelativo = str_replace('\\', '/', $caminhoRelativo);

                $arquivosEncontrados[] = [
                    'nome_real' => $item,
                    'caminho' => $caminhoRelativo,
                    'tamanho' => formatarTamanhoBytes(filesize($caminhoCompleto)),
                    'extensao' => $ext,
                    'pasta_relativa' => $subpastaRelativa
                ];
            }
        }
    }
}

// ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
// PROCESSAMENTO GERAL DAS COLEÇÕES
// ▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬
$caminhoBase = __DIR__ . '/../pdf/BibliotecaElfica';
$colecoes = [];
if (is_dir($caminhoBase)) {
    $pastas = scandir($caminhoBase);
    foreach ($pastas as $pasta) {
        if ($pasta === '.' || $pasta === '..' || !is_dir($caminhoBase . '/' . $pasta)) {
            continue;
        }

        $caminhoPasta = $caminhoBase . '/' . $pasta;
        $arquivosPdf = [];
        $capaFisicaPasta = '';

        // Executa a leitura recursiva incluindo todas as subpastas
        lerPastaDeRpgRecursivo($caminhoPasta, $pasta, $arquivosPdf, $capaFisicaPasta);

        if (empty($arquivosPdf)) {
            continue;
        }

        // Metadados iniciais da Coleção
        $chaveMetadados = $pasta;
        $livroPrincipalNome = '';
        
        $meta = [
            'titulo' => $pasta,
            'subtitulo' => 'Coleção de arquivos de RPG de ' . $pasta,
            'descricao' => 'Acesse livros base, fichas de personagens, aventuras e outros materiais de suporte para o sistema ' . $pasta . '.',
            'tags' => ['RPG', 'Livro', 'Fichas', 'Suplemento'],
            'produtor' => 'Editora Original / Comunidade',
            'lancamento' => 'Desconhecido',
            'origem' => 'Comunidade',
            'jogadores' => 'Qualquer número',
            'complexidade' => 'Média',
            'classificacao' => 'L',
            'paginas' => 'Várias',
            'capa' => $capaFisicaPasta,
            'cor' => '#9d7aff'
        ];

        if (isset($metadadosColecoes[$chaveMetadados])) {
            // Se a capa estiver declarada nos metadados ricos e existir, prioriza ela
            if (!empty($metadadosColecoes[$chaveMetadados]['capa'])) {
                $caminhoCapaMeta = $metadadosColecoes[$chaveMetadados]['capa'];
                $caminhoFisicoCapaMeta = __DIR__ . '/../' . ltrim(str_replace('..', '', $caminhoCapaMeta), '/');
                if (file_exists($caminhoFisicoCapaMeta)) {
                    $capaFisicaPasta = $caminhoCapaMeta;
                }
            }
            
            $meta = array_merge($meta, $metadadosColecoes[$chaveMetadados]);
            
            // Garante que o caminho da capa fique atualizado com o priorizado
            if (!empty($capaFisicaPasta)) {
                $meta['capa'] = $capaFisicaPasta;
            }
            $livroPrincipalNome = $meta['livro_principal_nome'] ?? '';
        }

        $livroPrincipalObj = null;
        $suplementos = [];

        // Identificar o livro principal de regras na coleção
        foreach ($arquivosPdf as $arqObj) {
            // Não incluir a própria capa do livro de Destaque como suplemento
            if (!empty($meta['capa']) && basename($meta['capa']) === $arqObj['nome_real']) {
                continue;
            }
            
            if (!empty($livroPrincipalNome) && $arqObj['nome_real'] === $livroPrincipalNome) {
                $livroPrincipalObj = $arqObj;
            } elseif (empty($livroPrincipalNome) && (stripos($arqObj['nome_real'], 'livro') !== false || stripos($arqObj['nome_real'], 'modulo') !== false || stripos($arqObj['nome_real'], 'basico') !== false || stripos($arqObj['nome_real'], 'regras') !== false || stripos($arqObj['nome_real'], '02') !== false || stripos($arqObj['nome_real'], '01') !== false)) {
                if ($livroPrincipalObj === null) {
                    $livroPrincipalObj = $arqObj;
                } else {
                    $suplementos[] = $arqObj;
                }
            } else {
                $suplementos[] = $arqObj;
            }
        }

        // Fallback se nenhum livro foi eleito principal
        if ($livroPrincipalObj === null && !empty($arquivosPdf)) {
            $livroPrincipalObj = $arquivosPdf[0];
            $suplementos = array_filter($suplementos, function($s) use ($livroPrincipalObj) {
                return $s['nome_real'] !== $livroPrincipalObj['nome_real'];
            });
            $suplementos = array_values($suplementos);
        }

        // Removemos a limpa de nomes amigáveis: O usuário quer ver o nome original de cada arquivo!
        $colecoes[] = [
            'pasta' => $pasta,
            'titulo' => $meta['titulo'],
            'subtitulo' => $meta['subtitulo'],
            'descricao' => $meta['descricao'],
            'tags' => $meta['tags'],
            'produtor' => $meta['produtor'],
            'lancamento' => $meta['lancamento'],
            'origem' => $meta['origem'],
            'jogadores' => $meta['jogadores'],
            'complexidade' => $meta['complexidade'],
            'classificacao' => $meta['classificacao'],
            'paginas' => $meta['paginas'],
            'capa' => $meta['capa'],
            'cor' => $meta['cor'],
            'livro_principal' => $livroPrincipalObj,
            'titulo_livro_principal' => !empty($meta['livro_principal_titulo']) ? $meta['livro_principal_titulo'] : ($livroPrincipalObj ? $livroPrincipalObj['nome_real'] : $pasta),
            'suplementos' => $suplementos
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLE | Biblioteca dos Antigos</title>
    <link rel="shortcut icon" href="../img/logo_branco1.png" type="image/x-icon">
    <!-- Tipografia premium Montserrat e Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <!-- FontAwesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Estilos padrão de layout global e da página -->
    <link rel="stylesheet" href="../css/nav-footer.css?v=1.4">
    <style>
        /* ============================================================ 
           DESIGN SYSTEM: PREMIUM DARK & GRID CATALOGO
           ============================================================ */
        body {
            background-color: #050209;
            color: #fff;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            position: relative;
        }

        /* Efeitos de Glow de Fundo */
        body::before {
            content: "";
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(123, 79, 247, 0.08) 0%, rgba(0, 0, 0, 0) 70%);
            top: -10%;
            left: -10%;
            z-index: 0;
            pointer-events: none;
        }

        body::after {
            content: "";
            position: absolute;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.06) 0%, rgba(0, 0, 0, 0) 70%);
            bottom: -10%;
            right: -10%;
            z-index: 0;
            pointer-events: none;
        }

        main {
            flex: 1;
            padding: 120px 4% 80px 4%;
            z-index: 1;
            position: relative;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

        .biblioteca-header {
            margin-bottom: 50px;
            position: relative;
        }

        .biblioteca-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 2.8rem;
            font-weight: 800;
            margin: 0 0 10px 0;
            background: linear-gradient(135deg, #fff 40%, #bca5ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .biblioteca-header p {
            font-size: 1.05rem;
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
            max-width: 700px;
            line-height: 1.6;
        }

        /* Grid do Catálogo */
        .catalogo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 35px 25px;
            width: 100%;
            margin-top: 20px;
        }

        /* Card de Livro */
        .livro-card {
            background: rgba(20, 15, 30, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 16px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .livro-card:hover {
            transform: translateY(-8px);
            border-color: var(--card-glow-color, rgba(157, 122, 255, 0.4));
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5), 0 0 20px var(--card-glow-color, rgba(157, 122, 255, 0.15));
            background: rgba(25, 20, 40, 0.65);
        }

        /* Capa Tridimensional Dinâmica ou Físicas */
        .capa-container {
            width: 100%;
            aspect-ratio: 1 / 1.4;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
            margin-bottom: 15px;
            background: #100b18;
            transition: transform 0.4s;
        }

        .livro-card:hover .capa-container {
            transform: scale(1.025);
        }

        .capa-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: filter 0.3s;
        }

        /* Capa CSS tridimensional para livros sem capa física */
        .capa-css {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 22px 18px;
            box-sizing: border-box;
            background: linear-gradient(135deg, var(--sist-cor-escuro), var(--sist-cor-claro));
            position: relative;
        }

        /* Simulação da Lombada do Livro */
        .capa-css::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 10px;
            height: 100%;
            background: linear-gradient(to right, rgba(0,0,0,0.35), rgba(255,255,255,0.06), rgba(0,0,0,0.1));
            border-right: 1px solid rgba(0, 0, 0, 0.2);
        }

        .capa-css-icone {
            font-size: 3.5rem;
            color: rgba(255, 255, 255, 0.2);
            text-align: center;
            margin: auto 0;
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.2));
        }

        .capa-css-titulo {
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.3;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
            margin: 0;
            text-align: left;
            padding-left: 5px;
        }

        /* Informações do Livro no Card */
        .livro-card-categoria {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--card-glow-color, #9d7aff);
            margin-bottom: 6px;
        }

        .livro-card-titulo {
            font-size: 0.95rem;
            font-weight: 700;
            color: #fff;
            margin: 0 0 10px 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 38px;
        }

        .livro-card-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.4);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding-top: 10px;
        }

        .livro-card-badge {
            background: rgba(255, 255, 255, 0.06);
            color: rgba(255, 255, 255, 0.85);
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.7rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* ============================================================ 
           MODAL DE DETALHES PREMIUM (GLASSMORPHISM)
           ============================================================ */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(3, 1, 7, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            padding: 20px;
            box-sizing: border-box;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .modal-overlay.ativo {
            display: flex;
            opacity: 1;
        }

        .modal-box {
            background: linear-gradient(135deg, rgba(15, 10, 25, 0.96), rgba(30, 20, 50, 0.96));
            border: 1px solid rgba(157, 122, 255, 0.18);
            border-radius: 24px;
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.8), 0 0 40px rgba(157, 122, 255, 0.1);
            transform: translateY(30px) scale(0.95);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.15);
            scrollbar-width: thin;
            scrollbar-color: rgba(157, 122, 255, 0.4) rgba(0,0,0,0.1);
        }

        .modal-box::-webkit-scrollbar {
            width: 8px;
        }
        .modal-box::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 0 24px 24px 0;
        }
        .modal-box::-webkit-scrollbar-thumb {
            background: rgba(157, 122, 255, 0.35);
            border-radius: 4px;
        }
        .modal-box::-webkit-scrollbar-thumb:hover {
            background: rgba(157, 122, 255, 0.5);
        }

        .modal-overlay.ativo .modal-box {
            transform: translateY(0) scale(1);
        }

        .modal-close-btn {
            position: absolute;
            top: 25px;
            right: 25px;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ccc;
            font-size: 1.2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 10;
        }

        .modal-close-btn:hover {
            color: #ff4d4d;
            background: rgba(255, 77, 77, 0.15);
            border-color: rgba(255, 77, 77, 0.25);
            transform: rotate(90deg);
        }

        /* Grid Interno do Modal */
        .modal-body {
            padding: 40px;
            display: flex;
            flex-direction: column;
            gap: 35px;
            box-sizing: border-box;
        }

        .modal-topo-secao {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 40px;
            align-items: start;
        }

        /* Capa no Modal */
        .modal-capa-wrapper {
            width: 100%;
            aspect-ratio: 1 / 1.4;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5), 0 0 25px rgba(157, 122, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.05);
            background: #110c1c;
        }

        .modal-capa-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Detalhes ao lado da capa */
        .modal-info-col {
            display: flex;
            flex-direction: column;
            height: 100%;
            justify-content: center;
        }

        .modal-produtora {
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--accent-color, #9d7aff);
            margin-bottom: 10px;
        }

        .modal-titulo {
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: #fff;
            margin: 0 0 8px 0;
            line-height: 1.2;
        }

        .modal-subtitulo {
            font-size: 1.1rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            margin: 0 0 18px 0;
        }

        .modal-tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 22px;
        }

        .modal-tag {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.7);
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modal-descricao {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            margin: 0 0 25px 0;
        }

        .modal-preco-rotulo {
            font-size: 1.5rem;
            font-weight: 900;
            color: #2ecc71;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-preco-rotulo span {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.4);
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Botões principais do Modal */
        .modal-botoes-grupo {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn-baixar-principal {
            background: #2ecc71;
            color: #fff !important;
            border: none;
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 6px 20px rgba(46, 204, 113, 0.35);
            text-decoration: none !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-baixar-principal:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 204, 113, 0.5);
        }

        .btn-online-principal {
            background: rgba(157, 122, 255, 0.12);
            color: #fff !important;
            border: 1px solid rgba(157, 122, 255, 0.35);
            padding: 14px 30px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-online-principal:hover {
            background: var(--accent-color, #9d7aff);
            border-color: var(--accent-color, #9d7aff);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(157, 122, 255, 0.3);
        }

        /* Seção de Fichas e Suplementos (Lista de arquivos extras) */
        .modal-fichas-secao {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 30px;
        }

        .modal-secao-titulo {
            font-family: 'Outfit', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            color: #fff;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-secao-titulo::before {
            content: "";
            width: 4px;
            height: 20px;
            background: var(--accent-color, #9d7aff);
            border-radius: 2px;
        }

        .fichas-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .ficha-item-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
            box-sizing: border-box;
            gap: 20px;
        }

        .ficha-item-card:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(157, 122, 255, 0.2);
            transform: translateX(4px);
        }

        .ficha-info-esquerda {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0; /* Essencial para que o flexbox permita o encolhimento de texto longo */
            flex: 1; /* Ocupa todo o espaço e empurra os botões para a direita */
            margin-right: 15px;
        }

        .ficha-icone {
            font-size: 1.4rem;
            color: var(--accent-color, #9d7aff);
            flex-shrink: 0;
        }

        /* O nome do arquivo cortará elegantemente com reticências (...) no meio do bloco */
        .ficha-nome {
            font-size: 0.95rem;
            font-weight: 700;
            color: #eee;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
            flex: 1; /* Ocupa o máximo possível permitindo truncar */
        }

        /* O tamanho do arquivo agora fica fixado ao lado sem risco de ser cortado */
        .ficha-tamanho {
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.4);
            font-weight: 650;
            flex-shrink: 0; /* Impede encolhimento */
            white-space: nowrap;
            margin-left: 10px;
        }

        .ficha-botoes-direita {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: nowrap; /* Garante que fiquem SEMPRE na mesma linha */
            flex-shrink: 0;
        }

        .btn-ficha-baixar {
            background: rgba(231, 76, 60, 0.12);
            border: 1px solid rgba(231, 76, 60, 0.25);
            color: #ff6b6b !important;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 0.72rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none !important;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .btn-ficha-baixar:hover {
            background: #e74c3c;
            color: #fff !important;
            border-color: #e74c3c;
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.4);
        }

        .btn-ficha-online {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #ccc !important;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 0.72rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none !important;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .btn-ficha-online:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #fff !important;
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* Estilização Premium para Agrupamentos de Subpastas no Modal */
        .modal-pasta-grupo {
            margin-bottom: 30px;
        }

        .modal-pasta-grupo:last-child {
            margin-bottom: 0;
        }

        .modal-pasta-header {
            font-family: 'Outfit', sans-serif;
            font-size: 1.02rem;
            font-weight: 800;
            color: #fff;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.04) 0%, rgba(255, 255, 255, 0) 100%);
            border-left: 3px solid var(--accent-color, #9d7aff);
            padding: 10px 16px;
            border-radius: 4px 12px 12px 4px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .modal-pasta-header i {
            color: var(--accent-color, #9d7aff);
            font-size: 1.15rem;
        }

        /* Seção inferior de Abas e Ficha Técnica */
        .modal-abas-secao {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 40px;
        }

        /* Navegação das Abas */
        .abas-nav {
            display: flex;
            gap: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .aba-tab-btn {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.9rem;
            font-weight: 800;
            padding: 8px 16px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            transition: color 0.3s;
            outline: none;
        }

        .aba-tab-btn.ativa {
            color: #fff;
        }

        .aba-tab-btn.ativa::after {
            content: "";
            position: absolute;
            bottom: -11px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--accent-color, #9d7aff);
            border-radius: 2px;
        }

        .aba-conteudo-box {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.7;
        }

        .aba-conteudo-painel {
            display: none;
            animation: fadeInSimple 0.4s ease;
        }

        .aba-conteudo-painel.ativo {
            display: block;
        }

        @keyframes fadeInSimple {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .credito-linha {
            margin-bottom: 12px;
            font-size: 0.9rem;
        }

        .credito-linha strong {
            color: #fff;
            margin-right: 5px;
        }

        /* Ficha Técnica Lateral (Cards da direita) */
        .ficha-tecnica-col {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .ficha-tecnica-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 22px;
        }

        .ficha-tecnica-card h4 {
            font-family: 'Outfit', sans-serif;
            font-size: 0.95rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 15px 0;
            color: #eee;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding-bottom: 8px;
        }

        .tecnica-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-size: 0.82rem;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.04);
            padding-bottom: 6px;
        }

        .tecnica-item:last-child {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }

        .tecnica-item label {
            color: rgba(255, 255, 255, 0.4);
            font-weight: 600;
        }

        .tecnica-item span {
            color: #eee;
            font-weight: 800;
            text-align: right;
        }

        /* Responsividade */
        @media (max-width: 900px) {
            .modal-topo-secao {
                grid-template-columns: 1fr;
                gap: 30px;
                justify-items: center;
            }

            .modal-capa-wrapper {
                max-width: 250px;
            }

            .modal-info-col {
                text-align: center;
                align-items: center;
            }

            .modal-tags-container,
            .modal-botoes-grupo {
                justify-content: center;
            }

            .modal-abas-secao {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        @media (max-width: 600px) {
            .biblioteca-header h1 {
                font-size: 2rem;
            }
            .modal-body {
                padding: 25px 20px;
            }
            .modal-titulo {
                font-size: 1.7rem;
            }
            .ficha-item-card {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .ficha-info-esquerda {
                flex-direction: column;
                gap: 8px;
                width: 100%;
                margin-right: 0;
            }
            .ficha-nome {
                width: 100%;
                text-align: center;
            }
            .ficha-tamanho {
                margin-left: 0;
                display: block;
                text-align: center;
            }
            .ficha-botoes-direita {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>

    <!-- HEADER / CABEÇALHO -->
    <header>
        <div class="logotipo">
            <a href="index.php"><img src="../img/logo_horizontal1.png" alt="Logo TABLE"></a>
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
                <li><a
                        href="<?= isset($_SESSION['usuario']['cargo']) && in_array(strtolower($_SESSION['usuario']['cargo']), ['mestre','admin']) ? 'criar-mapa.php' : 'editar-perfil.php?abrir_mestre=1'; ?>">Mundos</a>
                </li>
                <li><a href="rolagem-de-dados.php">Dados</a></li>
                <li><a href="sobre-nos.php">Sobre Nós</a></li>
            </ul>

            <!-- BOTÕES MOBILE -->
            <div class="nav-mobile-footer">
                <?php if (isset($_SESSION['usuario'])): ?>
                    <div class="usuario-logado-nav" onclick="window.location.href='perfil.php'">
                        <img src="<?= htmlspecialchars($fotoNavbar) ?>" alt="Avatar Navbar" class="avatar-nav">
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
                <img src="<?= htmlspecialchars($fotoNavbar) ?>" alt="Avatar Navbar" class="avatar-nav">
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

    <main>
        <div class="biblioteca-header">
            <h1>Biblioteca dos Antigos</h1>
            <p>Descubra compêndios lendários, regras básicas, aventuras completas e fichas de personagens de suporte para os seus sistemas favoritos. Baixe ou consulte os arquivos online diretamente.</p>
        </div>

        <!-- GRID DO CATÁLOGO DE RPG -->
        <div class="catalogo-grid">
            <?php foreach ($colecoes as $index => $col): 
                $corTema = $col['cor'];
                $hasCapaFisica = !empty($col['capa']) && file_exists($col['capa']);
                ?>
                <div class="livro-card" 
                     style="--card-glow-color: <?= $corTema ?>40;"
                     onclick="abrirModalLivro(<?= $index ?>)">
                    
                    <div class="capa-container">
                        <?php if ($hasCapaFisica): ?>
                            <img src="<?= htmlspecialchars($col['capa']) ?>" alt="<?= htmlspecialchars($col['titulo']) ?>" class="capa-img">
                        <?php else: 
                            // Capa dinâmica 3D via CSS baseada na cor do sistema
                            $corEscura = $corTema;
                            $corClara = adjustBrightness($corTema, 40);
                            ?>
                            <div class="capa-css" style="--sist-cor-escuro: <?= $corEscura ?>; --sist-cor-claro: <?= $corClara ?>;">
                                <i class="fas fa-book-open capa-css-icone"></i>
                                <h4 class="capa-css-titulo"><?= htmlspecialchars($col['titulo_livro_principal']) ?></h4>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="livro-card-categoria"><?= htmlspecialchars($col['pasta']) ?></div>
                    <h3 class="livro-card-titulo"><?= htmlspecialchars($col['titulo']) ?></h3>
                    
                    <div class="livro-card-footer">
                        <span><?= count($col['suplementos']) + 1 ?> arquivos</span>
                        <span class="livro-card-badge">PDF / IMG</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- ============================================================ 
         MODAL OVERLAY DE DETALHES DO LIVRO E SUPLEMENTOS
         ============================================================ -->
    <div class="modal-overlay" id="modal-detalhes-livro" onclick="fecharModalLivro(event)">
        <div class="modal-box">
            <button class="modal-close-btn" onclick="fecharModalLivro(null)"><i class="fas fa-times"></i></button>
            
            <div class="modal-body" id="modal-conteudo-dinamico">
                <!-- Preenchido via Javascript com transição fluida -->
            </div>
        </div>
    </div>

    <!-- FOOTER / RODAPÉ -->
    <footer class="rodape-principal">
        <div class="rodape-conteudo">
            <div class="rodape-logo-area">
                <div class="rodape-marca">
                    <img src="../img/logo_branco.png" alt="Logo TABLE">
                    <span>TABLE</span>
                </div>
                <p>Acompanhe uma experiência imersiva nos mundos de RPG. Jogue e customize fichas com seus amigos!</p>
            </div>
            <div class="rodape-links">
                <h4>Navegação</h4>
                <ul>
                    <li><a href="index.php">Início</a></li>
                    <li><a href="cm-jogar.php">Como Jogar</a></li>
                    <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Personagens</a></li>
                    <li><a href="<?= isset($_SESSION['usuario']['cargo']) && in_array(strtolower($_SESSION['usuario']['cargo']), ['mestre','admin']) ? 'criar-mapa.php' : 'editar-perfil.php?abrir_mestre=1'; ?>">Mundos</a></li>
                    <li><a href="rolagem-de-dados.php">Dados</a></li>
                    <li><a href="sobre-nos.php">Sobre Nós</a></li>
                </ul>
            </div>
            <div class="rodape-links">
                <h4>Jogar</h4>
                <ul>
                    <li><a href="cm-jogador.php">Como Player</a></li>
                    <li><a href="cm-mestre.php">Como Mestre</a></li>
                    <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Campanhas</a></li>
                    <li><a href="<?php echo isset($_SESSION['usuario']) ? 'perfil.php' : 'login.php'; ?>">Meu Perfil</a></li>
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

    <!-- IMPRESSÃO DOS DADOS DAS COLEÇÕES EM JSON -->
    <script id="colecoes-data" type="application/json">
        <?= json_encode($colecoes) ?>
    </script>

    <!-- SCRIPTS DE INTERATIVIDADE -->
    <script src="../js/script.js" defer></script>
    <script src="../js/nav-global.js" defer></script>
    <script>
        // Carrega os dados das coleções injetados pelo PHP
        const colecoes = JSON.parse(document.getElementById('colecoes-data').textContent);

        // Mapeamento das classificações indicativas padronizado do criar-sistema/editar-perfil
        const classInfo = {
            'L': { cor: '#27ae60', label: 'Livre para todos os públicos' },
            'Livre': { cor: '#27ae60', label: 'Livre para todos os públicos' },
            '10': { cor: '#2980b9', label: 'Maiores de 10 anos' },
            '12': { cor: '#f1c40f', label: 'Maiores de 12 anos' },
            'A12': { cor: '#f1c40f', label: 'Maiores de 12 anos' },
            '14': { cor: '#e67e22', label: 'Maiores de 14 anos' },
            'A14': { cor: '#e67e22', label: 'Maiores de 14 anos' },
            '16': { cor: '#c0392b', label: 'Maiores de 16 anos' },
            'A16': { cor: '#c0392b', label: 'Maiores de 16 anos' },
            '18': { cor: '#1a1a1a', label: 'Maiores de 18 anos' }
        };

        function abrirModalLivro(index) {
            const col = colecoes[index];
            const hasCapaFisica = col.capa !== '';
            
            // Gerador de Capa CSS dinâmica em JS caso não haja imagem física
            let capaHTML = '';
            if (hasCapaFisica) {
                capaHTML = `<img src="${col.capa}" alt="${col.titulo}">`;
            } else {
                capaHTML = `
                    <div class="capa-css" style="--sist-cor-escuro: ${col.cor}; --sist-cor-claro: ${adjustColorBrightness(col.cor, 40)}; height: 100%;">
                        <i class="fas fa-book-open capa-css-icone"></i>
                        <h4 class="capa-css-titulo">${col.titulo_livro_principal}</h4>
                    </div>
                `;
            }

            // Tags HTML
            let tagsHTML = '';
            col.tags.forEach(t => {
                tagsHTML += `<span class="modal-tag">${t}</span>`;
            });

            // Arquivos de apoio / Fichas HTML agrupados por subpasta
            let fichasHTML = '';
            if (col.suplementos.length > 0) {
                // Agrupa por pasta_relativa
                const grupos = {};
                col.suplementos.forEach(sup => {
                    const nomePasta = sup.pasta_relativa || 'Materiais Gerais';
                    if (!grupos[nomePasta]) {
                        grupos[nomePasta] = [];
                    }
                    grupos[nomePasta].push(sup);
                });

                // Ordenar para colocar 'Materiais Gerais' sempre primeiro
                const pastasOrdenadas = Object.keys(grupos).sort((a, b) => {
                    if (a === 'Materiais Gerais') return -1;
                    if (b === 'Materiais Gerais') return 1;
                    return a.localeCompare(b);
                });

                pastasOrdenadas.forEach(nomePasta => {
                    const arquivos = grupos[nomePasta];
                    fichasHTML += `
                        <div class="modal-pasta-grupo">
                            <div class="modal-pasta-header">
                                <i class="fas fa-folder-open"></i> ${nomePasta}
                            </div>
                            <div class="fichas-list">
                    `;

                    arquivos.forEach(sup => {
                        const visualizavel = ['pdf', 'jpg', 'png', 'jpeg', 'gif'].includes(sup.extensao);
                        let iconeClass = 'fa-file-pdf';
                        if (['zip', 'rar'].includes(sup.extensao)) {
                            iconeClass = 'fa-file-zipper';
                        } else if (['jpg', 'png', 'jpeg', 'gif'].includes(sup.extensao)) {
                            iconeClass = 'fa-file-image';
                        }

                        fichasHTML += `
                            <div class="ficha-item-card">
                                <div class="ficha-info-esquerda">
                                    <i class="fas ${iconeClass} ficha-icone"></i>
                                    <span class="ficha-nome" title="${sup.nome_real}">${sup.nome_real}</span>
                                    <span class="ficha-tamanho">(${sup.tamanho})</span>
                                </div>
                                <div class="ficha-botoes-direita">
                                    <a href="${sup.caminho}" download="${sup.nome_real}" class="btn-ficha-baixar">
                                        <i class="fas fa-arrow-down"></i> Baixar
                                    </a>
                                    ${visualizavel ? `
                                    <a href="${sup.caminho}" target="_blank" class="btn-ficha-online">
                                        <i class="fas fa-eye"></i> Ver Online
                                    </a>
                                    ` : ''}
                                </div>
                            </div>
                        `;
                    });

                    fichasHTML += `
                            </div>
                        </div>
                    `;
                });
            } else {
                fichasHTML = `<p style="opacity: 0.5; font-size: 0.9rem; text-align: center; padding: 10px 0;">Sem fichas ou materiais de apoio cadastrados para este sistema.</p>`;
            }

            // Botão principal do Livro de Destaque
            let botoesPrincipaisHTML = '';
            if (col.livro_principal) {
                botoesPrincipaisHTML = `
                    <a href="${col.livro_principal.caminho}" download="${col.livro_principal.nome_real}" class="btn-baixar-principal">
                        <i class="fas fa-arrow-down"></i> Baixar Livro <span style="opacity:0.75; font-size:0.8rem; margin-left:5px;">(${col.livro_principal.tamanho})</span>
                    </a>
                    <a href="${col.livro_principal.caminho}" target="_blank" class="btn-online-principal">
                        <i class="fas fa-eye"></i> Ver Online
                    </a>
                `;
            }

            // Obter Classificação Indicativa formatada do governo para a tabela lateral
            const classDet = classInfo[col.classificacao] || { cor: '#888', label: 'Classificação não informada' };
            const siglaClass = col.classificacao.replace('A', ''); // Ex: A16 vira 16

            const conteudo = `
                <!-- PARTE SUPERIOR 1: CAPA E METADADOS DO LIVRO PRINCIPAL -->
                <div class="modal-topo-secao" style="--accent-color: ${col.cor};">
                    <div class="modal-capa-wrapper">
                        ${capaHTML}
                    </div>
                    <div class="modal-info-col">
                        <div class="modal-produtora">${col.produtor}</div>
                        <h2 class="modal-titulo">${col.titulo}</h2>
                        <h3 class="modal-subtitulo">${col.subtitulo}</h3>
                        
                        <div class="modal-tags-container">
                            ${tagsHTML}
                        </div>
                        
                        <p class="modal-descricao">${col.descricao}</p>
                        
                        <div class="modal-preco-rotulo">
                            Grátis
                            <span>• Acesso Livre</span>
                        </div>
                        
                        <div class="modal-botoes-grupo">
                            ${botoesPrincipaisHTML}
                        </div>
                    </div>
                </div>

                <!-- PARTE SUPERIOR 2: SOBRE, CRÉDITOS E DETALHES TÉCNICOS -->
                <div class="modal-abas-secao" style="--accent-color: ${col.cor}; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 35px; margin-bottom: 10px;">
                    <div>
                        <div class="abas-nav">
                            <button class="aba-tab-btn ativa" onclick="alternarAbaModal('sobre', this)">Sobre o RPG</button>
                            <button class="aba-tab-btn" onclick="alternarAbaModal('creditos', this)">Créditos</button>
                        </div>
                        
                        <div class="aba-conteudo-box">
                            <div class="aba-conteudo-painel ativo" id="modal-aba-sobre">
                                <p>O sistema <strong>${col.titulo}</strong> oferece uma das experiências mais imersivas em termos de regras e cenário. Esta coleção foi curada para disponibilizar de forma inteiramente gratuita todos os materiais principais de apoio necessários para jogadores e mestres começarem suas campanhas imediatamente.</p>
                                <p>Fique à vontade para baixar os PDFs diretamente ou realizar a consulta online diretamente nas mesas físicas da TABLE RPG.</p>
                            </div>
                            
                            <div class="aba-conteudo-painel" id="modal-aba-creditos">
                                <div class="credito-linha"><strong>Editora/Produtor:</strong> ${col.produtor}</div>
                                <div class="credito-linha"><strong>Publicação Original:</strong> ${col.produtor} (${col.origem})</div>
                                <div class="credito-linha"><strong>Data de Lançamento:</strong> ${col.lancamento}</div>
                                <div class="credito-linha"><strong>Equipe de Produção:</strong> Autores originais, Tradutores da Comunidade de RPG e equipe técnica de diagramação.</div>
                                <div class="credito-linha"><strong>Disponibilização:</strong> Biblioteca dos Antigos. Todos os direitos reservados às respectivas marcas.</div>
                            </div>
                        </div>
                    </div>

                    <div class="ficha-tecnica-col">
                        <div class="ficha-tecnica-card">
                            <h4>Especificações</h4>
                            <div class="tecnica-item">
                                <label>Quantidade de páginas</label>
                                <span>${col.paginas}</span>
                            </div>
                            <div class="tecnica-item">
                                <label>Formato do arquivo</label>
                                <span>PDF / ZIP / IMG</span>
                            </div>
                            <div class="tecnica-item">
                                <label>Tamanho do Livro</label>
                                <span>${col.livro_principal ? col.livro_principal.tamanho : 'N/A'}</span>
                            </div>
                        </div>

                        <div class="ficha-tecnica-card">
                            <h4>Ficha do Sistema</h4>
                            <div class="tecnica-item">
                                <label>País de origem</label>
                                <span>${col.origem}</span>
                            </div>
                            <div class="tecnica-item">
                                <label>Recomendação</label>
                                <span>${col.jogadores}</span>
                            </div>
                            <div class="tecnica-item">
                                <label>Complexidade</label>
                                <span>${col.complexidade}</span>
                            </div>
                            <div class="tecnica-item">
                                <label>Classificação</label>
                                <span style="background: ${classDet.cor}; color: #fff; padding: 2px 6px; display: inline-flex; align-items: center; justify-content: center; font-weight: 850; border-radius: 4px; font-size: 0.72rem; border: 1px solid rgba(255,255,255,0.15); min-width: 22px; height: 22px; box-sizing: border-box; text-align: center;">${siglaClass}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PARTE INFERIOR: FICHAS E MATERIAIS DE APOIO -->
                <div class="modal-fichas-secao" style="--accent-color: ${col.cor};">
                    <h3 class="modal-secao-titulo">Fichas e Suplementos</h3>
                    <div class="fichas-list">
                        ${fichasHTML}
                    </div>
                </div>
            `;

            document.getElementById('modal-conteudo-dinamico').innerHTML = conteudo;
            document.getElementById('modal-detalhes-livro').classList.add('ativo');
            document.body.style.overflow = 'hidden'; // Impede o scroll de fundo
        }

        function fecharModalLivro(e) {
            // Fecha se clicou fora da modal-box ou se o evento for nulo (clique no botão fechar)
            if (e === null || e.target === document.getElementById('modal-detalhes-livro')) {
                document.getElementById('modal-detalhes-livro').classList.remove('ativo');
                document.body.style.overflow = ''; // Devolve o scroll de fundo
            }
        }

        function alternarAbaModal(aba, btn) {
            // Remove a classe ativa dos botões e painéis
            const botoes = btn.parentNode.querySelectorAll('.aba-tab-btn');
            botoes.forEach(b => b.classList.remove('ativa'));
            
            const paineis = btn.parentNode.parentNode.querySelectorAll('.aba-conteudo-painel');
            paineis.forEach(p => p.classList.remove('ativo'));

            // Ativa o botão clicado
            btn.classList.add('ativa');

            // Ativa o painel correto
            if (aba === 'sobre') {
                document.getElementById('modal-aba-sobre').classList.add('ativo');
            } else if (aba === 'creditos') {
                document.getElementById('modal-aba-creditos').classList.add('ativo');
            }
        }

        // Função utilitária para ajustar brilho de cor hexadecimal em JS (para criar o gradiente)
        function adjustColorBrightness(hex, percent) {
            let num = parseInt(hex.replace("#",""),16),
            amt = Math.round(2.55 * percent),
            R = (num >> 16) + amt,
            G = (num >> 8 & 0x00FF) + amt,
            B = (num & 0x0000FF) + amt;
            return "#" + (0x1000000 + (R<255?R<0?0:R:255)*0x10000 + (G<255?G<0?0:G:255)*0x100 + (B<255?B<0?0:B:255)).toString(16).slice(1);
        }
    </script>
</body>
</html>
<?php
// Função PHP utilitária para ajustar brilho no backend
function adjustBrightness($hex, $steps) {
    $steps = max(-255, min(255, $steps));
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));

    $r_hex = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
    $g_hex = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
    $b_hex = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);

    return '#' . $r_hex . $g_hex . $b_hex;
}
?>
