-- Allow teachers to withdraw from an accepted assignment (soft status).
ALTER TABLE class_assignments
    MODIFY COLUMN status ENUM('offered','accepted','rejected','overridden','withdrawn') NOT NULL DEFAULT 'offered';
