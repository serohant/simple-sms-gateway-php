-- database.sql
CREATE DATABASE IF NOT EXISTS sms_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sms_api;

-- Basit kullanıcı tablosu
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  api_key VARCHAR(64) NOT NULL UNIQUE,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Mesajlar
CREATE TABLE IF NOT EXISTS messages (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  msg_id VARCHAR(32) NOT NULL UNIQUE, -- MSG-YYYY-NNNNNN
  user_id INT NOT NULL,
  recipient VARCHAR(32) NOT NULL,
  content TEXT NOT NULL,
  status ENUM('queued','sent','delivered','failed') DEFAULT 'queued',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (user_id),
  INDEX (recipient),
  INDEX (status),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Durum geçmişi
CREATE TABLE IF NOT EXISTS message_status_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  msg_id VARCHAR(32) NOT NULL,
  old_status VARCHAR(16),
  new_status VARCHAR(16) NOT NULL,
  changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (msg_id)
) ENGINE=InnoDB;

-- Basit rate limit sayaçları
CREATE TABLE IF NOT EXISTS rate_limits (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  window_start TIMESTAMP NOT NULL,
  counter INT NOT NULL DEFAULT 0,
  UNIQUE KEY uniq_user_window (user_id, window_start),
  INDEX (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Demo kullanıcı
INSERT INTO users (name, api_key, status) VALUES
('Demo User', 'DEMO_API_KEY_123456', 'active');