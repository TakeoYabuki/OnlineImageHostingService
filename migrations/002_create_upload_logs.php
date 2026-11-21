<?php

return function (MySQLWrapper $db): void {
    $db->execute("
        CREATE TABLE IF NOT EXISTS upload_logs (
          id	INT AUTO_INCREMENT PRIMARY KEY,
          ip_address	VARCHAR(45) NOT NULL,
          file_size	INT UNSIGNED NOT NULL,
          uploaded_at	DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_ip_uploaded_at (ip_address, uploaded_at)
        )
    ");
};