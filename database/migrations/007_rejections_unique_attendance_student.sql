-- Rejection explanations: one row per class assignment (fixes duplicate INSERT errors and enables upsert).
-- Attendance: student-originated verification and reporter column.

SET NAMES utf8mb4;

-- Deduplicate before unique key (keeps the newest row per assignment)
DELETE re1 FROM rejection_explanations re1
INNER JOIN rejection_explanations re2
  ON re1.assignment_id = re2.assignment_id AND re1.id < re2.id;

ALTER TABLE rejection_explanations
  ADD UNIQUE KEY uq_re_explanation_assignment (assignment_id);

ALTER TABLE attendance_records
  MODIFY COLUMN verification_method ENUM('pin','manual','student','none') NOT NULL DEFAULT 'none';

ALTER TABLE attendance_records
  ADD COLUMN reported_by_student_id INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Set when a student attests to teacher presence/absence'
    AFTER verified_by_monitor_id,
  ADD KEY idx_attendance_student_reporter (reported_by_student_id),
  ADD CONSTRAINT fk_attendance_reported_student
    FOREIGN KEY (reported_by_student_id) REFERENCES users(id) ON DELETE SET NULL;
