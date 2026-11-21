
<?php
$key = $_GET["key"] ?? null;

if (!is_string($key) || preg_match('/\A[0-9a-f]{32}\z/', $key) !== 1) {
    http_response_code(400);
    exit("無効な画像キーです。");
}

header("Cache-Control: no-store");
header("Referrer-Policy: no-referrer");
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>画像の閲覧</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <main class="container">
        <h1>画像の閲覧</h1>
        <p class="subtitle">アップロードされた画像</p>

        <div class="image-preview">
            <img src="view.php?key=<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" alt="アップロードされた画像">
        </div>

        <a class="back-link" href="index.php">
            トップに戻る
        </a>
    </main>
</body>
</html>
