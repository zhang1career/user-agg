-- ============================================
-- File IO Database Schema
-- ============================================

-- Media file metadata table
CREATE TABLE IF NOT EXISTS `media_file` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `original_name` VARCHAR(255) NOT NULL COMMENT 'Original file name',
    `mime_type` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'MIME enum code',
    `size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'File size in bytes',
    `raw_path` VARCHAR(255) NOT NULL COMMENT 'S3 source object key',
    `transcoded_path` VARCHAR(255) NULL COMMENT 'S3 transcoded object key',
    `cdn_url` VARCHAR(255) NULL COMMENT 'CloudFront URL',
    `status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-init,1-uploaded,2-transcoding,3-ready,4-failed',
    `error_message` TEXT NULL COMMENT 'Error detail when processing failed',
    `ct` BIGINT UNSIGNED NOT NULL COMMENT 'Create time in Unix milliseconds',
    `ut` BIGINT UNSIGNED NOT NULL COMMENT 'Update time in Unix milliseconds',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='File metadata and processing status';
