-- Idempotent relationship repair for existing CivicAI MySQL databases.
-- Run after selecting civicai. Confirm orphan checks are zero before applying.
SET @ddl = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_reports_case') = 0, 'ALTER TABLE reports ADD CONSTRAINT fk_reports_case FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE civicai_stmt FROM @ddl; EXECUTE civicai_stmt; DEALLOCATE PREPARE civicai_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_comments_user') = 0, 'ALTER TABLE comments ADD CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE civicai_stmt FROM @ddl; EXECUTE civicai_stmt; DEALLOCATE PREPARE civicai_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_comments_case') = 0, 'ALTER TABLE comments ADD CONSTRAINT fk_comments_case FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE civicai_stmt FROM @ddl; EXECUTE civicai_stmt; DEALLOCATE PREPARE civicai_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_report_comments_report') = 0, 'ALTER TABLE report_comments ADD CONSTRAINT fk_report_comments_report FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE civicai_stmt FROM @ddl; EXECUTE civicai_stmt; DEALLOCATE PREPARE civicai_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_report_comments_parent') = 0, 'ALTER TABLE report_comments ADD CONSTRAINT fk_report_comments_parent FOREIGN KEY (parent_id) REFERENCES report_comments(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE civicai_stmt FROM @ddl; EXECUTE civicai_stmt; DEALLOCATE PREPARE civicai_stmt;

SET @ddl = IF((SELECT COUNT(*) FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_report_votes_report') = 0, 'ALTER TABLE report_votes ADD CONSTRAINT fk_report_votes_report FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE', 'SELECT 1');
PREPARE civicai_stmt FROM @ddl; EXECUTE civicai_stmt; DEALLOCATE PREPARE civicai_stmt;
