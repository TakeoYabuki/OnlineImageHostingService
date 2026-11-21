<?php

require_once __DIR__ . '/vendor/autoload.php';

//.envを読み込む
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

class MySQLWrapper {
  
private PDO $pdo;

  public function __construct() {

    //SQLホストの作成
    $dsn = sprintf(
      "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
      $_ENV["DB_HOST"],
      $_ENV["DB_PORT"],
      $_ENV["DB_NAME"]
    );

    $this->pdo = new PDO(
      $dsn,
      $_ENV["DB_USER"],
      $_ENV["DB_PASSWORD"]
    );
  }

  public function execute(string $sql, array $params =[]) : PDOStatement {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt; 
  }

  public function beginTransaction(): void {
    $this->pdo->beginTransaction();
  }

  public function commit(): void {
    $this->pdo->commit();
  }

  public function rollBack(): void {
    $this->pdo->rollBack();
  }

  public function inTransaction(): bool {
    return $this->pdo->inTransaction();
  }
}