<?php
session_start();
require_once 'conexao.php';

$isRegister = isset($_GET['register']) && $_GET['register'] === 'true';
$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($isRegister) {
        if ($username === '' || $password === '') {
            $message = 'Usuário e senha são obrigatórios.';
            $messageClass = 'error';
        } else {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');

            try {
                $stmt->execute([$username, $passwordHash]);
                $message = 'Usuário cadastrado com sucesso! Faça login.';
                $messageClass = 'success';
                header('Location: index.php');
                exit();
            } catch (PDOException $e) {
                $message = 'Erro ao cadastrar usuário. Tente outro nome.';
                $messageClass = 'error';
            }
        }
    } else {
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: chat.php');
            exit();
        } else {
            $message = 'Usuário ou senha incorretos.';
            $messageClass = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yoov IA - <?php echo $isRegister ? 'Cadastro' : 'Login'; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h1>Yoov IA - <?php echo $isRegister ? 'Cadastro' : 'Acesso'; ?></h1>
        <form method="POST" action="index.php<?php echo $isRegister ? '?register=true' : ''; ?>">
            <label for="username">Usuário</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Senha</label>
            <input type="password" name="password" id="password" required>

            <button type="submit"><?php echo $isRegister ? 'Cadastrar' : 'Entrar'; ?></button>
            <?php if ($isRegister): ?>
                <p class="register-text"><a href="index.php">Voltar para o login</a></p>
            <?php else: ?>
                <p class="register-text">Não tem conta? <a href="index.php?register=true">Cadastre-se</a></p>
            <?php endif; ?>
        </form>
        <?php if ($message): ?>
            <p class="<?php echo htmlspecialchars($messageClass); ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
