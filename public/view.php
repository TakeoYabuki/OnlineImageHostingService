<?php

require_once __DIR__ . '/../MySQLWrapper.php';

if($_SERVER["REQUEST_METHOD"] !== "GET") {
    header("Allow: GET");
    http_response_code(405);
    exit("Method Not Allowed.");
}

$key = $_GET["key"] ?? null;

//キーのバリデーション
if (!is_string($key) || preg_match('/\A[0-9a-f]{32}\z/', $key) !== 1) {
    http_response_code(400);
    exit("無効なキーが指定されました。");
}


$db = null;
$fileHandle = null;

try {
    $db = new MySQLWrapper();

    //トランザクション開始
    $db->beginTransaction();

    //画像取得
    $stmt = $db->execute(
    "SELECT * FROM images WHERE unique_key = ?
     FOR UPDATE",
    [$key]
    );

    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($image === false) {
        $db->rollBack();
        http_response_code(404);
        exit("画像が存在しません。");
    }

    //ローカルストレージから画像の取得
    $filePath = $image["image_path"];

    if(!is_file($filePath)) {
        $db->rollBack();
        http_response_code(404);
        exit("画像が存在しません。");
    }

    if(!is_readable($filePath)) {
        $db->rollBack();
        http_response_code(500);
        exit("画像が読み込めません。");
    }
    //ファイルを開き、読み取り用のハンドルを取得
    $fileHandle = fopen($filePath, "rb");

    if($fileHandle === false) {
        $db->rollBack();
        http_response_code(500);
        exit("画像が読み込めません。");
    }

    //閲覧回数と最終アクセス日時を更新
    $db->execute(
      "UPDATE images 
      SET view_count = view_count + 1,
      last_accessed_at = CURRENT_TIMESTAMP 
      WHERE unique_key = ?",
      [$key]
    );

    // トランザクションの確定
    $db->commit();

} catch (PDOException $e) {
    error_log($e->getMessage());
    
    try {
        if ($db !== null &&$db->inTransaction()) {
            $db->rollBack();
        }
    } catch (PDOException $rollbackError) {
        error_log("ロールバックエラー: " . $rollbackError->getMessage());
    }

    if (is_resource($fileHandle)) {
        fclose($fileHandle);
    }

    http_response_code(500);
    exit("データベースでエラーが発生しました。");
}


//画像送信処理
$imageType = $image["mime_type"];

header("Content-Type: $imageType");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: no-store");
//ファイルの内容を読み取り、ブラウザへ送信
if (fpassthru($fileHandle) === false) {
    error_log("画像の読み込みに失敗しました: " . $filePath);
}

fclose($fileHandle);
exit;
