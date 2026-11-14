-- Migration idempoten untuk database civicai yang sudah memiliki tabel reports.
-- Jalankan setelah memilih database civicai. Untuk instalasi baru, gunakan init.sql.
SET @ddl = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reports' AND COLUMN_NAME = 'ai_priority_score') = 0, 'ALTER TABLE reports ADD COLUMN ai_priority_score INT NULL', 'SELECT 1');
PREPARE civicai_stmt FROM @ddl; EXECUTE civicai_stmt; DEALLOCATE PREPARE civicai_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reports' AND COLUMN_NAME = 'ai_priority_reasoning') = 0, 'ALTER TABLE reports ADD COLUMN ai_priority_reasoning TEXT NULL', 'SELECT 1');
PREPARE civicai_stmt FROM @ddl; EXECUTE civicai_stmt; DEALLOCATE PREPARE civicai_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reports' AND COLUMN_NAME = 'ai_problem') = 0, 'ALTER TABLE reports ADD COLUMN ai_problem VARCHAR(100) NULL', 'SELECT 1');
PREPARE civicai_stmt FROM @ddl; EXECUTE civicai_stmt; DEALLOCATE PREPARE civicai_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reports' AND COLUMN_NAME = 'ai_duration') = 0, 'ALTER TABLE reports ADD COLUMN ai_duration VARCHAR(100) NULL', 'SELECT 1');
PREPARE civicai_stmt FROM @ddl; EXECUTE civicai_stmt; DEALLOCATE PREPARE civicai_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reports' AND COLUMN_NAME = 'ai_risk') = 0, 'ALTER TABLE reports ADD COLUMN ai_risk VARCHAR(150) NULL', 'SELECT 1');
PREPARE civicai_stmt FROM @ddl; EXECUTE civicai_stmt; DEALLOCATE PREPARE civicai_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reports' AND COLUMN_NAME = 'ai_location_context') = 0, 'ALTER TABLE reports ADD COLUMN ai_location_context VARCHAR(150) NULL', 'SELECT 1');
PREPARE civicai_stmt FROM @ddl; EXECUTE civicai_stmt; DEALLOCATE PREPARE civicai_stmt;
