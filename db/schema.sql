-- Schema: clinica_moya
CREATE DATABASE IF NOT EXISTS clinica_moya
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_0900_ai_ci;
USE clinica_moya;

-- 1) Catálogo de roles
CREATE TABLE IF NOT EXISTS roles (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2) Usuarios (credenciales)
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  role_id TINYINT UNSIGNED NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(30),
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_users_role
    FOREIGN KEY (role_id) REFERENCES roles(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE INDEX idx_users_role ON users(role_id);
CREATE INDEX idx_users_active ON users(is_active);
CREATE INDEX idx_users_deleted ON users(deleted_at);

-- 3) Pacientes (perfil)
CREATE TABLE IF NOT EXISTS patients (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  birth_date DATE,
  document_type ENUM('DNI','CE','PAS') DEFAULT 'DNI',
  document_number VARCHAR(30),
  address VARCHAR(200),
  emergency_contact VARCHAR(150),
  emergency_phone VARCHAR(30),
  blood_type ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-'),
  allergies TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_patients_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4) Doctores (perfil)
CREATE TABLE IF NOT EXISTS doctors (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  license_number VARCHAR(50) UNIQUE,
  bio TEXT,
  years_experience SMALLINT UNSIGNED DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_doctors_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5) Especialidades
CREATE TABLE IF NOT EXISTS specialties (
  id SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255)
) ENGINE=InnoDB;

-- 6) Relación Doctor-Especialidad (N:M)
CREATE TABLE IF NOT EXISTS doctor_specialty (
  doctor_id BIGINT UNSIGNED NOT NULL,
  specialty_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (doctor_id, specialty_id),
  CONSTRAINT fk_ds_doctor
    FOREIGN KEY (doctor_id) REFERENCES doctors(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ds_specialty
    FOREIGN KEY (specialty_id) REFERENCES specialties(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 7) Catálogo de estados de cita
CREATE TABLE IF NOT EXISTS appointment_status (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- 8) Disponibilidad de doctores (opcional)
CREATE TABLE IF NOT EXISTS doctor_availability (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  doctor_id BIGINT UNSIGNED NOT NULL,
  weekday TINYINT UNSIGNED NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_av_doctor
    FOREIGN KEY (doctor_id) REFERENCES doctors(user_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT chk_time_range CHECK (start_time < end_time)
) ENGINE=InnoDB;

CREATE INDEX idx_av_doctor_day ON doctor_availability(doctor_id, weekday);

-- 9) Citas
CREATE TABLE IF NOT EXISTS appointments (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  patient_id BIGINT UNSIGNED NOT NULL,
  doctor_id BIGINT UNSIGNED NOT NULL,
  specialty_id SMALLINT UNSIGNED NOT NULL,
  status_id TINYINT UNSIGNED NOT NULL,
  scheduled_date DATE NOT NULL,
  scheduled_time TIME NOT NULL,
  reason VARCHAR(255),
  created_by BIGINT UNSIGNED,
  canceled_by BIGINT UNSIGNED NULL,
  cancellation_reason VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_appt_patient
    FOREIGN KEY (patient_id) REFERENCES patients(user_id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_appt_doctor
    FOREIGN KEY (doctor_id) REFERENCES doctors(user_id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_appt_specialty
    FOREIGN KEY (specialty_id) REFERENCES specialties(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_appt_status
    FOREIGN KEY (status_id) REFERENCES appointment_status(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_appt_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_appt_canceled_by
    FOREIGN KEY (canceled_by) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_appt_time CHECK (scheduled_time >= '00:00:00' AND scheduled_time < '24:00:00')
) ENGINE=InnoDB;

CREATE INDEX idx_appt_doctor_datetime ON appointments(doctor_id, scheduled_date, scheduled_time);
CREATE INDEX idx_appt_patient_datetime ON appointments(patient_id, scheduled_date, scheduled_time);
CREATE INDEX idx_appt_status ON appointments(status_id);

-- 10) Seeds mínimos
INSERT IGNORE INTO roles (id, name) VALUES
  (1, 'admin'), (2, 'doctor'), (3, 'paciente');

INSERT IGNORE INTO appointment_status (id, name) VALUES
  (1, 'reservada'),
  (2, 'confirmada'),
  (3, 'atendida'),
  (4, 'cancelada'),
  (5, 'no_asistio');

INSERT IGNORE INTO specialties (name, description) VALUES
  ('Anestesiologia', NULL),
  ('Cardiologia', NULL),
  ('Dermatologia', NULL),
  ('Ginecologia', NULL),
  ('Medicina interna', NULL),
  ('Oftalmologia', NULL),
  ('Psiquiatria', NULL),
  ('Radiologia', NULL);
