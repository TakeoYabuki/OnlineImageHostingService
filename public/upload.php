<?php

require_once __DIR__ . '/../MySQLWrapper.php';

if($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");
    http_response_code(405);
    exit("Method Not Allowed.");
}

$formatType = [
    "image/jpeg" => ".jpg",
    "image/png" => ".png",
    "image/gif" => ".gif",
];

if (!isset($_FILES["image"])) {
    http_response_code(400);
    exit("画像をアップロードしてください。");
}

//画像の容量を確認(PHP側)
if ($_FILES["image"]["error"] === UPLOAD_ERR_INI_SIZE) {
    http_response_code(413);
    exit("画像の容量が大きすぎます。");
}


if($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit("画像のアップロードに失敗しました。");
}

$maxSize = 5 * 1024 * 1024;
$fileSize = $_FILES["image"]["size"];

//容量の確認(app側)
if($fileSize <= 0) {
    http_response_code(400);
    exit("画像が確認できません。");
}

if($fileSize > $maxSize) {
    http_response_code(413);
    exit("画像の容量が大きすぎます。");
}

//有効なファイルか確認
$finfo = new finfo(FILEINFO_MIME_TYPE);

$mimeType = $finfo->file($_FILES["image"]["tmp_name"]);

if ($mimeType === false || !isset($formatType[$mimeType])) {
    http_response_code(415);
    exit("無効な形式が指定されました。");
}

//アクセス回数と総容量の制限
$maxUploads = 10;
$maxTotalSize = 20 * 1024 * 1024;
$ipAddress = $_SERVER["REMOTE_ADDR"];



//Lock(同時接続防止)用の変数
$lockName = "upload:" . $ipAddress;

$lockAcquired = false;
$fileSaved = false;
$db = null;

//アクセス回数と総容量の履歴を取得
try {
    $db = new MySQLWrapper();

    //同じIPアドレスからの同時接続を防止
    $lockStmt = $db->execute(
        "SELECT GET_LOCK(?, 5) AS lock_result",
        [$lockName]
    );

    $lockRow = $lockStmt->fetch(PDO::FETCH_ASSOC);
    $lockResult = $lockRow["lock_result"];

    if((int)$lockResult !== 1) {
        http_response_code(503);
        echo "時間を置いて再度接続してください";
        return;
    }

    $lockAcquired = true;

    //アクセク回数とファイルサイズの総量を取得
    $stmt = $db->execute(
        "SELECT COUNT(*) AS uploaded_count, 
        COALESCE(SUM(file_size), 0) AS total_size
        FROM upload_logs WHERE ip_address = ? AND uploaded_at >= NOW() - INTERVAL 1 HOUR",
        [$ipAddress]
    );

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $uploadedCount = $row["uploaded_count"];
    $totalSize = $row["total_size"];

    $currentTotalSize = $totalSize + $fileSize;
    //アクセス回数と総容量の超過確認
    if ($currentTotalSize > $maxTotalSize || $uploadedCount >= $maxUploads) {
        http_response_code(429);
        echo "ファイルの合計容量またはアップロード回数が上限に達した為アップロード出来ません。";
        return;
    }

    //キーを生成。
    $uniqueKey = bin2hex(random_bytes(16));
    $deleteKey = bin2hex(random_bytes(32));

    $uploadDir = __DIR__ . '/../storage/images';

    $format = $formatType[$mimeType];

    //ファイル名とファイルパスを指定
    $fileName = $uniqueKey . $format;
    $filePath = "$uploadDir/$fileName";

    //ローカルストレージにファイルを保存
    if(!move_uploaded_file($_FILES["image"]["tmp_name"], $filePath)) {
        http_response_code(500);
        echo "ファイルをローカルストレージに保存できませんでした。";
        return;
    }

    $fileSaved = true;

    //トランザクション開始
    $db->beginTransaction();

    $db->execute(
        "INSERT INTO images (unique_key, delete_key, image_path, mime_type, file_size)
        VALUES (?, ?, ?, ?, ?)",
        [$uniqueKey, $deleteKey, $filePath, $mimeType, $fileSize]
    );

     $db->execute(
        "INSERT INTO upload_logs (ip_address, file_size)
        VALUES (?, ?)",
        [$ipAddress, $fileSize]
    );
    // トランザクションの確定
    $db->commit();

} catch (PDOException $e) {
    //トランザクション中ならロールバック
    try {
        if ($db !== null && $db->inTransaction()) {
            $db->rollBack();
        }
    } catch (PDOException $rollbackError) {
        error_log("ロールバックエラー: " . $rollbackError->getMessage());
    }

    //ＤBの更新が失敗した場合ローカルストレージから画像を削除
    if ($fileSaved && !unlink($filePath)) {
        error_log("画像の削除に失敗しました。: " . $filePath);
    }

    error_log($e->getMessage());
    http_response_code(500);
    echo ("データベースエラーが発生しました。画像が保存出来ませんでした。");
    return;

} finally {
    if($lockAcquired) {
        try {
            $releaseStmt= $db->execute(
                "SELECT RELEASE_LOCK(?) AS release_result",
                [$lockName]
            );

            $releaseRow = $releaseStmt->fetch(PDO::FETCH_ASSOC);
            $releaseResult = $releaseRow["release_result"];
            
            if((int)$releaseResult !==1) {
                error_log("ロックの解放に失敗しました: " . $lockName);
            }

        } catch (PDOException $e) {
            error_log("ロック解放エラー: " . $e->getMessage());
        }
    }
}


$data = ['success' => true,
        'view_url' => 'http://localhost:8000/viewer.php?key=' . $uniqueKey,
        'delete_url' => 'http://localhost:8000/delete.php?key=' . $deleteKey
        ];
        
header("Content-Type: application/json; charset=utf-8");

$json = json_encode($data);

echo  $json;