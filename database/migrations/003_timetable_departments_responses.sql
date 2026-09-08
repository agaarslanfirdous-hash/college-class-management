-- Departments (SKUAST-style faculties), program link, teacher slot accept/decline.
-- Run after: schema.sql, seed, 002_timetable_board.sql, 002_timetable_board_seed_caiml.sql

SET NAMES utf8mb4;

-- Per-slot: teacher can confirm, or decline with a reason
ALTER TABLE tt_placements
  ADD COLUMN availability_status ENUM('pending','confirmed','declined') NOT NULL DEFAULT 'pending' AFTER user_id,
  ADD COLUMN decline_reason ENUM('personal','meeting','leave','other') NULL DEFAULT NULL,
  ADD COLUMN decline_note VARCHAR(500) NULL DEFAULT NULL,
  ADD COLUMN responded_at DATETIME NULL DEFAULT NULL;
-- Re-run: if "Duplicate column" ignore this file or drop columns first

-- Existing data: treat as already committed to teach
UPDATE tt_placements SET availability_status = 'confirmed', responded_at = COALESCE(responded_at, NOW()) WHERE 1=1;

INSERT INTO departments (name, code) VALUES
('Faculty of Agriculture, Wadura', 'FOA_WAD'),
('Faculty of Horticulture, Shalimar', 'FOH_SHL'),
('College of Agricultural Engg. & Technology (COAE&T)', 'COAET'),
('Centre for AI & Machine Learning (CAIML)', 'CAIML'),
('Faculty of Veterinary Sciences & Animal Biotech, Shuhama', 'FVS_SGM'),
('Faculty of Fishery Sciences, Rangreth', 'FFS_RNG'),
('Faculty of Basic Sciences & Humanities', 'FOBS'),
('School of Agri-Technology & Business (SATB)', 'SATB'),
('Directorate of Research', 'DOR'),
('Directorate of Extension (incl. KVK network)', 'KVK_EXT'),
('Shalimar Main Campus (Admin & Support)', 'CAMPUS_MAIN')
ON DUPLICATE KEY UPDATE name = VALUES(name);

ALTER TABLE tt_programs
  ADD COLUMN department_id INT UNSIGNED NULL AFTER id;
ALTER TABLE tt_programs
  ADD KEY idx_tt_prog_dept (department_id);
ALTER TABLE tt_programs
  ADD CONSTRAINT fk_tt_programs_dept
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL;
-- Re-run: skip if duplicate column / duplicate key name

UPDATE tt_programs p
  INNER JOIN departments d ON d.code = 'CAIML'
  SET p.department_id = d.id WHERE p.id = 1;

-- Second board (copy of program 1) for Horticulture demo, once only
SET @d_foh := (SELECT id FROM departments WHERE code = 'FOH_SHL' LIMIT 1);
INSERT INTO tt_programs (title, session_info, is_active, department_id)
SELECT 'B.Sc. (Hons.) Horticulture — board (demo)', 'Session: Spring 2026 | Faculty of Horticulture', 1, @d_foh
FROM DUAL
WHERE @d_foh IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM tt_programs WHERE title = 'B.Sc. (Hons.) Horticulture — board (demo)');

SET @np := (SELECT id FROM tt_programs WHERE title = 'B.Sc. (Hons.) Horticulture — board (demo)' LIMIT 1);

INSERT INTO tt_periods (program_id, period_index, label, time_range, sort_order)
SELECT @np, t.period_index, t.label, t.time_range, t.sort_order
FROM tt_periods t
WHERE t.program_id = 1
  AND @np IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM tt_periods x WHERE x.program_id = @np);

INSERT INTO tt_cells (program_id, day_of_week, period_index, course_code, course_name, venue, display_note)
SELECT @np, c.day_of_week, c.period_index, c.course_code, c.course_name, c.venue, c.display_note
FROM tt_cells c
WHERE c.program_id = 1
  AND @np IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM tt_cells x WHERE x.program_id = @np);

INSERT INTO tt_teacher_courses (program_id, user_id, course_code)
SELECT @np, t.user_id, t.course_code
FROM tt_teacher_courses t
WHERE t.program_id = 1 AND @np IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM tt_teacher_courses y WHERE y.program_id = @np);

INSERT INTO tt_placements (cell_id, user_id, availability_status, decline_reason, decline_note, responded_at)
SELECT c2.id, p.user_id, 'confirmed', NULL, NULL, NOW()
FROM tt_placements p
INNER JOIN tt_cells c1 ON c1.id = p.cell_id AND c1.program_id = 1
INNER JOIN tt_cells c2 ON c2.program_id = @np
  AND c2.day_of_week = c1.day_of_week
  AND c2.period_index = c1.period_index
LEFT JOIN tt_placements ex ON ex.cell_id = c2.id
WHERE @np IS NOT NULL AND ex.id IS NULL;
