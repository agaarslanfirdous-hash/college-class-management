-- Seed CAIML B.Tech AI timetable (from official PDF: Spring 2026, Sem 2, U2).
-- Run after 002_timetable_board.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE tt_exchange_requests;
TRUNCATE TABLE tt_placements;
TRUNCATE TABLE tt_teacher_courses;
TRUNCATE TABLE tt_cells;
TRUNCATE TABLE tt_periods;
DELETE FROM tt_programs;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO tt_programs (id, title, session_info) VALUES
(1, 'B.Tech Artificial Intelligence (CAIML)', 'Session: Spring 2026 | Semester: 2nd | Centre for AI & ML, SKUAST-K');

INSERT INTO tt_periods (program_id, period_index, label, time_range, sort_order) VALUES
(1, 1, 'I',   '09:30-10:30', 1),
(1, 2, 'II',  '10:30-11:30', 2),
(1, 3, 'III', '11:30-12:30', 3),
(1, 4, 'IV',  '12:30-1:30',  4),
(1, 5, 'V',   '1:30-2:30',   5),
(1, 6, 'VI',  '2:30-3:30',   6),
(1, 7, 'VII', '3:30-4:30',   7);

-- 5 days x 7 periods; use NULL/empty for free or Library blocks
INSERT INTO tt_cells (program_id, day_of_week, period_index, course_code, course_name, venue, display_note) VALUES
-- Mon: ECE101, MTH102, AGR103, EVS101 | CSE103(T), CSE104, —
(1, 1, 1, 'ECE101', 'Electronic Systems', 'CAIML', NULL),
(1, 1, 2, 'MTH102', 'Engineering Mathematics II', 'Agri Statistics', NULL),
(1, 1, 3, 'AGR103', 'Fund. Applied Sc. in Agriculture', 'Agronomy', NULL),
(1, 1, 4, 'EVS101', 'Env. Science & Disaster Mgmt', 'EVS', NULL),
(1, 1, 5, 'CSE103', 'Data Structures (C++)', 'CAIML', '(T)'),
(1, 1, 6, 'CSE104', 'Internet & Web Programming', 'CAIML', NULL),
(1, 1, 7, NULL, NULL, NULL, NULL),
-- Tue: MTH102(T), AGR104, CSE103, EVS101, ECE101P, —, —
(1, 2, 1, 'MTH102', 'Engineering Mathematics II', 'Agri Statistics', '(T)'),
(1, 2, 2, 'AGR104', 'Basics of Agri Engineering', 'CAIML', NULL),
(1, 2, 3, 'CSE103', 'Data Structures (C++)', 'CAIML', NULL),
(1, 2, 4, 'EVS101', 'Env. Science & Disaster Mgmt', 'EVS', NULL),
(1, 2, 5, 'ECE101P', 'Electronic Systems (Practical)', 'CAIML', 'P'),
(1, 2, 6, NULL, NULL, NULL, NULL),
(1, 2, 7, NULL, NULL, NULL, NULL),
-- Wed: MTH102, ECE101, AGR103, Library, CSE104P, Library, —
(1, 3, 1, 'MTH102', 'Engineering Mathematics II', 'Agri Statistics', NULL),
(1, 3, 2, 'ECE101', 'Electronic Systems', 'CAIML', NULL),
(1, 3, 3, 'AGR103', 'Fund. Applied Sc. in Agriculture', 'Agronomy', NULL),
(1, 3, 4, 'Lib', 'Library / self-study', 'Library', NULL),
(1, 3, 5, 'CSE104P', 'IWP (Practical)', 'CAIML', 'P'),
(1, 3, 6, 'Lib', 'Library / self-study', 'Library', NULL),
(1, 3, 7, NULL, NULL, NULL, NULL),
-- Thu: Library, AGR104, AGR104P, AGR103P, ECA, —, —
(1, 4, 1, 'Lib', 'Library / self-study', 'Library', NULL),
(1, 4, 2, 'AGR104', 'Basics of Agri Engineering', 'CAIML', NULL),
(1, 4, 3, 'AGR104P', 'Practical', 'CAIML', 'P'),
(1, 4, 4, 'AGR103P', 'Practical', 'Agronomy', 'P'),
(1, 4, 5, 'ECA', 'ECA / activity', 'CAIML', NULL),
(1, 4, 6, NULL, NULL, NULL, NULL),
(1, 4, 7, NULL, NULL, NULL, NULL),
-- Fri: EVS101P, CSE103P, Tutorial, —, —, —, —
(1, 5, 1, 'EVS101P', 'EVS Practical', 'EVS', 'P'),
(1, 5, 2, 'CSE103P', 'DS (Practical)', 'CAIML', 'P'),
(1, 5, 3, 'Tut', 'Tutorial', 'CAIML', NULL),
(1, 5, 4, NULL, NULL, NULL, NULL),
(1, 5, 5, NULL, NULL, NULL, NULL),
(1, 5, 6, NULL, NULL, NULL, NULL),
(1, 5, 7, NULL, NULL, NULL, NULL);

-- Map demo teacher (id from seed) to CSE + ECE courses; admin can edit users later.
-- Assumes users id: teacher=2 from seed. Adjust if needed.
INSERT INTO tt_teacher_courses (program_id, user_id, course_code) VALUES
(1, 2, 'CSE103'),
(1, 2, 'CSE104'),
(1, 2, 'CSE103P'),
(1, 2, 'CSE104P'),
(1, 2, 'ECE101'),
(1, 2, 'ECE101P');

-- Initial placement: teacher@college.edu on CSE103 Monday session (cell id will resolve)
INSERT INTO tt_placements (cell_id, user_id)
SELECT c.id, 2 FROM tt_cells c WHERE c.program_id = 1 AND c.day_of_week = 1 AND c.period_index = 5 AND c.course_code = 'CSE103' LIMIT 1;
