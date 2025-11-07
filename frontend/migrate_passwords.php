<?php
declare(strict_types=1);

require_once __DIR__ . '/conexao.php';

/**
 * Re-hash legacy plaintext passwords to bcrypt.
 *
 * Users that already have bcrypt hashes ($2y/$2a/$2b prefix) are skipped.
 */
$selectStmt = $db->query('SELECT id, username, password_hash FROM users');
$updateStmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');

$updated = 0;
$skipped = 0;

while ($row = $selectStmt->fetch(PDO::FETCH_ASSOC)) {
    $current = (string) $row['password_hash'];

    if ($current === '' || preg_match('/^\$2[aby]\$/', $current) === 1) {
        $skipped++;
        continue;
    }

    $newHash = password_hash($current, PASSWORD_BCRYPT);
    $updateStmt->execute([$newHash, $row['id']]);
    $updated++;
    echo "Atualizado: {$row['username']}\n";
}

echo "Total atualizados: {$updated}\n";
echo "Total ignorados: {$skipped}\n";
