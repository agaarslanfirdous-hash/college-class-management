-- Section + batch/year for class offerings
ALTER TABLE classes
  ADD COLUMN section VARCHAR(64) NULL DEFAULT NULL COMMENT 'e.g. A, B, Morning' AFTER grade_level,
  ADD COLUMN academic_batch VARCHAR(64) NULL DEFAULT NULL COMMENT 'e.g. 2024-28, Year 1' AFTER section;
