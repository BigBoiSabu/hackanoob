<?php


$dsn = 'mysql:host='+$_ENV['DB_HOST']+';port=3306;dbname='+$_ENV['DB_NAME'];
$dbUser = $_ENV['DB_USER'];
$dbPass = $_ENV['DB_PASS'];
$dbOptions = array(
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_CASE => PDO::CASE_LOWER
);

        if(!isset($_SESSION['PDO'])){
            $_SESSION['PDO'] = 0;
        }
        
        $_SESSION['PDO']++;

try {
    //$pdo = new PDO($dsn, $dbUser, $dbPass, $dbOptions);
    
} catch (PDOException $e) {
    die('Erro ao conectar ao banco de dados');
}

?>
