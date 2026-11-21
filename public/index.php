<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Online Image Hosting Service</title>
        <link rel="stylesheet" href="css/style.css">
    </head>

    <body>
        <main class="container">

            <h1>Image Hosting Service</h1>
            <p class="subtitle">画像を共有しましょう。</p>

            <form id="form" action="upload.php" method="POST" enctype="multipart/form-data">
 
              <input type="file" name="image" accept="image/jpeg,image/png,image/gif" required>

                <button type="submit" id="submit">Upload Image</button>

            </form>

            <div id="result" hidden>
                <p id="message"></p>

                <p>閲覧用URL</p> 
                <a id="view-url" href=""></a>
                <button type="button" id="copy-view" disabled>コピー</button>

                <p>削除用URL</p>
                <a id="delete-url" href=""></a>
                <button type="button" id="copy-delete" disabled>コピー</button>
            </div>

        </main>
        <script src="js/script.js"></script>
    </body>
</html>
