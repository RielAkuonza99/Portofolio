-- Upgrade content model for direct project/journey uploads and public traffic.
ALTER TABLE projects
  ADD COLUMN image_crop VARCHAR(40) NOT NULL DEFAULT 'center',
  ADD COLUMN video_start DECIMAL(10,2) NOT NULL DEFAULT 0,
  ADD COLUMN video_end DECIMAL(10,2) NOT NULL DEFAULT 0,
  ADD COLUMN video_crop VARCHAR(40) NOT NULL DEFAULT 'contain';

ALTER TABLE journey
  ADD COLUMN slug VARCHAR(200) NOT NULL DEFAULT '',
  ADD COLUMN body_html MEDIUMTEXT NULL,
  ADD COLUMN cover_url VARCHAR(1000) NOT NULL DEFAULT '',
  ADD COLUMN link_url VARCHAR(1000) NOT NULL DEFAULT '';

CREATE TABLE IF NOT EXISTS site_visits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  path VARCHAR(120) NOT NULL,
  ip_hash CHAR(64) NOT NULL,
  user_agent VARCHAR(300) NOT NULL DEFAULT '',
  visited_at DATETIME NOT NULL,
  KEY idx_visit_time (visited_at),
  KEY idx_visit_path (path, visited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
