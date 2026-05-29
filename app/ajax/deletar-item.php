<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;
$tipo = $data['tipo'] ?? null;
$id_usuario = $_SESSION['usuario']['id'];

if (!$id || !$tipo) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
    exit;
}

try {
    $pdo = Database::getConexao();

    if ($tipo === 'personagem') {
        // Verifica se o personagem pertence ao usuário
        $stmt = $pdo->prepare("SELECT id_usuario FROM tb_personagem WHERE id_personagem = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch();

        if ($res && $res['id_usuario'] == $id_usuario) {
            // Inicia uma transação para garantir que ou apaga tudo ou nada
            $pdo->beginTransaction();

            // Limpa dependências reais do personagem conforme o SQL
            $pdo->prepare("DELETE FROM tb_personagem_atributo WHERE id_personagem = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_personagem_status WHERE id_personagem = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_personagem_pericia WHERE id_personagem = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_habilidade_personagem WHERE id_personagem = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_personagem_classe WHERE id_personagem = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_personagem_origem WHERE id_personagem = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_personagem_item WHERE id_personagem = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_campanha_personagem WHERE id_personagem = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_rolagem_dado WHERE id_personagem = ?")->execute([$id]);

            // Agora sim, deleta o personagem
            $stmtDel = $pdo->prepare("DELETE FROM tb_personagem WHERE id_personagem = ?");
            $stmtDel->execute([$id]);

            $pdo->commit();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Você não tem permissão para deletar este personagem.']);
        }
    } 
    elseif ($tipo === 'campanha') {
        // Verifica se o usuário é o mestre da campanha
        $stmt = $pdo->prepare("SELECT id_usuario_mestre FROM tb_campanha WHERE id_campanha = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch();

        if ($res && $res['id_usuario_mestre'] == $id_usuario) {
            $pdo->beginTransaction();

            // Limpa dependências da campanha
            $pdo->prepare("DELETE FROM tb_convite_campanha WHERE id_campanha = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_rolagem_dado WHERE id_campanha = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_campanha_personagem WHERE id_campanha = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_campanha_usuario WHERE id_campanha = ?")->execute([$id]);
            
            // Combates e suas dependências
            $stmtCombates = $pdo->prepare("SELECT id_combate FROM tb_combate WHERE id_campanha = ?");
            $stmtCombates->execute([$id]);
            while($row = $stmtCombates->fetch()) {
                $pdo->prepare("DELETE FROM tb_combate_monstro WHERE id_combate = ?")->execute([$row['id_combate']]);
            }
            $pdo->prepare("DELETE FROM tb_combate WHERE id_campanha = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_sessao WHERE id_campanha = ?")->execute([$id]);

            $stmtDel = $pdo->prepare("DELETE FROM tb_campanha WHERE id_campanha = ?");
            $stmtDel->execute([$id]);

            $pdo->commit();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Apenas o mestre pode deletar a campanha.']);
        }
    } 
    elseif ($tipo === 'sistema') {
        // Verifica se o sistema pertence ao usuário ou se ele é admin
        $stmt = $pdo->prepare("SELECT id_usuario_criador FROM tb_sistema WHERE id_sistema = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch();

        $isAdmin = (isset($_SESSION['usuario']['cargo']) && strtolower($_SESSION['usuario']['cargo']) === 'admin');

        if ($res && ($res['id_usuario_criador'] == $id_usuario || $isAdmin)) {
            $pdo->beginTransaction();

            // 1. Apagar TODOS OS PERSONAGENS de QUALQUER USUÁRIO vinculados a este sistema
            $stmtPers = $pdo->prepare("SELECT id_personagem FROM tb_personagem WHERE id_sistema = ?");
            $stmtPers->execute([$id]);
            $personagens = $stmtPers->fetchAll(PDO::FETCH_COLUMN);

            foreach ($personagens as $id_pers) {
                $pdo->prepare("DELETE FROM tb_personagem_atributo WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_personagem_status WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_personagem_pericia WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_habilidade_personagem WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_personagem_classe WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_personagem_origem WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_personagem_item WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_campanha_personagem WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_rolagem_dado WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_personagem WHERE id_personagem = ?")->execute([$id_pers]);
            }

            // 2. Apagar TODAS AS CAMPANHAS vinculadas a este sistema
            $stmtCamp = $pdo->prepare("SELECT id_campanha FROM tb_campanha WHERE id_sistema = ?");
            $stmtCamp->execute([$id]);
            $campanhas = $stmtCamp->fetchAll(PDO::FETCH_COLUMN);

            foreach ($campanhas as $id_camp) {
                $pdo->prepare("DELETE FROM tb_convite_campanha WHERE id_campanha = ?")->execute([$id_camp]);
                $pdo->prepare("DELETE FROM tb_rolagem_dado WHERE id_campanha = ?")->execute([$id_camp]);
                $pdo->prepare("DELETE FROM tb_campanha_personagem WHERE id_campanha = ?")->execute([$id_camp]);
                $pdo->prepare("DELETE FROM tb_campanha_usuario WHERE id_campanha = ?")->execute([$id_camp]);
                
                $stmtCombates = $pdo->prepare("SELECT id_combate FROM tb_combate WHERE id_campanha = ?");
                $stmtCombates->execute([$id_camp]);
                while($row = $stmtCombates->fetch()) {
                    $pdo->prepare("DELETE FROM tb_combate_monstro WHERE id_combate = ?")->execute([$row['id_combate']]);
                }
                $pdo->prepare("DELETE FROM tb_combate WHERE id_campanha = ?")->execute([$id_camp]);
                $pdo->prepare("DELETE FROM tb_sessao WHERE id_campanha = ?")->execute([$id_camp]);
                $pdo->prepare("DELETE FROM tb_campanha WHERE id_campanha = ?")->execute([$id_camp]);
            }

            // 3. Deleta todos os vínculos em tb_usuario_sistema
            $pdo->prepare("DELETE FROM tb_usuario_sistema WHERE id_sistema = ?")->execute([$id]);

            // 4. Deleta todos os sistemas importados antigos deste original (legado)
            $pdo->prepare("DELETE FROM tb_sistema WHERE id_sistema_original = ?")->execute([$id]);

            // 5. Deleta os componentes do sistema (cascade manual)
            $pdo->prepare("DELETE FROM tb_atributo WHERE id_sistema = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_origem WHERE id_sistema = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_classe WHERE id_sistema = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_pericia WHERE id_sistema = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_monstro WHERE id_sistema = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_item WHERE id_sistema = ?")->execute([$id]);

            // 6. Finalmente, deleta o sistema em si
            $stmtDel = $pdo->prepare("DELETE FROM tb_sistema WHERE id_sistema = ?");
            $stmtDel->execute([$id]);

            $pdo->commit();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Você não tem permissão para deletar este sistema.']);
        }
    }
    elseif ($tipo === 'sistema_vinculo') {
        // Verifica se o usuário tem o sistema vinculado
        $stmt = $pdo->prepare("SELECT 1 FROM tb_usuario_sistema WHERE id_usuario = ? AND id_sistema = ?");
        $stmt->execute([$id_usuario, $id]);
        
        if ($stmt->fetch()) {
            $pdo->beginTransaction();

            // 1. Apagar todos os personagens DESTE USUÁRIO que pertencem a este sistema
            $stmtPers = $pdo->prepare("SELECT id_personagem FROM tb_personagem WHERE id_usuario = ? AND id_sistema = ?");
            $stmtPers->execute([$id_usuario, $id]);
            $personagens = $stmtPers->fetchAll(PDO::FETCH_COLUMN);

            foreach ($personagens as $id_pers) {
                $pdo->prepare("DELETE FROM tb_personagem_atributo WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_personagem_status WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_personagem_pericia WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_habilidade_personagem WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_personagem_classe WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_personagem_origem WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_personagem_item WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_campanha_personagem WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_rolagem_dado WHERE id_personagem = ?")->execute([$id_pers]);
                $pdo->prepare("DELETE FROM tb_personagem WHERE id_personagem = ?")->execute([$id_pers]);
            }

            // 2. Apagar todas as campanhas DESTE USUÁRIO que usam este sistema
            $stmtCamp = $pdo->prepare("SELECT id_campanha FROM tb_campanha WHERE id_usuario_mestre = ? AND id_sistema = ?");
            $stmtCamp->execute([$id_usuario, $id]);
            $campanhas = $stmtCamp->fetchAll(PDO::FETCH_COLUMN);

            foreach ($campanhas as $id_camp) {
                $pdo->prepare("DELETE FROM tb_convite_campanha WHERE id_campanha = ?")->execute([$id_camp]);
                $pdo->prepare("DELETE FROM tb_rolagem_dado WHERE id_campanha = ?")->execute([$id_camp]);
                $pdo->prepare("DELETE FROM tb_campanha_personagem WHERE id_campanha = ?")->execute([$id_camp]);
                $pdo->prepare("DELETE FROM tb_campanha_usuario WHERE id_campanha = ?")->execute([$id_camp]);
                
                $stmtCombates = $pdo->prepare("SELECT id_combate FROM tb_combate WHERE id_campanha = ?");
                $stmtCombates->execute([$id_camp]);
                while($row = $stmtCombates->fetch()) {
                    $pdo->prepare("DELETE FROM tb_combate_monstro WHERE id_combate = ?")->execute([$row['id_combate']]);
                }
                $pdo->prepare("DELETE FROM tb_combate WHERE id_campanha = ?")->execute([$id_camp]);
                $pdo->prepare("DELETE FROM tb_sessao WHERE id_campanha = ?")->execute([$id_camp]);
                $pdo->prepare("DELETE FROM tb_campanha WHERE id_campanha = ?")->execute([$id_camp]);
            }

            // 3. Remover o vínculo do sistema
            $pdo->prepare("DELETE FROM tb_usuario_sistema WHERE id_usuario = ? AND id_sistema = ?")->execute([$id_usuario, $id]);

            $pdo->commit();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Você não possui este sistema vinculado.']);
        }
    }
    else {
        echo json_encode(['success' => false, 'error' => 'Tipo de item desconhecido.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro no servidor: ' . $e->getMessage()]);
}

