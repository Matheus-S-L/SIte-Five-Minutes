<?php
include_once('INC/conexao.php');
session_start();

$mensagemErro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $emailLogin = trim($_POST['ds_email_usuario'] ?? '');
    $senhaLogin = $_POST['ds_senha_usuario'] ?? '';

    if (empty($emailLogin) || empty($senhaLogin)) {
        $mensagemErro = "Preencha todos os campos!";
    } else {
        try {
            $sql = "SELECT * FROM tb_usuarios WHERE ds_email_usuario = :email";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $emailLogin]);

            $usuario = $stmt->fetch();

            if ($usuario) {
                if (password_verify($senhaLogin, $usuario['ds_senha_usuario'])) {
                    
                    $_SESSION['usuario_id'] = $usuario['cd_usuario'];
                    $_SESSION['usuario_nome'] = $usuario['nm_usuario'];
                    $_SESSION['usuario_email'] = $usuario['ds_email_usuario'];
                    $_SESSION['usuario_foto'] = $usuario['ds_foto_perfil_usuario'] ?? '';

                    header('Location: TelaHome.php');
                    exit;

                } else {
                    $mensagemErro = "Senha incorreta!";
                }
            } else {
                $mensagemErro = "E-mail não encontrado!";
            }

        } catch (PDOException $e) {
            $mensagemErro = "Erro no banco de dados: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>5Minutes. - Entrar na conta</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Lora:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #fbf9f1;
            font-family: 'Inter', sans-serif;
            color: #2b2b2b;
        }

        .container-login {
            width: 100%;
            max-width: 380px;
            padding: 40px 32px;
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
            margin-bottom: 24px;
            text-align: center;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #4b4b4b;
            margin-bottom: 6px;
        }

        .form-control {
            padding: 11px 14px;
            font-size: 0.95rem;
            border: 1px solid #e5c65c;
            border-radius: 10px;
            background-color: #fdfbf7;
            margin-bottom: 16px;
            color: #2b2b2b;
        }

        .form-control:focus {
            background-color: #ffffff;
            border-color: #d9b443;
            box-shadow: 0 0 0 3px rgba(217, 180, 67, 0.15);
        }

        .btn-entrar {
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

        .btn-entrar:hover {
            background-color: #cca535;
            color: #111111;
            transform: translateY(-1px);
        }

        .voltar {
            display: block;
            text-align: center;
            margin-top: 18px;
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
            margin-bottom: 16px;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <div class="container-login">
        <div class="marca">5Minutes<span>.</span></div>
        <p class="subtitulo">Entre na sua conta para continuar focado.</p>

        <?php if (!empty($mensagemErro)): ?>
            <div class="alert alert-danger text-center"><?php echo $mensagemErro; ?></div>
        <?php endif; ?>

        <form action="" method="POST">

            <div class="mb-2">
                <label for="ds_email_usuario" class="form-label">E-mail cadastrado</label>
                <input type="email" class="form-control" id="ds_email_usuario" name="ds_email_usuario" placeholder="seu@email.com" required>
            </div>

            <div class="mb-3">
                <label for="ds_senha_usuario" class="form-label">Senha</label>
                <input type="password" class="form-control" id="ds_senha_usuario" name="ds_senha_usuario" placeholder="Sua senha" required>
            </div>

            <button type="submit" class="btn-entrar">Entrar</button>
        </form>

        <a href="index.html" class="voltar">← Voltar para o início</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>