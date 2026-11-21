<?php
require_once __DIR__ . '/../MySQLWrapper.php';

if($_SERVER["REQUEST_METHOD"] !== "GET" && ($_SERVER["REQUEST_METHOD"] !== "POST")) {
    header("Allow: GET, POST");
    http_response_code(405);
    exit("Method Not Allowed.");
}

$key = $_SERVER["REQUEST_METHOD"] === "GET" ?( $_GET["key"] ?? null) : ($_POST["key"] ?? null);

//キーのバリデーション
if (!is_string($key) || preg_match('/\A[0-9a-f]{64}\z/', $key) !== 1) {
    http_response_code(400);
    exit("無効なキーが指定されました。");
}

// GET・POST共通
$db = new MySQLWrapper();

//削除する画像を取得する(GET工程)
if($_SERVER["REQUEST_METHOD"] === "GET") {

    try {
        $stmt = $db->execute(
        "SELECT * FROM images WHERE delete_key = ?",
        [$key]
        );

        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if($image === false) {
        http_response_code(404);
        exit("画像が存在しません。");
        }

    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        exit("データベースでエラーが発生しました。");
    }

    header("Cache-Control: no-store");
    header("Referrer-Policy: no-referrer");
?>

    <!DOCTYPE html>
    <html lang="ja">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>画像の削除</title>
            <link rel="stylesheet" href="css/style.css">
        </head>
        <body>
            <main class="container">
                <h1>画像の削除</h1>

                <div class="delete-warning">
                    <h2>本当に削除しますか？</h2>
                    <p>削除した画像は復元できません。</p>
                </div>

                <form action="delete.php" method="POST">
                    <input type="hidden" name="key" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="delete-button">
                        画像を削除する
                    </button>
                </form>

                <a class="back-link" href="index.php">キャンセルしてトップに戻る</a>
            </main>
        </body>
    </html>

<?php
    exit;
}

//これ以降はPOST工程
try{
    // トランザクション開始
    $db->beginTransaction();

    // 対象画像をロックして再取得
    $stmt = $db->execute(
        "SELECT * FROM images WHERE delete_key = ? FOR UPDATE",
        [$key]
    );

    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($image === false) {
        $db->rollBack();
        http_response_code(404);
        exit("画像が存在しません。");
    }

    header("Cache-Control: no-store");
    header("Referrer-Policy: no-referrer");

    $filePath = $image["image_path"];

    //ファイルが既に存在しない場合も、DBレコードの削除は実行する
    if(is_file($filePath) && !unlink($filePath)) {
        throw new RuntimeException("画像ファイルの削除に失敗しました。");
    }

    $db->execute(
        "DELETE FROM images WHERE delete_key = ?",
        [$key]
    );

    // トランザクションの確定
    $db->commit();

} catch (PDOException | RuntimeException $e) {
    error_log($e->getMessage());

    try {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
    } catch (PDOException $rollbackError) {
        error_log("ロールバックエラー: " . $rollbackError->getMessage());
    }

    http_response_code(500);
    exit("データベースでエラーが発生しました。");
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>削除完了</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main class="container">
        <h1>削除完了</h1>

        <div class="success-message">
            画像を削除しました。
        </div>

        <a class="back-link" href="index.php">
            トップに戻る
        </a>
    </main>
</body>
</html>