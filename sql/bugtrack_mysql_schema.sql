-- =========================================================
-- BugTrack: MySQL database schema
-- Converted from the Buggy SQLite schema for a PHP + MySQL
-- Web-Based Bug Tracking and Project Monitoring System.
--
-- Tested design target: MySQL 8.0+ / MariaDB 10.5+
-- Import this file using phpMyAdmin or the MySQL command line.
-- =========================================================

CREATE DATABASE IF NOT EXISTS bugtrack
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bugtrack;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables in dependency-safe order so this script can be re-imported.
DROP TABLE IF EXISTS task_history;
DROP TABLE IF EXISTS sub_tasks;
DROP TABLE IF EXISTS user_inbox;
DROP TABLE IF EXISTS attachments;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS logs;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS job_lists;
DROP TABLE IF EXISTS user_projects;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------
-- 1. USERS
-- ---------------------------------------------------------
CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(80) NOT NULL,
    email VARCHAR(190) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 2. PROJECTS
-- ---------------------------------------------------------
CREATE TABLE projects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    color CHAR(7) NOT NULL DEFAULT '#007bff',
    text_color CHAR(7) NOT NULL DEFAULT '#ffffff',
    complete_moves_to VARCHAR(50) NOT NULL DEFAULT 'Resolved',
    owner_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_projects_owner (owner_id),
    CONSTRAINT fk_projects_owner
        FOREIGN KEY (owner_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 3. TASKS (used as bug reports in BugTrack)
-- ---------------------------------------------------------
CREATE TABLE tasks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'Bug',
    creator_id BIGINT UNSIGNED NOT NULL,
    assigned_to_id BIGINT UNSIGNED DEFAULT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    sort_order INT NOT NULL DEFAULT 0,
    priority VARCHAR(20) NOT NULL DEFAULT 'Medium',
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_tasks_project (project_id),
    KEY idx_tasks_creator (creator_id),
    KEY idx_tasks_assignee (assigned_to_id),
    KEY idx_tasks_status (status),
    KEY idx_tasks_priority (priority),
    KEY idx_tasks_project_status (project_id, status),
    KEY idx_tasks_created (created_at),
    CONSTRAINT fk_tasks_project
        FOREIGN KEY (project_id) REFERENCES projects(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_tasks_creator
        FOREIGN KEY (creator_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_tasks_assignee
        FOREIGN KEY (assigned_to_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 4. COMMENTS
-- ---------------------------------------------------------
CREATE TABLE comments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_comments_task (task_id),
    KEY idx_comments_user (user_id),
    CONSTRAINT fk_comments_task
        FOREIGN KEY (task_id) REFERENCES tasks(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_comments_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 5. ATTACHMENTS
-- parent_type is 'task' or 'comment'.
-- parent_id is polymorphic, so it is validated by PHP.
-- ---------------------------------------------------------
CREATE TABLE attachments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_type VARCHAR(20) NOT NULL,
    parent_id BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    original_filename VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attachments_parent (parent_type, parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 6. LOGS (general system activity)
-- ---------------------------------------------------------
CREATE TABLE logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_logs_user (user_id),
    KEY idx_logs_created (created_at),
    CONSTRAINT fk_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 7. TASK HISTORY (dedicated bug status/change history)
-- ---------------------------------------------------------
CREATE TABLE task_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED DEFAULT NULL,
    field_name VARCHAR(50) NOT NULL,
    old_value TEXT DEFAULT NULL,
    new_value TEXT DEFAULT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_task_history_task (task_id),
    KEY idx_task_history_user (user_id),
    KEY idx_task_history_changed (changed_at),
    CONSTRAINT fk_task_history_task
        FOREIGN KEY (task_id) REFERENCES tasks(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_task_history_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 8. JOB LISTS (retained from the original Buggy schema)
-- ---------------------------------------------------------
CREATE TABLE job_lists (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    owner_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_job_lists_owner (owner_id),
    CONSTRAINT fk_job_lists_owner
        FOREIGN KEY (owner_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 9. JOBS (retained from the original Buggy schema)
-- ---------------------------------------------------------
CREATE TABLE jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    list_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    assigned_to_id BIGINT UNSIGNED DEFAULT NULL,
    priority VARCHAR(20) NOT NULL DEFAULT 'Medium',
    is_one_time TINYINT(1) NOT NULL DEFAULT 1,
    recurring_period VARCHAR(20) DEFAULT NULL,
    recurring_value INT NOT NULL DEFAULT 1,
    start_date DATETIME DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'incomplete',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_jobs_list (list_id),
    KEY idx_jobs_assignee (assigned_to_id),
    KEY idx_jobs_status (status),
    CONSTRAINT fk_jobs_list
        FOREIGN KEY (list_id) REFERENCES job_lists(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_jobs_assignee
        FOREIGN KEY (assigned_to_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 10. USER INBOX
-- ---------------------------------------------------------
CREATE TABLE user_inbox (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL DEFAULT 'incomplete',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    due_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_user_inbox_user (user_id),
    KEY idx_user_inbox_job (job_id),
    CONSTRAINT fk_user_inbox_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_user_inbox_job
        FOREIGN KEY (job_id) REFERENCES jobs(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 11. USER PROJECT MEMBERSHIP
-- ---------------------------------------------------------
CREATE TABLE user_projects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    is_pinned TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_project (user_id, project_id),
    KEY idx_user_projects_project (project_id),
    CONSTRAINT fk_user_projects_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_user_projects_project
        FOREIGN KEY (project_id) REFERENCES projects(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 12. SETTINGS
-- ---------------------------------------------------------
CREATE TABLE settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT DEFAULT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_settings_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 13. SESSIONS
-- ---------------------------------------------------------
CREATE TABLE sessions (
    id VARCHAR(128) NOT NULL,
    access INT DEFAULT NULL,
    data MEDIUMTEXT DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 14. SUB-TASKS
-- ---------------------------------------------------------
CREATE TABLE sub_tasks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id BIGINT UNSIGNED NOT NULL,
    description VARCHAR(500) NOT NULL,
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sub_tasks_task (task_id),
    CONSTRAINT fk_sub_tasks_task
        FOREIGN KEY (task_id) REFERENCES tasks(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- Suggested task statuses:
-- Open, In Progress, Ready for QA, Resolved, Closed
--
-- Suggested priorities:
-- Low, Medium, High, Critical
--
-- No default admin account is inserted. Create users through
-- the PHP registration/admin flow using password_hash().
-- ---------------------------------------------------------
