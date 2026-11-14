-- Laporan postingan warga untuk moderasi officer
CREATE TABLE IF NOT EXISTS post_reports (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    report_id INT UNSIGNED NOT NULL,
    reporter_id INT UNSIGNED NOT NULL,
    reason VARCHAR(50) NOT NULL,
    details TEXT NULL,
    status ENUM('pending', 'reviewed', 'valid', 'rejected') NOT NULL DEFAULT 'pending',
    officer_notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reporter_post (report_id, reporter_id),
    INDEX idx_post_reports_status (status),
    INDEX idx_post_reports_report (report_id),
    CONSTRAINT fk_post_reports_report FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE,
    CONSTRAINT fk_post_reports_user FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
);
