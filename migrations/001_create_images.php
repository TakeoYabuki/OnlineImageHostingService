<?php

return function (MySQLWrapper $db): void {
    $db->execute("
      CREATE TABLE IF NOT EXISTS images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        unique_key VARCHAR(64) NOT NULL UNIQUE,
        delete_key VARCHAR(64) NOT NULL UNIQUE,
        image_path VARCHAR(255) NOT NULL,
        mime_type VARCHAR(50) NOT NULL,
        file_size INT UNSIGNED NOT NULL,
        view_count INT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_accessed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_last_accessed_at (last_accessed_at)
      )
    ");
};