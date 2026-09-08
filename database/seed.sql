-- Demo seed — all demo accounts use password: password (bcrypt below; change in production!)
-- Run after schema: mysql -u user -p college_cms < seed.sql

INSERT INTO departments (name, code) VALUES
('Computer Science', 'CS'),
('Mathematics', 'MATH');

INSERT INTO users (email, password_hash, role, full_name, is_active) VALUES
('admin@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', 1),
('teacher@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', 'Dr. Jane Teacher', 1),
('monitor@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'monitor', 'Class Monitor One', 1),
('student@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Student Demo', 1);

INSERT INTO classes (code, name, subject, department_id, grade_level, section, academic_batch, capacity) VALUES
('CS101', 'Intro to Programming', 'Programming', 1, 'Year 1', NULL, NULL, 40),
('MATH201', 'Calculus II', 'Mathematics', 2, 'Year 2', NULL, NULL, 35);

-- Example schedule: Monday 10:00-11:00 (day 1 = Monday if using 0=Sun)
INSERT INTO schedules (class_id, day_of_week, start_time, end_time, room, effective_from, effective_to) VALUES
(1, 1, '10:00:00', '11:00:00', 'Lab A', CURDATE(), NULL),
(2, 3, '14:00:00', '15:30:00', 'Room 201', CURDATE(), NULL);

-- Teacher must set check-in PIN from Profile after first login (stored as bcrypt).

INSERT INTO class_assignments (schedule_id, teacher_id, monitor_id, status, decided_at) VALUES
(1, (SELECT id FROM users WHERE email = 'teacher@college.edu'), (SELECT id FROM users WHERE email = 'monitor@college.edu'), 'accepted', NOW());

INSERT INTO enrollments (student_id, class_id) VALUES
((SELECT id FROM users WHERE email = 'student@college.edu'), 1),
((SELECT id FROM users WHERE email = 'student@college.edu'), 2);

INSERT INTO announcements (author_id, title, body, target_role, is_published) VALUES
((SELECT id FROM users WHERE email = 'admin@college.edu'), 'Welcome', 'Welcome to the College Class Management System.', 'all', 1);
