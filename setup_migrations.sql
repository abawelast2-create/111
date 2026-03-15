-- Create personal_access_tokens table if not exists
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text NULL,
  `last_used_at` timestamp NULL,
  `expires_at` timestamp NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Add updated_at to employees if not exists
-- ALTER TABLE employees ADD COLUMN IF NOT EXISTS updated_at timestamp NULL;

-- Register all migrations as already run
INSERT IGNORE INTO migrations (migration, batch) VALUES
('2019_12_14_000001_create_personal_access_tokens_table', 1),
('2024_01_01_000001_create_branches_table', 1),
('2024_01_01_000002_create_admins_table', 1),
('2024_01_01_000003_create_employees_table', 1),
('2024_01_01_000004_create_attendances_table', 1),
('2024_01_01_000005_create_settings_table', 1),
('2024_01_01_000006_create_login_attempts_table', 1),
('2024_01_01_000007_create_audit_log_table', 1),
('2024_01_01_000008_create_known_devices_table', 1),
('2024_01_01_000009_create_leaves_table', 1),
('2024_01_01_000010_create_secret_reports_table', 1),
('2024_01_01_000011_create_tampering_cases_table', 1);

-- Add updated_at column to tables that might not have it
ALTER TABLE `employees` ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL;
ALTER TABLE `branches` ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL;
ALTER TABLE `admins` ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL;
ALTER TABLE `attendances` ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL;
ALTER TABLE `settings` ADD COLUMN IF NOT EXISTS `description` varchar(255) NULL;
ALTER TABLE `leaves` ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL;
