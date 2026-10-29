-- Aliados (2026-10-28). No crea tablas nuevas: agrega dos columnas a tablas existentes.
-- Requisito: la tabla users ya tiene la columna role_id (migración de roles, 2026_10_19).
-- Ejecutar una sola vez en la base de datos de MySQL/MariaDB.

ALTER TABLE `users`
    ADD COLUMN `is_ally` TINYINT(1) NOT NULL DEFAULT 0 AFTER `role_id`,
    ADD INDEX `users_is_ally_index` (`is_ally`);

ALTER TABLE `cr_cars`
    ADD COLUMN `owner_id` BIGINT UNSIGNED NULL AFTER `vendor_id`,
    ADD INDEX `cr_cars_owner_id_foreign` (`owner_id`),
    ADD CONSTRAINT `cr_cars_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- Registrar la migración para que `php artisan migrate` no la vuelva a ejecutar.
-- Si usas este archivo, no ejecutes también la migración de Laravel.
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_10_28_000000_add_allies', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;
