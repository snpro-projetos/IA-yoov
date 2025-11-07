<?php
// conexaodb.php - Conexão com banco de dados MySQL local

$host = 'localhost';
$dbname = 'yoov_local';
$username = 'root'; // altere se necessário
$password = '';     // insira a senha do seu MySQL, se houver

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados MySQL: ' . $e->getMessage());
}
?>