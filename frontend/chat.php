<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
require_once 'conexao.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yoov IA - Chat</title>
    <link rel="stylesheet" href="style.css">
    <script src="assets/js/main.js" defer></script>
</head>
<body>
    <div class="chat-container">
        <h1>Yoov IA - Chat</h1>
        <div class="chat-box" id="chatBox">
            <?php
            $stmt = $db->prepare('SELECT * FROM history WHERE user_id = ? ORDER BY created_at ASC');
            $stmt->execute([$_SESSION['user_id']]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($messages as $msg) {
                echo '<div class="chat-message user"><strong>VocÃª:</strong> ' . htmlspecialchars($msg['question']) . '</div>';
                echo '<div class="chat-message ai"><strong>IA:</strong> ' . htmlspecialchars($msg['answer']) . '</div>';
            }
            ?>
        </div>

        <form id="chatForm" class="chat-input">
            <input type="text" id="question" name="question" placeholder="Digite sua pergunta..." required>
            <button type="submit">Enviar</button>
        </form>
    </div>

    <script>
    document.getElementById('chatForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const question = document.getElementById('question').value.trim();
        if (!question) return;

        const chatBox = document.getElementById('chatBox');
        chatBox.innerHTML += `<div class='chat-message user'><strong>VocÃª:</strong> ${question}</div>`;
        document.getElementById('question').value = '';

        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question })
            });
            const data = await response.json();

            if (!response.ok || data.error) {
                throw new Error(data.error || 'Erro desconhecido.');
            }

            chatBox.innerHTML += `<div class='chat-message ai'><strong>IA:</strong> ${data.answer}</div>`;

            // Salva no histÃ³rico local via AJAX PHP
            fetch('includes/save_history.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `question=${encodeURIComponent(question)}&answer=${encodeURIComponent(data.answer)}`
            });
        } catch (error) {
            const message = (error && error.message) ? error.message : 'Falha na conexao com a IA.';
            chatBox.innerHTML += `<div class='chat-message ai error'><strong>Erro:</strong> ${message}</div>`;
        }

        chatBox.scrollTop = chatBox.scrollHeight;
    });
    </script>
</body>
</html>












