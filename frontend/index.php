<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yoov IA - Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h1>Yoov IA - Acesso</h1>
        <form method="POST" action="index.php">
            <label for="username">Usuário</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Senha</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Entrar</button>
            <p class="register-text">Não tem conta? <a href="index.php?register=true">Cadastre-se</a></p>
        </form>

        <?php
        session_start();
        include_once 'includes/db.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);

            $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: chat.php');
                exit();
            } else {
                echo '<p class="error">Usuário ou senha incorretos.</p>';
            }
        }

        if (isset($_GET['register']) && $_GET['register'] === 'true') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $username = trim($_POST['username']);
                $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
                $stmt = $db->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
                $stmt->execute([$username, $password]);
                echo '<p class="success">Usuário cadastrado com sucesso! Faça login.</p>';
            }
        }
        ?>
    </div>
</body>
</html>
