-- =====================================================================
--  ANTRENMAN TAKİP  —  MySQL / MariaDB şeması
--  Tek kullanıcılı kişisel kilo & ağırlık takip uygulaması
--  Kurulum:  mysql -u KULLANICI -p < schema.sql
--  (ya da phpMyAdmin > Import)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS gymtrack
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gymtrack;

-- ---------- Kullanıcı (tek satır) ----------
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  start_weight  DECIMAL(5,1) NOT NULL DEFAULT 108.0,
  target_weight DECIMAL(5,1) NOT NULL DEFAULT 85.0,
  height_cm     INT          NOT NULL DEFAULT 166,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Varsayılan kullanıcı:  kullanıcı adı = zeyt   şifre = antrenman
-- !! Giriş yaptıktan sonra README'deki adımla ŞİFRENİ DEĞİŞTİR.
INSERT INTO users (username, password_hash)
VALUES ('zeyt', '$2y$10$nnXqFDr5TSRWD7ibVREHGOriq1ELjEwBp9eC/jmhqXZ3I39QppvN2')
ON DUPLICATE KEY UPDATE username = username;

-- ---------- Hareket kütüphanesi ----------
CREATE TABLE IF NOT EXISTS exercises (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(120) NOT NULL,
  category    ENUM('lower','upper','core','cardio','other') NOT NULL DEFAULT 'other',
  session     ENUM('A','B','both','none') NOT NULL DEFAULT 'none',
  is_optional TINYINT(1) NOT NULL DEFAULT 0,
  notes       VARCHAR(255) DEFAULT NULL,
  sort_order  INT NOT NULL DEFAULT 100,
  active      TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------- Antrenman seansları ----------
CREATE TABLE IF NOT EXISTS workouts (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  workout_date  DATE NOT NULL,
  session_label ENUM('A','B','Serbest') NOT NULL DEFAULT 'Serbest',
  notes         VARCHAR(500) DEFAULT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (workout_date)
) ENGINE=InnoDB;

-- ---------- Set kayıtları ----------
CREATE TABLE IF NOT EXISTS workout_sets (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  workout_id  INT NOT NULL,
  exercise_id INT NOT NULL,
  set_number  INT NOT NULL DEFAULT 1,
  reps        INT DEFAULT NULL,
  weight      DECIMAL(6,2) DEFAULT NULL,   -- kg (vücut ağırlığı hareketlerinde boş bırakılabilir)
  rpe_note    VARCHAR(120) DEFAULT NULL,   -- "form bozuldu", "kolay", vs.
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (workout_id)  REFERENCES workouts(id)  ON DELETE CASCADE,
  FOREIGN KEY (exercise_id) REFERENCES exercises(id) ON DELETE CASCADE,
  INDEX (exercise_id)
) ENGINE=InnoDB;

-- ---------- Vücut ağırlığı (kilo takibi) ----------
CREATE TABLE IF NOT EXISTS bodyweight (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  user_id   INT NOT NULL,
  log_date  DATE NOT NULL,
  weight_kg DECIMAL(5,1) NOT NULL,
  note      VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_user_date (user_id, log_date)
) ENGINE=InnoDB;

-- =====================================================================
--  Hareket kütüphanesi — Faz 1 programından dolduruldu
-- =====================================================================
INSERT INTO exercises (name, category, session, is_optional, notes, sort_order) VALUES
-- Seans A — alt
('TRX / Goblet Squat',            'lower','A',0,'Kontrollü derinlik, kapalı zincir',10),
('Hip Hinge / RDL (Smith/DB)',    'lower','A',0,'Hamstring koruyucu — strap',20),
('Leg Curl',                      'lower','A',0,'Hamstring',30),
('Glute Bridge / Hip Thrust',     'lower','A',1,'Önerilir, diz dostu',40),
('Calf Raise',                    'lower','A',1,NULL,50),
-- Seans A — üst
('Chest Press / Incline DB Press','upper','A',0,NULL,60),
('Wide-grip Lat Pulldown',        'upper','A',0,'STRAP',70),
('Seated Cable Row (nötr)',       'upper','A',0,'STRAP',80),
('Reverse Fly / Rear Delt',       'upper','A',0,'Postür',90),
('Cable Push Down',               'upper','A',1,NULL,100),
('Hammer / EZ Curl (hafif)',      'upper','A',1,'Ön kola dikkat',110),
('Pallof / Cable Core Rotation',  'core', 'A',0,'Anti-rotasyon',120),
-- Seans B — alt
('Leg Press (derin değil)',       'lower','B',0,'Kapalı zincir',10),
('Romanian / Hip Hinge',          'lower','B',0,'Hamstring — strap',20),
('Leg Extension',                 'lower','B',0,'HAFİF, orta açı, kilitleme yok (ACL)',30),
('Glute Bridge',                  'lower','B',0,NULL,40),
-- Seans B — üst
('Machine Shoulder Press',        'upper','B',0,NULL,50),
('Chest-supported / Bentover Row','upper','B',0,'STRAP',60),
('Wide-grip Row',                 'upper','B',0,'STRAP',70),
('Chin Tuck + boyun/postür',      'upper','B',0,'Kifoz',80),
('Anti-rotation Core (Pallof)',   'core', 'B',0,NULL,90),
-- Ortak / kardiyo
('Treadmill Incline (yürüyüş)',   'cardio','both',0,'Düşük etkili — koşu yok',200),
('Bisiklet',                      'cardio','both',0,'Düşük etkili',210),
('Eliptik',                       'cardio','both',0,NULL,220),
('Yüzme',                         'cardio','both',1,'En ideal — eklem dostu',230);
