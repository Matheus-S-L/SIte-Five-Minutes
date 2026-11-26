<?php
include_once('INC/conexao.php');
session_start();

$mensagemErro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nomeUsuario = trim($_POST['nm_usuario'] ?? '');
    $emailUsuario = trim($_POST['ds_email_usuario'] ?? '');
    $senhaUsuario = $_POST['ds_senha_usuario'] ?? '';
    $materiaUsuario = trim($_POST['ds_materia_usuario'] ?? '');

    if (empty($nomeUsuario) || empty($emailUsuario) || empty($senhaUsuario) || empty($materiaUsuario)) {
        $mensagemErro = "Preencha todos os campos obrigatórios!";
    } else {
        try {
            $senhaHash = password_hash($senhaUsuario, PASSWORD_DEFAULT);

            $sql = "INSERT INTO tb_usuarios (nm_usuario, ds_email_usuario, ds_senha_usuario, ds_materia_usuario) VALUES (:nome, :email, :senha, :materia)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $nomeUsuario,
                ':email' => $emailUsuario,
                ':senha' => $senhaHash,
                ':materia' => $materiaUsuario
            ]);

            $idNovoUsuario = $pdo->lastInsertId();
            
            $_SESSION['usuario_id'] = $idNovoUsuario;
            $_SESSION['usuario_nome'] = $nomeUsuario;
            $_SESSION['usuario_email'] = $emailUsuario;

            header('Location: TelaHome.php');
            exit;

        } catch (PDOException $e) {
            $mensagemErro = "Erro ao cadastrar (E-mail já pode estar em uso): " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>5Minutes. - Criar Conta</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Lora:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #fbf9f1;
            font-family: 'Inter', sans-serif;
            color: #2b2b2b;
            padding: 20px 0;
        }

        .container-login {
            width: 100%;
            max-width: 400px;
            padding: 36px 32px;
            background-color: #ffffff;
            border: 1.5px solid #e5c65c;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 12px 30px rgba(217, 180, 67, 0.12);
        }

        .marca {
            font-family: 'Lora', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #2b2b2b;
            margin-bottom: 4px;
            text-align: center;
            letter-spacing: -0.5px;
        }

        .marca span {
            color: #d9b443;
        }

        .subtitulo {
            font-size: 0.88rem;
            color: #6b6b6b;
            margin-bottom: 22px;
            text-align: center;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #4b4b4b;
            margin-bottom: 4px;
        }

        .form-control {
            padding: 10px 14px;
            font-size: 0.92rem;
            border: 1px solid #e5c65c;
            border-radius: 10px;
            background-color: #fdfbf7;
            margin-bottom: 14px;
            color: #2b2b2b;
        }

        .form-control:focus {
            background-color: #ffffff;
            border-color: #d9b443;
            box-shadow: 0 0 0 3px rgba(217, 180, 67, 0.15);
        }

        .btn-cadastrar {
            width: 100%;
            padding: 12px 16px;
            background-color: #d9b443; 
            color: #1f1f1f;
            font-weight: 600;
            font-size: 0.95rem;
            border: 1px solid #c49f32;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 4px;
        }

        .btn-cadastrar:hover {
            background-color: #cca535;
            color: #111111;
            transform: translateY(-1px);
        }

        .voltar {
            display: block;
            text-align: center;
            margin-top: 16px;
            font-size: 0.85rem;
            color: #6b6b6b;
            text-decoration: none;
            transition: color 0.2s;
        }

        .voltar:hover {
            color: #d9b443;
            text-decoration: underline;
        }

        .alert-danger {
            font-size: 0.85rem;
            padding: 8px;
            margin-bottom: 14px;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <div class="container-login">
        <div class="marca">5Minutes<span>.</span></div>
        <p class="subtitulo">Crie sua conta e comece a focar melhor hoje.</p>

        <?php if (!empty($mensagemErro)): ?>
            <div class="alert alert-danger text-center"><?php echo $mensagemErro; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            
            <div class="mb-1">
                <label for="nm_usuario" class="form-label">Nome de usuário</label>
                <input type="text" class="form-control" id="nm_usuario" name="nm_usuario" placeholder="Ex: Maria Silva" required>
            </div>

            <div class="mb-1">
                <label for="ds_email_usuario" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="ds_email_usuario" name="ds_email_usuario" placeholder="seu@email.com" required>
            </div>

            <div class="mb-1">
                <label for="ds_senha_usuario" class="form-label">Senha</label>
                <input type="password" class="form-control" id="ds_senha_usuario" name="ds_senha_usuario" placeholder="Sua senha secreta" required>
            </div>

            <div class="mb-1">
                <label for="ds_materia_usuario" class="form-label">Matéria de foco principal</label>
                <input type="text" class="form-control" id="ds_materia_usuario" name="ds_materia_usuario" placeholder="Ex: Matemática, Programação..." required>
            </div>

            <button type="submit" class="btn-cadastrar">Cadastrar</button>
        </form>

        <a href="index.html" class="voltar">← Voltar para o início</a>
    </div>   

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>