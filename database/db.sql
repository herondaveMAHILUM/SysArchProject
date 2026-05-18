-- ============================================================
--  SIMS — Sit In Monitoring System
--  Database: sims_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS sims_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sims_db;

-- ── STUDENTS TABLE ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS students (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  id_number     VARCHAR(50)  NOT NULL UNIQUE,
  last_name     VARCHAR(100) NOT NULL,
  first_name    VARCHAR(100) NOT NULL,
  middle_name   VARCHAR(100) DEFAULT '',
  year_level    TINYINT      NOT NULL,
  course        VARCHAR(50)  NOT NULL,
  address       TEXT         NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password      VARCHAR(255) NOT NULL,
  profile_pic   VARCHAR(255) DEFAULT '',
  remaining_session INT      DEFAULT 30,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── SIT-IN RECORDS TABLE ─────────────────────────────────
CREATE TABLE IF NOT EXISTS sitin_records (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  student_id  INT          NOT NULL,
  purpose     VARCHAR(100) NOT NULL,
  lab         VARCHAR(50)  NOT NULL,
  login_time  TIME         DEFAULT NULL,
  logout_time TIME         DEFAULT NULL,
  date        DATE         NOT NULL,
  status      ENUM('active','done') DEFAULT 'active',
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── RESERVATIONS TABLE ───────────────────────────────────
CREATE TABLE IF NOT EXISTS reservations (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  student_id  INT          NOT NULL,
  purpose     VARCHAR(100) NOT NULL,
  lab         VARCHAR(50)  NOT NULL,
  pc_number   INT          DEFAULT NULL,
  time_in     TIME         NOT NULL,
  date        DATE         NOT NULL,
  status      ENUM('pending','approved','rejected','checked_in','completed','expired') DEFAULT 'pending',
  checked_in  TINYINT(1)   DEFAULT 0,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── ANNOUNCEMENTS TABLE ──────────────────────────────────
CREATE TABLE IF NOT EXISTS announcements (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  message    TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── FEEDBACK TABLE ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS feedback (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  student_id  INT  NOT NULL,
  sitin_id    INT  DEFAULT NULL,
  rating      TINYINT NOT NULL,
  comments    TEXT DEFAULT '',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (sitin_id)   REFERENCES sitin_records(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── NOTIFICATIONS TABLE ──────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  student_id  INT          NOT NULL,
  title       VARCHAR(200) NOT NULL,
  message     TEXT         NOT NULL,
  type        ENUM('reservation','announcement','system','general') DEFAULT 'general',
  is_read     TINYINT(1)   DEFAULT 0,
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── RESERVATION LOGS TABLE ───────────────────────────────
CREATE TABLE IF NOT EXISTS reservation_logs (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  reservation_id INT          NOT NULL,
  admin_name     VARCHAR(100) DEFAULT 'Admin',
  action         ENUM('approved','rejected','cancelled') NOT NULL,
  notes          TEXT         DEFAULT '',
  created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── PC STATUS TABLE (for cinema-style reservation) ───────
CREATE TABLE IF NOT EXISTS pc_status (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  lab            VARCHAR(50)  NOT NULL,
  pc_number      INT          NOT NULL,
  is_available   TINYINT(1)   DEFAULT 1,
  reserved_by    INT          DEFAULT NULL,
  reservation_id INT          DEFAULT NULL,
  reserved_date  DATE         DEFAULT NULL,
  reserved_time  TIME         DEFAULT NULL,
  UNIQUE KEY unique_lab_pc (lab, pc_number),
  FOREIGN KEY (reserved_by)    REFERENCES students(id)    ON DELETE SET NULL,
  FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── LAB SOFTWARE TABLE ───────────────────────────────────
CREATE TABLE IF NOT EXISTS lab_software (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  lab        VARCHAR(20)  NOT NULL,
  pc_number  INT          NOT NULL,
  software   VARCHAR(200) NOT NULL,
  version    VARCHAR(100) DEFAULT '',
  category   VARCHAR(100) DEFAULT '',
  notes      TEXT         DEFAULT NULL,
  updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_pc_software (lab, pc_number, software)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
--  SEED: PC STATUSES
-- ============================================================
INSERT IGNORE INTO pc_status (lab, pc_number, is_available) VALUES
('524', 1, 1), ('524', 2, 1), ('524', 3, 1), ('524', 4, 1), ('524', 5, 1),
('524', 6, 1), ('524', 7, 1), ('524', 8, 1), ('524', 9, 1), ('524', 10, 1),
('526', 1, 1), ('526', 2, 1), ('526', 3, 1), ('526', 4, 1), ('526', 5, 1),
('526', 6, 1), ('526', 7, 1), ('526', 8, 1), ('526', 9, 1), ('526', 10, 1),
('528', 1, 1), ('528', 2, 1), ('528', 3, 1), ('528', 4, 1), ('528', 5, 1),
('528', 6, 1), ('528', 7, 1), ('528', 8, 1), ('528', 9, 1), ('528', 10, 1);

-- ============================================================
--  SEED: ANNOUNCEMENTS
-- ============================================================
INSERT INTO announcements (message, created_at) VALUES
  ('Welcome to the new SIMS portal! Please log in to check your sessions.', '2026-02-11 08:00:00'),
  ('Important Announcement: We are excited to announce the launch of our new website! Explore our latest products and services now!', '2024-05-08 10:00:00');

-- ============================================================
--  SEED: LAB SOFTWARE
--  Category = actual OS type (Windows 11 / Windows 10)
-- ============================================================

-- ── LAB 524  (Windows 11 — PCs 1–10) ────────────────────
INSERT IGNORE INTO lab_software (lab, pc_number, software, version, category, notes) VALUES
('524', 1,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('524', 1,  'Visual Studio Code', '1.89.1',       'IDE',        'Extensions: Python, Java, C++'),
('524', 1,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('524', 1,  'Java JDK',           '21.0.3',       'Runtime',    'LTS release'),
('524', 1,  'Git',                '2.45.0',       'Utility',    NULL),

('524', 2,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('524', 2,  'NetBeans IDE',       '21.0',         'IDE',        'Java development'),
('524', 2,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('524', 2,  'Java JDK',           '21.0.3',       'Runtime',    NULL),
('524', 2,  'MySQL Workbench',    '8.0.36',       'Database',   NULL),

('524', 3,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('524', 3,  'Visual Studio 2022', '17.9',         'IDE',        'Community Edition'),
('524', 3,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('524', 3,  '.NET SDK',           '8.0.4',        'Runtime',    NULL),
('524', 3,  'Git',                '2.45.0',       'Utility',    NULL),

('524', 4,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('524', 4,  'Visual Studio Code', '1.89.1',       'IDE',        NULL),
('524', 4,  'Mozilla Firefox',    '125.0.2',      'Browser',    NULL),
('524', 4,  'Python',             '3.12.3',       'Runtime',    NULL),
('524', 4,  'Anaconda',           '2024.02',      'Utility',    'Data science distribution'),

('524', 5,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('524', 5,  'Eclipse IDE',        '2024-03',      'IDE',        'Java EE'),
('524', 5,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('524', 5,  'Java JDK',           '21.0.3',       'Runtime',    NULL),
('524', 5,  'Apache Tomcat',      '10.1.20',      'Server',     NULL),

('524', 6,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('524', 6,  'Visual Studio Code', '1.89.1',       'IDE',        NULL),
('524', 6,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('524', 6,  'Node.js',            '20.12.2',      'Runtime',    'LTS version'),
('524', 6,  'Git',                '2.45.0',       'Utility',    NULL),

('524', 7,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('524', 7,  'IntelliJ IDEA',      '2024.1',       'IDE',        'Community Edition'),
('524', 7,  'Mozilla Firefox',    '125.0.2',      'Browser',    NULL),
('524', 7,  'Java JDK',           '21.0.3',       'Runtime',    NULL),
('524', 7,  'MySQL Workbench',    '8.0.36',       'Database',   NULL),

('524', 8,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('524', 8,  'Visual Studio Code', '1.89.1',       'IDE',        NULL),
('524', 8,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('524', 8,  'Python',             '3.12.3',       'Runtime',    NULL),
('524', 8,  'Git',                '2.45.0',       'Utility',    NULL),

('524', 9,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('524', 9,  'Dev-C++',            '6.3',          'IDE',        'Bloodshed Dev-C++'),
('524', 9,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('524', 9,  'MinGW-w64',          '13.2.0',       'Compiler',   'GCC for Windows'),
('524', 9,  'Git',                '2.45.0',       'Utility',    NULL),

('524', 10, 'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('524', 10, 'Visual Studio Code', '1.89.1',       'IDE',        NULL),
('524', 10, 'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('524', 10, 'PHP',                '8.3.6',        'Runtime',    NULL),
('524', 10, 'XAMPP',              '8.2.12',       'Server',     'Apache + MySQL bundle');

-- ── LAB 526  (Windows 10 — PCs 1–10) ────────────────────
INSERT IGNORE INTO lab_software (lab, pc_number, software, version, category, notes) VALUES
('526', 1,  'Windows 10',         '22H2',         'Windows 10', 'Primary OS'),
('526', 1,  'Eclipse IDE',        '2024-03',      'IDE',        'Java SE'),
('526', 1,  'Mozilla Firefox',    '125.0.2',      'Browser',    NULL),
('526', 1,  'Java JDK',           '17.0.11',      'Runtime',    'LTS release'),
('526', 1,  'Git',                '2.44.0',       'Utility',    NULL),

('526', 2,  'Windows 10',         '22H2',         'Windows 10', 'Primary OS'),
('526', 2,  'Visual Studio Code', '1.89.1',       'IDE',        NULL),
('526', 2,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('526', 2,  'Python',             '3.11.9',       'Runtime',    NULL),
('526', 2,  'MySQL Workbench',    '8.0.36',       'Database',   NULL),

('526', 3,  'Windows 10',         '22H2',         'Windows 10', 'Primary OS'),
('526', 3,  'NetBeans IDE',       '21.0',         'IDE',        NULL),
('526', 3,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('526', 3,  'Java JDK',           '17.0.11',      'Runtime',    NULL),
('526', 3,  'XAMPP',              '8.2.12',       'Server',     NULL),

('526', 4,  'Windows 10',         '22H2',         'Windows 10', 'Primary OS'),
('526', 4,  'Visual Studio Code', '1.89.1',       'IDE',        NULL),
('526', 4,  'Mozilla Firefox',    '125.0.2',      'Browser',    NULL),
('526', 4,  'Node.js',            '20.12.2',      'Runtime',    'LTS version'),
('526', 4,  'Git',                '2.44.0',       'Utility',    NULL),

('526', 5,  'Windows 10',         '22H2',         'Windows 10', 'Primary OS'),
('526', 5,  'Dev-C++',            '6.3',          'IDE',        NULL),
('526', 5,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('526', 5,  'MinGW-w64',          '13.2.0',       'Compiler',   NULL),
('526', 5,  'Git',                '2.44.0',       'Utility',    NULL),

('526', 6,  'Windows 10',         '22H2',         'Windows 10', 'Primary OS'),
('526', 6,  'IntelliJ IDEA',      '2024.1',       'IDE',        'Community Edition'),
('526', 6,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('526', 6,  'Java JDK',           '17.0.11',      'Runtime',    NULL),
('526', 6,  'MySQL Workbench',    '8.0.36',       'Database',   NULL),

('526', 7,  'Windows 10',         '22H2',         'Windows 10', 'Primary OS'),
('526', 7,  'Visual Studio Code', '1.89.1',       'IDE',        NULL),
('526', 7,  'Mozilla Firefox',    '125.0.2',      'Browser',    NULL),
('526', 7,  'PHP',                '8.2.19',       'Runtime',    NULL),
('526', 7,  'XAMPP',              '8.2.12',       'Server',     NULL),

('526', 8,  'Windows 10',         '22H2',         'Windows 10', 'Primary OS'),
('526', 8,  'Eclipse IDE',        '2024-03',      'IDE',        NULL),
('526', 8,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('526', 8,  'Java JDK',           '17.0.11',      'Runtime',    NULL),
('526', 8,  'Git',                '2.44.0',       'Utility',    NULL),

('526', 9,  'Windows 10',         '22H2',         'Windows 10', 'Primary OS'),
('526', 9,  'Visual Studio 2022', '17.9',         'IDE',        'Community Edition'),
('526', 9,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('526', 9,  '.NET SDK',           '8.0.4',        'Runtime',    NULL),
('526', 9,  'Git',                '2.44.0',       'Utility',    NULL),

('526', 10, 'Windows 10',         '22H2',         'Windows 10', 'Primary OS'),
('526', 10, 'Visual Studio Code', '1.89.1',       'IDE',        NULL),
('526', 10, 'Mozilla Firefox',    '125.0.2',      'Browser',    NULL),
('526', 10, 'Python',             '3.11.9',       'Runtime',    NULL),
('526', 10, 'Anaconda',           '2024.02',      'Utility',    'Data science tools');

-- ── LAB 528  (Windows 11 — PCs 1–10) ────────────────────
INSERT IGNORE INTO lab_software (lab, pc_number, software, version, category, notes) VALUES
('528', 1,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('528', 1,  'Visual Studio Code', '1.89.1',       'IDE',        NULL),
('528', 1,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('528', 1,  'Python',             '3.12.3',       'Runtime',    NULL),
('528', 1,  'Git',                '2.45.0',       'Utility',    NULL),

('528', 2,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('528', 2,  'IntelliJ IDEA',      '2024.1',       'IDE',        'Community Edition'),
('528', 2,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('528', 2,  'Java JDK',           '21.0.3',       'Runtime',    NULL),
('528', 2,  'MySQL Workbench',    '8.0.36',       'Database',   NULL),

('528', 3,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('528', 3,  'NetBeans IDE',       '21.0',         'IDE',        NULL),
('528', 3,  'Mozilla Firefox',    '125.0.2',      'Browser',    NULL),
('528', 3,  'Java JDK',           '21.0.3',       'Runtime',    NULL),
('528', 3,  'Apache Tomcat',      '10.1.20',      'Server',     NULL),

('528', 4,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('528', 4,  'Visual Studio Code', '1.89.1',       'IDE',        NULL),
('528', 4,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('528', 4,  'Node.js',            '20.12.2',      'Runtime',    'LTS version'),
('528', 4,  'Git',                '2.45.0',       'Utility',    NULL),

('528', 5,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('528', 5,  'Visual Studio 2022', '17.9',         'IDE',        'Community Edition'),
('528', 5,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('528', 5,  '.NET SDK',           '8.0.4',        'Runtime',    NULL),
('528', 5,  'Git',                '2.45.0',       'Utility',    NULL),

('528', 6,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('528', 6,  'Eclipse IDE',        '2024-03',      'IDE',        NULL),
('528', 6,  'Mozilla Firefox',    '125.0.2',      'Browser',    NULL),
('528', 6,  'Java JDK',           '21.0.3',       'Runtime',    NULL),
('528', 6,  'MySQL Workbench',    '8.0.36',       'Database',   NULL),

('528', 7,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('528', 7,  'Visual Studio Code', '1.89.1',       'IDE',        NULL),
('528', 7,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('528', 7,  'PHP',                '8.3.6',        'Runtime',    NULL),
('528', 7,  'XAMPP',              '8.2.12',       'Server',     NULL),

('528', 8,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('528', 8,  'Dev-C++',            '6.3',          'IDE',        NULL),
('528', 8,  'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('528', 8,  'MinGW-w64',          '13.2.0',       'Compiler',   NULL),
('528', 8,  'Git',                '2.45.0',       'Utility',    NULL),

('528', 9,  'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('528', 9,  'Visual Studio Code', '1.89.1',       'IDE',        NULL),
('528', 9,  'Mozilla Firefox',    '125.0.2',      'Browser',    NULL),
('528', 9,  'Python',             '3.12.3',       'Runtime',    NULL),
('528', 9,  'Anaconda',           '2024.02',      'Utility',    'Data science distribution'),

('528', 10, 'Windows 11',         '23H2',         'Windows 11', 'Primary OS'),
('528', 10, 'Visual Studio Code', '1.89.1',       'IDE',        NULL),
('528', 10, 'Google Chrome',      '124.0.6367',   'Browser',    NULL),
('528', 10, 'Java JDK',           '21.0.3',       'Runtime',    NULL),
('528', 10, 'Git',                '2.45.0',       'Utility',    NULL);

-- ── LAB 530  (Windows 11 — PCs 1–10) ────────────────────
INSERT IGNORE INTO lab_software (lab, pc_number, software, version, category, notes) VALUES
('530', 1,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('530', 1,  'Visual Studio Code', '1.89.1',      'IDE',        'Extensions: Python, Java, C++'),
('530', 1,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('530', 1,  'Java JDK',           '21.0.3',      'Runtime',    'LTS release'),
('530', 1,  'Git',                '2.45.0',      'Utility',    NULL),

('530', 2,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('530', 2,  'NetBeans IDE',       '21.0',        'IDE',        'Java development'),
('530', 2,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('530', 2,  'Java JDK',           '21.0.3',      'Runtime',    NULL),
('530', 2,  'MySQL Workbench',    '8.0.36',      'Database',   NULL),

('530', 3,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('530', 3,  'Visual Studio 2022', '17.9',        'IDE',        'Community Edition'),
('530', 3,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('530', 3,  '.NET SDK',           '8.0.4',       'Runtime',    NULL),
('530', 3,  'Git',                '2.45.0',      'Utility',    NULL),

('530', 4,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('530', 4,  'Visual Studio Code', '1.89.1',      'IDE',        NULL),
('530', 4,  'Mozilla Firefox',    '125.0.2',     'Browser',    NULL),
('530', 4,  'Python',             '3.12.3',      'Runtime',    NULL),
('530', 4,  'Anaconda',           '2024.02',     'Utility',    'Data science distribution'),

('530', 5,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('530', 5,  'Eclipse IDE',        '2024-03',     'IDE',        'Java EE'),
('530', 5,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('530', 5,  'Java JDK',           '21.0.3',      'Runtime',    NULL),
('530', 5,  'Apache Tomcat',      '10.1.20',     'Server',     NULL),

('530', 6,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('530', 6,  'Visual Studio Code', '1.89.1',      'IDE',        NULL),
('530', 6,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('530', 6,  'Node.js',            '20.12.2',     'Runtime',    'LTS version'),
('530', 6,  'Git',                '2.45.0',      'Utility',    NULL),

('530', 7,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('530', 7,  'IntelliJ IDEA',      '2024.1',      'IDE',        'Community Edition'),
('530', 7,  'Mozilla Firefox',    '125.0.2',     'Browser',    NULL),
('530', 7,  'Java JDK',           '21.0.3',      'Runtime',    NULL),
('530', 7,  'MySQL Workbench',    '8.0.36',      'Database',   NULL),

('530', 8,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('530', 8,  'Visual Studio Code', '1.89.1',      'IDE',        NULL),
('530', 8,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('530', 8,  'Python',             '3.12.3',      'Runtime',    NULL),
('530', 8,  'Git',                '2.45.0',      'Utility',    NULL),

('530', 9,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('530', 9,  'Dev-C++',            '6.3',         'IDE',        'Bloodshed Dev-C++'),
('530', 9,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('530', 9,  'MinGW-w64',          '13.2.0',      'Compiler',   'GCC for Windows'),
('530', 9,  'Git',                '2.45.0',      'Utility',    NULL),

('530', 10, 'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('530', 10, 'Visual Studio Code', '1.89.1',      'IDE',        NULL),
('530', 10, 'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('530', 10, 'PHP',                '8.3.6',       'Runtime',    NULL),
('530', 10, 'XAMPP',              '8.2.12',      'Server',     'Apache + MySQL bundle');

-- ── LAB 542  (Windows 10 — PCs 1–10) ────────────────────
INSERT IGNORE INTO lab_software (lab, pc_number, software, version, category, notes) VALUES
('542', 1,  'Windows 10',         '22H2',        'Windows 10', 'Primary OS'),
('542', 1,  'Eclipse IDE',        '2024-03',     'IDE',        'Java SE'),
('542', 1,  'Mozilla Firefox',    '125.0.2',     'Browser',    NULL),
('542', 1,  'Java JDK',           '17.0.11',     'Runtime',    'LTS release'),
('542', 1,  'Git',                '2.44.0',      'Utility',    NULL),

('542', 2,  'Windows 10',         '22H2',        'Windows 10', 'Primary OS'),
('542', 2,  'Visual Studio Code', '1.89.1',      'IDE',        NULL),
('542', 2,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('542', 2,  'Python',             '3.11.9',      'Runtime',    NULL),
('542', 2,  'MySQL Workbench',    '8.0.36',      'Database',   NULL),

('542', 3,  'Windows 10',         '22H2',        'Windows 10', 'Primary OS'),
('542', 3,  'NetBeans IDE',       '21.0',        'IDE',        NULL),
('542', 3,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('542', 3,  'Java JDK',           '17.0.11',     'Runtime',    NULL),
('542', 3,  'XAMPP',              '8.2.12',      'Server',     NULL),

('542', 4,  'Windows 10',         '22H2',        'Windows 10', 'Primary OS'),
('542', 4,  'Visual Studio Code', '1.89.1',      'IDE',        NULL),
('542', 4,  'Mozilla Firefox',    '125.0.2',     'Browser',    NULL),
('542', 4,  'Node.js',            '20.12.2',     'Runtime',    'LTS version'),
('542', 4,  'Git',                '2.44.0',      'Utility',    NULL),

('542', 5,  'Windows 10',         '22H2',        'Windows 10', 'Primary OS'),
('542', 5,  'Dev-C++',            '6.3',         'IDE',        NULL),
('542', 5,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('542', 5,  'MinGW-w64',          '13.2.0',      'Compiler',   NULL),
('542', 5,  'Git',                '2.44.0',      'Utility',    NULL),

('542', 6,  'Windows 10',         '22H2',        'Windows 10', 'Primary OS'),
('542', 6,  'IntelliJ IDEA',      '2024.1',      'IDE',        'Community Edition'),
('542', 6,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('542', 6,  'Java JDK',           '17.0.11',     'Runtime',    NULL),
('542', 6,  'MySQL Workbench',    '8.0.36',      'Database',   NULL),

('542', 7,  'Windows 10',         '22H2',        'Windows 10', 'Primary OS'),
('542', 7,  'Visual Studio Code', '1.89.1',      'IDE',        NULL),
('542', 7,  'Mozilla Firefox',    '125.0.2',     'Browser',    NULL),
('542', 7,  'PHP',                '8.2.19',      'Runtime',    NULL),
('542', 7,  'XAMPP',              '8.2.12',      'Server',     NULL),

('542', 8,  'Windows 10',         '22H2',        'Windows 10', 'Primary OS'),
('542', 8,  'Eclipse IDE',        '2024-03',     'IDE',        NULL),
('542', 8,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('542', 8,  'Java JDK',           '17.0.11',     'Runtime',    NULL),
('542', 8,  'Git',                '2.44.0',      'Utility',    NULL),

('542', 9,  'Windows 10',         '22H2',        'Windows 10', 'Primary OS'),
('542', 9,  'Visual Studio 2022', '17.9',        'IDE',        'Community Edition'),
('542', 9,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('542', 9,  '.NET SDK',           '8.0.4',       'Runtime',    NULL),
('542', 9,  'Git',                '2.44.0',      'Utility',    NULL),

('542', 10, 'Windows 10',         '22H2',        'Windows 10', 'Primary OS'),
('542', 10, 'Visual Studio Code', '1.89.1',      'IDE',        NULL),
('542', 10, 'Mozilla Firefox',    '125.0.2',     'Browser',    NULL),
('542', 10, 'Python',             '3.11.9',      'Runtime',    NULL),
('542', 10, 'Anaconda',           '2024.02',     'Utility',    'Data science tools');

-- ── LAB 544  (Windows 11 — PCs 1–10) ────────────────────
INSERT IGNORE INTO lab_software (lab, pc_number, software, version, category, notes) VALUES
('544', 1,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('544', 1,  'Visual Studio Code', '1.89.1',      'IDE',        NULL),
('544', 1,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('544', 1,  'Python',             '3.12.3',      'Runtime',    NULL),
('544', 1,  'Git',                '2.45.0',      'Utility',    NULL),

('544', 2,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('544', 2,  'IntelliJ IDEA',      '2024.1',      'IDE',        'Community Edition'),
('544', 2,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('544', 2,  'Java JDK',           '21.0.3',      'Runtime',    NULL),
('544', 2,  'MySQL Workbench',    '8.0.36',      'Database',   NULL),

('544', 3,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('544', 3,  'NetBeans IDE',       '21.0',        'IDE',        NULL),
('544', 3,  'Mozilla Firefox',    '125.0.2',     'Browser',    NULL),
('544', 3,  'Java JDK',           '21.0.3',      'Runtime',    NULL),
('544', 3,  'Apache Tomcat',      '10.1.20',     'Server',     NULL),

('544', 4,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('544', 4,  'Visual Studio Code', '1.89.1',      'IDE',        NULL),
('544', 4,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('544', 4,  'Node.js',            '20.12.2',     'Runtime',    'LTS version'),
('544', 4,  'Git',                '2.45.0',      'Utility',    NULL),

('544', 5,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('544', 5,  'Visual Studio 2022', '17.9',        'IDE',        'Community Edition'),
('544', 5,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('544', 5,  '.NET SDK',           '8.0.4',       'Runtime',    NULL),
('544', 5,  'Git',                '2.45.0',      'Utility',    NULL),

('544', 6,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('544', 6,  'Eclipse IDE',        '2024-03',     'IDE',        NULL),
('544', 6,  'Mozilla Firefox',    '125.0.2',     'Browser',    NULL),
('544', 6,  'Java JDK',           '21.0.3',      'Runtime',    NULL),
('544', 6,  'MySQL Workbench',    '8.0.36',      'Database',   NULL),

('544', 7,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('544', 7,  'Visual Studio Code', '1.89.1',      'IDE',        NULL),
('544', 7,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('544', 7,  'PHP',                '8.3.6',       'Runtime',    NULL),
('544', 7,  'XAMPP',              '8.2.12',      'Server',     NULL),

('544', 8,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('544', 8,  'Dev-C++',            '6.3',         'IDE',        NULL),
('544', 8,  'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('544', 8,  'MinGW-w64',          '13.2.0',      'Compiler',   NULL),
('544', 8,  'Git',                '2.45.0',      'Utility',    NULL),

('544', 9,  'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('544', 9,  'Visual Studio Code', '1.89.1',      'IDE',        NULL),
('544', 9,  'Mozilla Firefox',    '125.0.2',     'Browser',    NULL),
('544', 9,  'Python',             '3.12.3',      'Runtime',    NULL),
('544', 9,  'Anaconda',           '2024.02',     'Utility',    'Data science distribution'),

('544', 10, 'Windows 11',         '23H2',        'Windows 11', 'Primary OS'),
('544', 10, 'Visual Studio Code', '1.89.1',      'IDE',        NULL),
('544', 10, 'Google Chrome',      '124.0.6367',  'Browser',    NULL),
('544', 10, 'Java JDK',           '21.0.3',      'Runtime',    NULL),
('544', 10, 'Git',                '2.45.0',      'Utility',    NULL);

-- ============================================================
--  DUPLICATE: Expand seeded lab_software entries from PCs 1-10 to 11-50
--  This duplicates existing entries for PCs 1..10 into higher PC slots
--  for all seeded labs (524, 526, 528, 530, 542, 544).
-- ============================================================

INSERT IGNORE INTO lab_software (lab, pc_number, software, version, category, notes)
SELECT lab, pc_number + 10, software, version, category, notes
FROM lab_software
WHERE pc_number BETWEEN 1 AND 10 AND lab IN ('524','526','528','530','542','544');

INSERT IGNORE INTO lab_software (lab, pc_number, software, version, category, notes)
SELECT lab, pc_number + 20, software, version, category, notes
FROM lab_software
WHERE pc_number BETWEEN 1 AND 10 AND lab IN ('524','526','528','530','542','544');

INSERT IGNORE INTO lab_software (lab, pc_number, software, version, category, notes)
SELECT lab, pc_number + 30, software, version, category, notes
FROM lab_software
WHERE pc_number BETWEEN 1 AND 10 AND lab IN ('524','526','528','530','542','544');

INSERT IGNORE INTO lab_software (lab, pc_number, software, version, category, notes)
SELECT lab, pc_number + 40, software, version, category, notes
FROM lab_software
WHERE pc_number BETWEEN 1 AND 10 AND lab IN ('524','526','528','530','542','544');
