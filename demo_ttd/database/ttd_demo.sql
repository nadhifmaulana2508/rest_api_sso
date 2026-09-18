CREATE TABLE IF NOT EXISTS ttd_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_peg VARCHAR(50) NOT NULL UNIQUE,
    signature_path VARCHAR(255) NOT NULL,
    pin_hash VARCHAR(255) NOT NULL,
    status ENUM('ACTIVE','REVOKED') NOT NULL DEFAULT 'ACTIVE',
    failed_attempt TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ttd_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ttd_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id VARCHAR(100) NOT NULL,
    id_peg VARCHAR(50) NOT NULL,
    application VARCHAR(50) NOT NULL DEFAULT 'SIMPEG',
    document_title VARCHAR(200) NOT NULL,
    document_hash CHAR(64) NOT NULL,
    verification_token CHAR(64) NOT NULL UNIQUE,
    signed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_doc_id (document_id),
    INDEX idx_doc_employee (id_peg)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ttd_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_peg VARCHAR(50) NOT NULL,
    application VARCHAR(50) NOT NULL,
    document_id VARCHAR(100) NULL,
    action VARCHAR(30) NOT NULL,
    status VARCHAR(20) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_employee (id_peg),
    INDEX idx_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
