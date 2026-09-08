-- Interactive program timetable (CAIML-style board): drag/drop + exchange requests.
-- Run: mysql -u U -p DB < database/migrations/002_timetable_board.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS tt_programs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    session_info VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tt_periods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_id INT UNSIGNED NOT NULL,
    period_index TINYINT UNSIGNED NOT NULL COMMENT '1=I..7=VII, teaching slots only; lunch is visual only',
    label VARCHAR(8) NOT NULL,
    time_range VARCHAR(32) NOT NULL,
    sort_order TINYINT UNSIGNED NOT NULL,
    FOREIGN KEY (program_id) REFERENCES tt_programs(id) ON DELETE CASCADE,
    UNIQUE KEY uk_period (program_id, period_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tt_cells (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_id INT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL COMMENT '1=Mon .. 5=Fri',
    period_index TINYINT UNSIGNED NOT NULL,
    course_code VARCHAR(32) NULL,
    course_name VARCHAR(255) NULL,
    venue VARCHAR(128) NULL,
    display_note VARCHAR(64) NULL COMMENT 'e.g. (T) or (P) or P',
    FOREIGN KEY (program_id) REFERENCES tt_programs(id) ON DELETE CASCADE,
    KEY idx_cell_day (program_id, day_of_week, period_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Which teachers may appear on this board for a given course (for drag rules).
CREATE TABLE IF NOT EXISTS tt_teacher_courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    course_code VARCHAR(32) NOT NULL,
    FOREIGN KEY (program_id) REFERENCES tt_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_teach_course (program_id, user_id, course_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- At most one teaching assignment per cell on the board (instructor of record for planning).
CREATE TABLE IF NOT EXISTS tt_placements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cell_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cell_id) REFERENCES tt_cells(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_placements_cell (cell_id),
    KEY idx_placements_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tt_exchange_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_id INT UNSIGNED NOT NULL,
    from_user_id INT UNSIGNED NOT NULL,
    to_user_id INT UNSIGNED NOT NULL,
    from_cell_id INT UNSIGNED NOT NULL,
    to_cell_id INT UNSIGNED NOT NULL,
    message TEXT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_by_user_id INT UNSIGNED NULL,
    resolved_at DATETIME NULL,
    FOREIGN KEY (program_id) REFERENCES tt_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (from_cell_id) REFERENCES tt_cells(id) ON DELETE CASCADE,
    FOREIGN KEY (to_cell_id) REFERENCES tt_cells(id) ON DELETE CASCADE,
    KEY idx_exch_status (status, program_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
