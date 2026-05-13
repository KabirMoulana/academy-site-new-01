-- ============================================================
-- EduCore Academy Management System — MySQL Database Schema
-- Run this in phpMyAdmin or MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS educore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE educore;

-- ─── USERS (base class) ────────────────────────────────────
CREATE TABLE users (
  userID       INT AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(100) NOT NULL,
  email        VARCHAR(150) NOT NULL UNIQUE,
  passwordHash VARCHAR(255) NOT NULL,
  role         ENUM('admin','manager','director','lecturer','student','parent','receptionist') NOT NULL,
  isActive     TINYINT(1) DEFAULT 1,
  createdAt    DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ─── ADMINS ────────────────────────────────────────────────
CREATE TABLE admins (
  adminID           INT AUTO_INCREMENT PRIMARY KEY,
  userID            INT NOT NULL UNIQUE,
  adminAccessLevel  VARCHAR(50) DEFAULT 'Admin',
  adminGrantedDate  DATE,
  adminRevokedDate  DATE,
  FOREIGN KEY (userID) REFERENCES users(userID) ON DELETE CASCADE
);

-- ─── MANAGERS ──────────────────────────────────────────────
CREATE TABLE managers (
  managerID           INT AUTO_INCREMENT PRIMARY KEY,
  userID              INT NOT NULL UNIQUE,
  department          VARCHAR(100),
  managerGrantedDate  DATE,
  managerRevokedDate  DATE,
  FOREIGN KEY (userID) REFERENCES users(userID) ON DELETE CASCADE
);

-- ─── DIRECTORS ─────────────────────────────────────────────
CREATE TABLE directors (
  directorID           INT AUTO_INCREMENT PRIMARY KEY,
  userID               INT NOT NULL UNIQUE,
  title                VARCHAR(100),
  directorGrantedDate  DATE,
  directorRevokedDate  DATE,
  FOREIGN KEY (userID) REFERENCES users(userID) ON DELETE CASCADE
);

-- ─── LECTURERS ─────────────────────────────────────────────
CREATE TABLE lecturers (
  lecturerID           INT AUTO_INCREMENT PRIMARY KEY,
  userID               INT NOT NULL UNIQUE,
  qualification        VARCHAR(150),
  specialization       VARCHAR(150),
  lecturerGrantedDate  DATE,
  lecturerRevokedDate  DATE,
  FOREIGN KEY (userID) REFERENCES users(userID) ON DELETE CASCADE
);

-- ─── STUDENTS ──────────────────────────────────────────────
CREATE TABLE students (
  studentID           INT AUTO_INCREMENT PRIMARY KEY,
  userID              INT NOT NULL UNIQUE,
  type                ENUM('physical','online') DEFAULT 'online',
  grade               VARCHAR(20),
  dateOfBirth         DATE,
  studentGrantedDate  DATE,
  studentRevokedDate  DATE,
  FOREIGN KEY (userID) REFERENCES users(userID) ON DELETE CASCADE
);

-- ─── PARENTS ───────────────────────────────────────────────
CREATE TABLE parents (
  parentID            INT AUTO_INCREMENT PRIMARY KEY,
  userID              INT NOT NULL UNIQUE,
  contactNo           VARCHAR(20),
  linkedStudentID     INT,
  parentGrantedDate   DATE,
  parentRevokedDate   DATE,
  FOREIGN KEY (userID) REFERENCES users(userID) ON DELETE CASCADE,
  FOREIGN KEY (linkedStudentID) REFERENCES students(studentID) ON DELETE SET NULL
);

-- ─── RECEPTIONISTS ─────────────────────────────────────────
CREATE TABLE receptionists (
  receptionistID           INT AUTO_INCREMENT PRIMARY KEY,
  userID                   INT NOT NULL UNIQUE,
  assignedCounter          VARCHAR(50),
  receptionistGrantedDate  DATE,
  receptionistRevokedDate  DATE,
  FOREIGN KEY (userID) REFERENCES users(userID) ON DELETE CASCADE
);

-- ─── SUBJECTS ──────────────────────────────────────────────
CREATE TABLE subjects (
  subjectID          INT AUTO_INCREMENT PRIMARY KEY,
  name               VARCHAR(100) NOT NULL,
  syllabus           VARCHAR(200),
  level              VARCHAR(50),
  subjectDescription TEXT
);

-- ─── BATCHES ───────────────────────────────────────────────
CREATE TABLE batches (
  batchID        INT AUTO_INCREMENT PRIMARY KEY,
  batchName      VARCHAR(100) NOT NULL,
  schedule       VARCHAR(150),
  mode           ENUM('online','physical') DEFAULT 'online',
  batchStartDate DATE,
  batchEndDate   DATE
);

-- ─── ENROLLMENTS ───────────────────────────────────────────
CREATE TABLE enrollments (
  enrollmentID  INT AUTO_INCREMENT PRIMARY KEY,
  studentID     INT NOT NULL,
  batchID       INT NOT NULL,
  enrollDate    DATE DEFAULT (CURRENT_DATE),
  unenrollDate  DATE,
  status        ENUM('active','inactive') DEFAULT 'active',
  FOREIGN KEY (studentID) REFERENCES students(studentID) ON DELETE CASCADE,
  FOREIGN KEY (batchID)   REFERENCES batches(batchID)   ON DELETE CASCADE
);

-- ─── CLASS SESSIONS ────────────────────────────────────────
CREATE TABLE class_sessions (
  sessionID     INT AUTO_INCREMENT PRIMARY KEY,
  batchID       INT NOT NULL,
  subjectID     INT NOT NULL,
  lecturerID    INT NOT NULL,
  date          DATE NOT NULL,
  classLink     VARCHAR(255),
  recording     VARCHAR(255),
  sessionStatus VARCHAR(50) DEFAULT 'Upcoming',
  FOREIGN KEY (batchID)    REFERENCES batches(batchID)     ON DELETE CASCADE,
  FOREIGN KEY (subjectID)  REFERENCES subjects(subjectID)  ON DELETE CASCADE,
  FOREIGN KEY (lecturerID) REFERENCES lecturers(lecturerID) ON DELETE CASCADE
);

-- ─── ATTENDANCE ────────────────────────────────────────────
CREATE TABLE attendance (
  attendanceID   INT AUTO_INCREMENT PRIMARY KEY,
  sessionID      INT NOT NULL,
  studentID      INT NOT NULL,
  date           DATE NOT NULL,
  status         ENUM('present','absent') DEFAULT 'present',
  attendanceNote VARCHAR(255),
  FOREIGN KEY (sessionID) REFERENCES class_sessions(sessionID) ON DELETE CASCADE,
  FOREIGN KEY (studentID) REFERENCES students(studentID)        ON DELETE CASCADE
);

-- ─── EXAM RESULTS ──────────────────────────────────────────
CREATE TABLE exam_results (
  resultID    INT AUTO_INCREMENT PRIMARY KEY,
  studentID   INT NOT NULL,
  subjectID   INT NOT NULL,
  lecturerID  INT NOT NULL,
  marks       FLOAT NOT NULL,
  resultGrade VARCHAR(10),
  examDate    DATE,
  FOREIGN KEY (studentID)  REFERENCES students(studentID)   ON DELETE CASCADE,
  FOREIGN KEY (subjectID)  REFERENCES subjects(subjectID)   ON DELETE CASCADE,
  FOREIGN KEY (lecturerID) REFERENCES lecturers(lecturerID) ON DELETE CASCADE
);

-- ─── PAYMENTS ──────────────────────────────────────────────
CREATE TABLE payments (
  paymentID  INT AUTO_INCREMENT PRIMARY KEY,
  studentID  INT NOT NULL,
  amount     FLOAT NOT NULL,
  month      DATE NOT NULL,
  status     ENUM('pending','approved') DEFAULT 'pending',
  paidAt     DATETIME,
  FOREIGN KEY (studentID) REFERENCES students(studentID) ON DELETE CASCADE
);

-- ─── RECEIPTS ──────────────────────────────────────────────
CREATE TABLE receipts (
  receiptID     INT AUTO_INCREMENT PRIMARY KEY,
  paymentID     INT NOT NULL,
  issueDate     DATE DEFAULT (CURRENT_DATE),
  receiptAmount FLOAT NOT NULL,
  receiptNote   VARCHAR(255),
  FOREIGN KEY (paymentID) REFERENCES payments(paymentID) ON DELETE CASCADE
);

-- ─── FEEDBACK ──────────────────────────────────────────────
CREATE TABLE feedback (
  feedbackID     INT AUTO_INCREMENT PRIMARY KEY,
  studentID      INT NOT NULL,
  subjectID      INT NOT NULL,
  comment        TEXT,
  feedbackRating INT CHECK (feedbackRating BETWEEN 1 AND 5),
  givenAt        DATETIME DEFAULT CURRENT_TIMESTAMP,
  feedbackStatus VARCHAR(50) DEFAULT 'Pending',
  FOREIGN KEY (studentID) REFERENCES students(studentID) ON DELETE CASCADE,
  FOREIGN KEY (subjectID) REFERENCES subjects(subjectID) ON DELETE CASCADE
);

-- ─── STUDY MATERIALS ───────────────────────────────────────
CREATE TABLE study_materials (
  materialID  INT AUTO_INCREMENT PRIMARY KEY,
  subjectID   INT NOT NULL,
  lecturerID  INT NOT NULL,
  title       VARCHAR(200) NOT NULL,
  fileURL     VARCHAR(255),
  fileType    VARCHAR(50),
  uploadedAt  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (subjectID)  REFERENCES subjects(subjectID)   ON DELETE CASCADE,
  FOREIGN KEY (lecturerID) REFERENCES lecturers(lecturerID) ON DELETE CASCADE
);

-- ─── ANNOUNCEMENTS ─────────────────────────────────────────
CREATE TABLE announcements (
  announcementID     INT AUTO_INCREMENT PRIMARY KEY,
  userID             INT NOT NULL,
  title              VARCHAR(200) NOT NULL,
  content            TEXT,
  postedAt           DATETIME DEFAULT CURRENT_TIMESTAMP,
  announcementStatus ENUM('Published','Draft') DEFAULT 'Published',
  FOREIGN KEY (userID) REFERENCES users(userID) ON DELETE CASCADE
);

-- ─── NOTIFICATIONS ─────────────────────────────────────────
CREATE TABLE notifications (
  notificationID   INT AUTO_INCREMENT PRIMARY KEY,
  userID           INT NOT NULL,
  message          TEXT NOT NULL,
  isRead           TINYINT(1) DEFAULT 0,
  notificationType VARCHAR(50),
  sentAt           DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (userID) REFERENCES users(userID) ON DELETE CASCADE
);

-- ─── PERFORMANCE POINTS ────────────────────────────────────
CREATE TABLE performance_points (
  pointID     INT AUTO_INCREMENT PRIMARY KEY,
  studentID   INT NOT NULL,
  points      INT NOT NULL,
  pointReason VARCHAR(255),
  awardedAt   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (studentID) REFERENCES students(studentID) ON DELETE CASCADE
);

-- ─── LEADERBOARD ───────────────────────────────────────────
CREATE TABLE leaderboard (
  entryID      INT AUTO_INCREMENT PRIMARY KEY,
  studentID    INT NOT NULL UNIQUE,
  rank         INT,
  totalPoints  INT DEFAULT 0,
  lastUpdated  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (studentID) REFERENCES students(studentID) ON DELETE CASCADE
);

-- ─── ACADEMY PERFORMANCE ───────────────────────────────────
CREATE TABLE academy_performance (
  performanceID   INT AUTO_INCREMENT PRIMARY KEY,
  overallRating   FLOAT,
  reviewedPeriod  VARCHAR(100),
  reviewedAt      DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ─── REPORTS ───────────────────────────────────────────────
CREATE TABLE reports (
  reportID      INT AUTO_INCREMENT PRIMARY KEY,
  type          VARCHAR(100),
  generatedAt   DATETIME DEFAULT CURRENT_TIMESTAMP,
  reportPeriod  VARCHAR(100),
  reportFileURL VARCHAR(255)
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default admin user (password: admin123)
INSERT INTO users (name, email, passwordHash, role, isActive) VALUES
('Super Admin', 'admin@educore.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
('Kumari Fernando', 'manager@educore.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 1),
('Dr. Nimal Perera', 'director@educore.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'director', 1),
('Arun Silva', 'lecturer@educore.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer', 1),
('Anura Kumara', 'student@educore.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('Sanduni Perera', 'parent@educore.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 1),
('Saman Wickrama', 'receptionist@educore.lk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist', 1);

INSERT INTO admins (userID, adminAccessLevel, adminGrantedDate) VALUES (1, 'Super Admin', CURDATE());
INSERT INTO managers (userID, department, managerGrantedDate) VALUES (2, 'Operations', CURDATE());
INSERT INTO directors (userID, title, directorGrantedDate) VALUES (3, 'Executive Director', CURDATE());
INSERT INTO lecturers (userID, qualification, specialization, lecturerGrantedDate) VALUES (4, 'MSc Mathematics', 'Applied Mathematics', CURDATE());
INSERT INTO students (userID, type, grade, dateOfBirth, studentGrantedDate) VALUES (5, 'online', 'A/L', '2005-03-14', CURDATE());
INSERT INTO receptionists (userID, assignedCounter, receptionistGrantedDate) VALUES (7, 'Counter 1', CURDATE());

INSERT INTO subjects (name, syllabus, level, subjectDescription) VALUES
('Mathematics', 'A/L Combined Maths', 'Advanced', 'Combined Mathematics for A/L students'),
('Physics', 'A/L Physics', 'Advanced', 'Physics for A/L students'),
('Chemistry', 'A/L Chemistry', 'Advanced', 'Chemistry for A/L students'),
('ICT', 'O/L ICT', 'Ordinary', 'Information & Communication Technology');

INSERT INTO batches (batchName, schedule, mode, batchStartDate, batchEndDate) VALUES
('Batch A – 2025', 'Mon/Wed 6PM', 'online', '2025-01-10', '2025-12-20'),
('Batch B – 2025', 'Tue/Thu 4PM', 'physical', '2025-01-15', '2025-12-18');

INSERT INTO enrollments (studentID, batchID, enrollDate, status) VALUES (1, 1, CURDATE(), 'active');

INSERT INTO announcements (userID, title, content, announcementStatus) VALUES
(1, 'Mid-Year Examination Schedule Released', 'All students are advised to check the examination timetable for the mid-year exams starting July 15, 2025.', 'Published'),
(1, 'New Online Batch Registration Open', 'Registration for the new online batch (July intake) is now open. Limited seats available.', 'Published');
