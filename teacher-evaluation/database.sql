CREATE DATABASE IF NOT EXISTS teacher_evaluation
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE teacher_evaluation;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs, notifications, settings, evaluation_comments,
  evaluation_answers, evaluations, questions, question_categories, rating_scales,
  evaluation_periods, teacher_subjects, class_students, classes, subjects,
  students, teachers, departments, users, roles;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(30) NOT NULL UNIQUE,
  label VARCHAR(60) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE departments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  code VARCHAR(20) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id BIGINT UNSIGNED NOT NULL,
  department_id BIGINT UNSIGNED NULL,
  full_name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
  CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id),
  INDEX idx_users_role_status (role_id, status),
  INDEX idx_users_name (full_name)
) ENGINE=InnoDB;

CREATE TABLE teachers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  employee_number VARCHAR(40) NOT NULL UNIQUE,
  title VARCHAR(80) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_teachers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE students (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  student_number VARCHAR(40) NOT NULL UNIQUE,
  year_level VARCHAR(30) NULL,
  section VARCHAR(60) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE subjects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  department_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(160) NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  CONSTRAINT fk_subjects_department FOREIGN KEY (department_id) REFERENCES departments(id),
  INDEX idx_subjects_department_status (department_id, status)
) ENGINE=InnoDB;

CREATE TABLE classes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  school_year VARCHAR(20) NOT NULL,
  semester VARCHAR(30) NOT NULL,
  department_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_class_term (name, school_year, semester),
  CONSTRAINT fk_classes_department FOREIGN KEY (department_id) REFERENCES departments(id),
  INDEX idx_classes_term (school_year, semester)
) ENGINE=InnoDB;

CREATE TABLE class_students (
  class_id BIGINT UNSIGNED NOT NULL,
  student_id BIGINT UNSIGNED NOT NULL,
  enrolled_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (class_id, student_id),
  CONSTRAINT fk_class_students_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  CONSTRAINT fk_class_students_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE teacher_subjects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id BIGINT UNSIGNED NOT NULL,
  subject_id BIGINT UNSIGNED NOT NULL,
  class_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_teacher_assignment (teacher_id, subject_id, class_id),
  CONSTRAINT fk_teacher_subjects_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
  CONSTRAINT fk_teacher_subjects_subject FOREIGN KEY (subject_id) REFERENCES subjects(id),
  CONSTRAINT fk_teacher_subjects_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  INDEX idx_teacher_subject_class (subject_id, class_id)
) ENGINE=InnoDB;

CREATE TABLE evaluation_periods (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  status ENUM('draft','open','closed') NOT NULL DEFAULT 'draft',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_periods_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_period_status_dates (status, starts_at, ends_at)
) ENGINE=InnoDB;

CREATE TABLE question_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE rating_scales (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  value TINYINT UNSIGNED NOT NULL UNIQUE,
  label VARCHAR(60) NOT NULL,
  description VARCHAR(255) NULL,
  CHECK (value BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE questions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NOT NULL,
  prompt VARCHAR(500) NOT NULL,
  question_type ENUM('rating','text') NOT NULL DEFAULT 'rating',
  is_required TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_questions_category FOREIGN KEY (category_id) REFERENCES question_categories(id),
  INDEX idx_questions_category_active (category_id, is_active, sort_order)
) ENGINE=InnoDB;

CREATE TABLE evaluations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id BIGINT UNSIGNED NOT NULL,
  teacher_id BIGINT UNSIGNED NOT NULL,
  class_id BIGINT UNSIGNED NOT NULL,
  subject_id BIGINT UNSIGNED NOT NULL,
  period_id BIGINT UNSIGNED NOT NULL,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  is_anonymous TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_evaluation_assignment (student_id, teacher_id, class_id, subject_id, period_id),
  CONSTRAINT fk_evaluations_student FOREIGN KEY (student_id) REFERENCES students(id),
  CONSTRAINT fk_evaluations_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id),
  CONSTRAINT fk_evaluations_class FOREIGN KEY (class_id) REFERENCES classes(id),
  CONSTRAINT fk_evaluations_subject FOREIGN KEY (subject_id) REFERENCES subjects(id),
  CONSTRAINT fk_evaluations_period FOREIGN KEY (period_id) REFERENCES evaluation_periods(id),
  INDEX idx_evaluations_teacher_period (teacher_id, period_id),
  INDEX idx_evaluations_class_subject (class_id, subject_id)
) ENGINE=InnoDB;

CREATE TABLE evaluation_answers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  evaluation_id BIGINT UNSIGNED NOT NULL,
  question_id BIGINT UNSIGNED NOT NULL,
  rating_value TINYINT UNSIGNED NULL,
  answer_text TEXT NULL,
  UNIQUE KEY uq_evaluation_question (evaluation_id, question_id),
  CONSTRAINT fk_answers_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE,
  CONSTRAINT fk_answers_question FOREIGN KEY (question_id) REFERENCES questions(id),
  CONSTRAINT fk_answers_rating FOREIGN KEY (rating_value) REFERENCES rating_scales(value),
  INDEX idx_answers_question_rating (question_id, rating_value)
) ENGINE=InnoDB;

CREATE TABLE evaluation_comments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  evaluation_id BIGINT UNSIGNED NOT NULL UNIQUE,
  comment_text TEXT NOT NULL,
  visibility ENUM('teacher','admin','private') NOT NULL DEFAULT 'teacher',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comments_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  message TEXT NOT NULL,
  read_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notifications_user_read (user_id, read_at, created_at)
) ENGINE=InnoDB;

CREATE TABLE settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  module VARCHAR(80) NOT NULL,
  record_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  details JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_module_date (module, created_at),
  INDEX idx_audit_user_date (user_id, created_at)
) ENGINE=InnoDB;

INSERT INTO roles (name, label) VALUES
  ('admin','Administrator'), ('teacher','Teacher'), ('student','Student');

INSERT INTO rating_scales (value, label, description) VALUES
  (1,'Poor','Needs significant improvement'),
  (2,'Needs Improvement','Below expectations'),
  (3,'Good','Meets expectations'),
  (4,'Very Good','Above expectations'),
  (5,'Excellent','Outstanding');

INSERT INTO question_categories (name, sort_order) VALUES
  ('Knowledge',1), ('Teaching',2), ('Management',3),
  ('Communication',4), ('Assessment',5), ('Professionalism',6);

INSERT INTO questions (category_id, prompt, question_type, sort_order)
SELECT id, CONCAT('The teacher demonstrates effective ', LOWER(name), '.'), 'rating', 1
FROM question_categories;

INSERT INTO settings (setting_key, setting_value) VALUES
  ('school_name','Teacher Evaluation System'),
  ('evaluation_anonymous','1'),
  ('comments_enabled','1'),
  ('results_visible_to_teachers','1');