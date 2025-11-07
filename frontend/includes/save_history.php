<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Usuário não autenticado.';
    exit();
}

require_once __DIR__ . '/../conexao.php';

$question = trim((string)($_POST['question'] ?? ''));
$answer = trim((string)($_POST['answer'] ?? ''));

if ($question === '' || $answer === '') {
    http_response_code(400);
    echo 'Campos question e answer são obrigatórios.';
    exit();
}

$stmt = $db->prepare('INSERT INTO history (user_id, question, answer) VALUES (?, ?, ?)');
$stmt->execute([$_SESSION['user_id'], $question, $answer]);

echo 'ok';
