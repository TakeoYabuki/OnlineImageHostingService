<?php
require_once __DIR__ . '/../MySQLWrapper.php';

//有効期限(３０日)が失効した画像を取得
try{
    $db = new MySQLWrapper();

    $stmt = $db->execute(
        "SELECT * FROM images 
        WHERE last_accessed_at <= now() - INTERVAL 30 DAY"
    );

    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($images as $image) {
        try {
            //トランザクション開始
            $db->beginTransaction();
            //削除条件の再確認とレコードロック
            $stmt = $db->execute(
                "SELECT * FROM images
                WHERE id = ?
                AND last_accessed_at <= NOW() - INTERVAL 30 DAY
                FOR UPDATE",
                [$image["id"]]
            );

            $lockedImage = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($lockedImage === false) {
                $db->rollBack();
                continue;
            }

            $imagePath = $lockedImage["image_path"];
            
            if(is_file($imagePath)) {
                if(!unlink($imagePath)) {
                error_log("画像の削除に失敗しました: " . $imagePath);
                $db->rollBack();
                continue;
                }
            }

            $db->execute(
                "DELETE FROM images WHERE id = ?",
                [$lockedImage["id"]]
            );
            // トランザクションの確定
            $db->commit();

            echo "画像を削除しました: " . $lockedImage["id"] . PHP_EOL;

        } catch(PDOException $e) {
            error_log("DBエラー: " . $e->getMessage());

            try {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
            } catch (PDOException $rollbackError) {
                error_log("ロールバックエラー: " . $rollbackError->getMessage());
                exit(1);
            }
        }
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    exit(1);
}


