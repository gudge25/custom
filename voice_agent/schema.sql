-- Voice Agent Latency Report: registrations table + 10-day retention event
-- Run once against asteriskcdrdb (matches DB_NAME in .env).
-- Requires the MySQL/MariaDB event scheduler to be ON — see README.md.

CREATE TABLE IF NOT EXISTS registrations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(20) NOT NULL,
    roundtrip_usec INT UNSIGNED NOT NULL,
    registration_datetime DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_name_time_rtt (name, registration_datetime, roundtrip_usec),
    KEY idx_name_time (name, registration_datetime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP EVENT IF EXISTS registrations_prune_daily;

CREATE EVENT registrations_prune_daily
ON SCHEDULE EVERY 1 DAY STARTS CONCAT(CURDATE() + INTERVAL 1 DAY, ' 02:00:00')
ON COMPLETION PRESERVE
DO
    DELETE FROM registrations WHERE registration_datetime < (NOW() - INTERVAL 10 DAY);
