-- Timetable: pending approval for two-teacher chip swaps. Student: teacher-seen at class time.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS tt_swap_proposals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_id INT UNSIGNED NOT NULL,
    placement_a_id INT UNSIGNED NOT NULL,
    placement_b_id INT UNSIGNED NOT NULL,
    requester_user_id INT UNSIGNED NOT NULL,
    assignee_user_id INT UNSIGNED NULL COMMENT 'Other teacher who must approve; NULL = class monitors',
    needs_class_monitor TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    resolver_user_id INT UNSIGNED NULL,
    FOREIGN KEY (program_id) REFERENCES tt_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (placement_a_id) REFERENCES tt_placements(id) ON DELETE CASCADE,
    FOREIGN KEY (placement_b_id) REFERENCES tt_placements(id) ON DELETE CASCADE,
    INDEX idx_swap_proposal_pending (status, assignee_user_id),
    INDEX idx_swap_proposal_req (status, requester_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_teacher_presence (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    schedule_id INT UNSIGNED NOT NULL,
    class_date DATE NOT NULL,
    teacher_present TINYINT(1) NOT NULL COMMENT '1=seen, 0=not seen',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    UNIQUE KEY uk_stu_presence (student_id, schedule_id, class_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
