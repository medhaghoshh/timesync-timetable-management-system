-- =========================================================
-- TimeSync - Smart Timetable Management & Personalization
-- Database Schema + Seed Data
-- =========================================================

CREATE DATABASE IF NOT EXISTS timesync_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE timesync_db;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','student') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (role)
) ENGINE=InnoDB;

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    semester_number INT NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    semester_id INT NOT NULL,
    section_name VARCHAR(5) NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_section (department_id, semester_id, section_name)
) ENGINE=InnoDB;

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    roll_number VARCHAR(30) NOT NULL UNIQUE,
    department_id INT NOT NULL,
    semester_id INT NOT NULL,
    section_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (semester_id) REFERENCES semesters(id),
    FOREIGN KEY (section_id) REFERENCES sections(id),
    INDEX idx_student_lookup (department_id, semester_id, section_id)
) ENGINE=InnoDB;

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    designation VARCHAR(100) DEFAULT 'Administrator',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(150) NOT NULL,
    department_id INT NOT NULL,
    semester_id INT NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_subject (subject_code, department_id)
) ENGINE=InnoDB;

CREATE TABLE faculty (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150),
    department_id INT NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(30) NOT NULL,
    building VARCHAR(80) DEFAULT 'Main Building',
    capacity INT DEFAULT 60,
    UNIQUE KEY uniq_room (room_number, building)
) ENGINE=InnoDB;

CREATE TABLE uploaded_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    records_imported INT DEFAULT 0,
    records_failed INT DEFAULT 0,
    conflicts_found INT DEFAULT 0,
    status ENUM('processing','completed','failed') DEFAULT 'processing',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    subject_id INT NOT NULL,
    faculty_id INT NOT NULL,
    room_id INT NOT NULL,
    department_id INT NOT NULL,
    semester_id INT NOT NULL,
    section_id INT NOT NULL,
    uploaded_file_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_file_id) REFERENCES uploaded_files(id) ON DELETE SET NULL,
    INDEX idx_section_day (section_id, day),
    INDEX idx_faculty_day (faculty_id, day),
    INDEX idx_room_day (room_id, day)
) ENGINE=InnoDB;

CREATE TABLE conflicts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conflict_type ENUM('section','faculty','room','duplicate') NOT NULL,
    severity ENUM('HIGH','MEDIUM','LOW') NOT NULL DEFAULT 'MEDIUM',
    timetable_id_1 INT NOT NULL,
    timetable_id_2 INT NOT NULL,
    description TEXT,
    status ENUM('open','resolved','ignored') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (timetable_id_1) REFERENCES timetables(id) ON DELETE CASCADE,
    FOREIGN KEY (timetable_id_2) REFERENCES timetables(id) ON DELETE CASCADE,
    INDEX idx_conflict_type (conflict_type),
    INDEX idx_conflict_status (status)
) ENGINE=InnoDB;

-- =========================================================
-- SEED DATA
-- =========================================================

INSERT INTO departments (name, code) VALUES
('Computer Science & Engineering', 'CSE'),
('Electronics & Communication', 'ECE'),
('Information Technology', 'IT'),
('Mechanical Engineering', 'ME'),
('Civil Engineering', 'CE');

INSERT INTO semesters (semester_number) VALUES (1),(2),(3),(4),(5),(6),(7),(8);

INSERT INTO sections (department_id, semester_id, section_name) VALUES
(1,5,'A'),(1,5,'B'),(1,5,'C'),
(3,5,'A'),(3,5,'B'),
(2,5,'A'),(4,5,'A'),(5,5,'A');

INSERT INTO subjects (subject_code, subject_name, department_id, semester_id) VALUES
('CS501','Database Management Systems',1,5),
('CS502','Operating Systems',1,5),
('CS503','Computer Networks',1,5),
('CS504','Software Engineering',1,5),
('CS505','Artificial Intelligence',1,5),
('CS506','Machine Learning',1,5),
('CS507','Web Technology',1,5),
('CS508','Compiler Design',1,5),
('IT501','Database Management Systems',3,5),
('IT502','Operating Systems',3,5),
('IT503','Web Technology',3,5);

INSERT INTO faculty (name, email, department_id) VALUES
('Dr. Sharma','sharma@college.edu',1),
('Prof. Roy','roy@college.edu',1),
('Prof. Sen','sen@college.edu',1),
('Dr. Gupta','gupta@college.edu',1),
('Dr. Iyer','iyer@college.edu',1),
('Prof. Nair','nair@college.edu',3),
('Dr. Verma','verma@college.edu',3);

INSERT INTO rooms (room_number, building, capacity) VALUES
('204','Main Building',70),
('301','Main Building',60),
('Lab 2','CS Block',40),
('305','Main Building',60),
('Lab 1','CS Block',40),
('210','Main Building',65);

-- Demo admin user (password: Admin@123)
INSERT INTO users (name, email, password, role) VALUES
('System Admin','admin@timesync.com','$2b$10$IvE2Qy.vMjIDzKamBhsh/eST5PS1Hupo2ursuZ3KP8zEAhESTChnC','admin');
-- Demo student user (password: Student@123)
INSERT INTO users (name, email, password, role) VALUES
('Aditi Verma','student@timesync.com','$2b$10$lbWhJf7OsB.oUBDwpJvXNu.cRtai6pErdDtC6GZsR29gAniQPVsqq','student');

INSERT INTO admins (user_id, designation) VALUES (1, 'System Administrator');
INSERT INTO students (user_id, roll_number, department_id, semester_id, section_id) VALUES
(2, 'CSE2023001', 1, 5, 1);

INSERT INTO users (name, email, password, role) VALUES
('Rohan Das','rohan.das@student.timesync.com','$2b$10$lbWhJf7OsB.oUBDwpJvXNu.cRtai6pErdDtC6GZsR29gAniQPVsqq','student'),
('Priya Nair','priya.nair@student.timesync.com','$2b$10$lbWhJf7OsB.oUBDwpJvXNu.cRtai6pErdDtC6GZsR29gAniQPVsqq','student'),
('Karan Mehta','karan.mehta@student.timesync.com','$2b$10$lbWhJf7OsB.oUBDwpJvXNu.cRtai6pErdDtC6GZsR29gAniQPVsqq','student');

INSERT INTO students (user_id, roll_number, department_id, semester_id, section_id) VALUES
(3, 'CSE2023002', 1, 5, 1),
(4, 'CSE2023045', 1, 5, 2),
(5, 'IT2023010', 3, 5, 1);

INSERT INTO uploaded_files (file_name, file_path, uploaded_by, records_imported, records_failed, conflicts_found, status) VALUES
('CSE_A_Sem5.xlsx', 'uploads/CSE_A_Sem5.xlsx', 1, 24, 0, 0, 'completed');

-- Sample timetable data for CSE Sem5 Section A (section_id = 1)
INSERT INTO timetables (day, start_time, end_time, subject_id, faculty_id, room_id, department_id, semester_id, section_id, uploaded_file_id) VALUES
('Monday','09:00:00','10:00:00',1,1,1,1,5,1,1),
('Monday','10:00:00','11:00:00',2,2,2,1,5,1,1),
('Monday','11:15:00','12:15:00',3,3,3,1,5,1,1),
('Monday','12:15:00','13:15:00',4,4,4,1,5,1,1),
('Monday','14:00:00','15:00:00',5,5,1,1,5,1,1),

('Tuesday','09:00:00','10:00:00',2,2,2,1,5,1,1),
('Tuesday','10:00:00','11:00:00',1,1,1,1,5,1,1),
('Tuesday','11:15:00','12:15:00',6,1,5,1,5,1,1),
('Tuesday','12:15:00','13:15:00',7,3,4,1,5,1,1),
('Tuesday','14:00:00','16:00:00',3,3,3,1,5,1,1),

('Wednesday','09:00:00','10:00:00',4,4,4,1,5,1,1),
('Wednesday','10:00:00','11:00:00',5,5,1,1,5,1,1),
('Wednesday','11:15:00','12:15:00',1,1,1,1,5,1,1),
('Wednesday','12:15:00','13:15:00',8,2,2,1,5,1,1),

('Thursday','09:00:00','10:00:00',6,1,5,1,5,1,1),
('Thursday','10:00:00','11:00:00',7,3,4,1,5,1,1),
('Thursday','11:15:00','12:15:00',2,2,2,1,5,1,1),
('Thursday','14:00:00','15:00:00',3,3,3,1,5,1,1),

('Friday','09:00:00','10:00:00',5,5,1,1,5,1,1),
('Friday','10:00:00','11:00:00',4,4,4,1,5,1,1),
('Friday','11:15:00','12:15:00',8,2,2,1,5,1,1),
('Friday','12:15:00','13:15:00',1,1,1,1,5,1,1),

('Saturday','09:00:00','10:00:00',7,3,4,1,5,1,1),
('Saturday','10:00:00','11:00:00',6,1,5,1,5,1,1);

-- Section B timetable (section_id = 2) - includes intentional FACULTY and ROOM conflicts with Section A for demo purposes
INSERT INTO timetables (day, start_time, end_time, subject_id, faculty_id, room_id, department_id, semester_id, section_id, uploaded_file_id) VALUES
('Monday','09:30:00','10:30:00',1,1,2,1,5,2,1),
('Monday','10:30:00','11:30:00',2,2,3,1,5,2,1),
('Tuesday','09:00:00','10:00:00',3,3,4,1,5,2,1),
('Wednesday','09:00:00','10:00:00',4,4,1,1,5,2,1),
('Wednesday','11:00:00','12:00:00',5,5,1,1,5,2,1),
('Thursday','09:00:00','10:00:00',6,1,5,1,5,2,1),
('Friday','09:00:00','10:00:00',7,3,4,1,5,2,1);
