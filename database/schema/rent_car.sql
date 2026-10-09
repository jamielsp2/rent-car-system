-- Rent Car System: structure of the tables the application uses.
-- Loaded by the migration 2026_01_01_000000_create_rent_car_schema (and by the
-- installer). Every statement is CREATE TABLE IF NOT EXISTS, so it is safe to run
-- on a database that already has some of them.

SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `cr_cars` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `images` text DEFAULT NULL,
  `license_plate` varchar(15) DEFAULT NULL,
  `make_id` bigint(20) unsigned DEFAULT NULL,
  `model_name` varchar(120) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'available',
  `year` int(10) unsigned DEFAULT NULL,
  `mileage` int(10) unsigned DEFAULT NULL,
  `horsepower` decimal(8,2) DEFAULT NULL,
  `vehicle_type_id` bigint(20) unsigned DEFAULT NULL,
  `transmission_id` bigint(20) unsigned DEFAULT NULL,
  `fuel_type_id` bigint(20) unsigned DEFAULT NULL,
  `drive_type` varchar(60) DEFAULT NULL,
  `cylinders` smallint(5) unsigned DEFAULT NULL,
  `number_of_seats` int(10) unsigned DEFAULT NULL,
  `number_of_doors` int(10) unsigned DEFAULT NULL,
  `rental_rate` double unsigned NOT NULL DEFAULT 0,
  `deposit_amount` decimal(15,2) DEFAULT NULL,
  `min_rental_days` smallint(5) unsigned NOT NULL DEFAULT 1,
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `rental_type` varchar(191) NOT NULL DEFAULT 'per_day',
  `rental_available_types` text DEFAULT NULL,
  `insurance_info` text DEFAULT NULL,
  `vin` varchar(191) DEFAULT NULL,
  `location` varchar(191) DEFAULT NULL,
  `country_id` bigint(20) unsigned DEFAULT NULL,
  `state_id` bigint(20) unsigned DEFAULT NULL,
  `city_id` bigint(20) unsigned DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `tax_id` bigint(20) unsigned DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `is_for_sale` tinyint(1) NOT NULL DEFAULT 0,
  `sale_price` decimal(15,2) DEFAULT NULL,
  `condition` varchar(30) DEFAULT NULL,
  `ownership_history` varchar(191) DEFAULT NULL,
  `warranty_information` text DEFAULT NULL,
  `sale_status` varchar(30) NOT NULL DEFAULT 'available',
  `external_booking_url` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `author_type` varchar(191) NOT NULL DEFAULT 'App\\Models\\User',
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  `moderation_status` varchar(60) NOT NULL DEFAULT 'pending',
  `reject_reason` varchar(400) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cr_cars_make_id_index` (`make_id`),
  KEY `cr_cars_status_index` (`status`),
  KEY `cr_cars_vehicle_type_id_index` (`vehicle_type_id`),
  KEY `cr_cars_vin_index` (`vin`),
  KEY `cr_cars_latitude_longitude_index` (`latitude`,`longitude`),
  KEY `cr_cars_model_name_index` (`model_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_booking_cars` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `car_id` bigint(20) unsigned NOT NULL,
  `pickup_city_id` bigint(20) unsigned DEFAULT NULL,
  `return_city_id` bigint(20) unsigned DEFAULT NULL,
  `car_image` varchar(191) DEFAULT NULL,
  `car_name` varchar(191) NOT NULL,
  `price` double NOT NULL,
  `currency_id` bigint(20) unsigned NOT NULL,
  `rental_start_date` datetime NOT NULL,
  `rental_end_date` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_booking_contracts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `pickup_mileage` int(10) unsigned DEFAULT NULL,
  `pickup_fuel_level` varchar(20) DEFAULT NULL,
  `exterior_condition` varchar(20) DEFAULT NULL,
  `interior_condition` varchar(20) DEFAULT NULL,
  `existing_damages` text DEFAULT NULL,
  `damage_marks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `pickup_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `signer_name` varchar(120) DEFAULT NULL,
  `signer_document` varchar(60) DEFAULT NULL,
  `customer_signature` longtext DEFAULT NULL,
  `company_representative` varchar(120) DEFAULT NULL,
  `company_signature` longtext DEFAULT NULL,
  `terms_snapshot` longtext DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `return_mileage` int(10) unsigned DEFAULT NULL,
  `return_fuel_level` varchar(20) DEFAULT NULL,
  `return_exterior_condition` varchar(20) DEFAULT NULL,
  `return_interior_condition` varchar(20) DEFAULT NULL,
  `return_damage_marks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `return_checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `return_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `return_notes` text DEFAULT NULL,
  `return_extra_charges` decimal(15,2) DEFAULT NULL,
  `return_charges_description` text DEFAULT NULL,
  `return_signer_name` varchar(120) DEFAULT NULL,
  `return_customer_signature` longtext DEFAULT NULL,
  `return_company_representative` varchar(120) DEFAULT NULL,
  `return_company_signature` longtext DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cr_booking_contracts_booking_id_unique` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_bookings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_number` varchar(191) DEFAULT NULL,
  `transaction_id` varchar(191) DEFAULT NULL,
  `customer_name` varchar(191) DEFAULT NULL,
  `customer_email` varchar(191) DEFAULT NULL,
  `customer_phone` varchar(191) DEFAULT NULL,
  `customer_age` int(10) unsigned DEFAULT NULL,
  `customer_id` varchar(191) DEFAULT NULL,
  `amount` double NOT NULL,
  `sub_total` double NOT NULL,
  `coupon_amount` double NOT NULL DEFAULT 0,
  `credit_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `coupon_code` varchar(191) DEFAULT NULL,
  `tax_amount` double NOT NULL DEFAULT 0,
  `deposit_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency_id` varchar(191) DEFAULT NULL,
  `payment_id` varchar(191) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `completion_miles` int(10) unsigned DEFAULT NULL,
  `completion_gas_level` varchar(30) DEFAULT NULL,
  `completion_damage_images` text DEFAULT NULL,
  `completion_notes` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cr_bookings_booking_number_unique` (`booking_number`),
  UNIQUE KEY `cr_bookings_transaction_id_unique` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_booking_service` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` bigint(20) unsigned NOT NULL,
  `service_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_car_colors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'pending',
  `order` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cr_car_colors_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_cars_colors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cr_car_id` bigint(20) unsigned NOT NULL,
  `cr_car_color_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_car_fuels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `icon` varchar(191) DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'pending',
  `order` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cr_car_fuels_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_car_maintenance_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` double NOT NULL DEFAULT 0,
  `currency_id` bigint(20) unsigned NOT NULL,
  `date` datetime DEFAULT NULL,
  `car_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cr_car_maintenance_histories_car_id_index` (`car_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_car_makes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `logo` varchar(191) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'published',
  `order` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_car_price_ranges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `car_id` bigint(20) unsigned NOT NULL,
  `min_days` int(10) unsigned NOT NULL,
  `max_days` int(10) unsigned DEFAULT NULL,
  `price_per_day` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cr_car_price_ranges_car_id_min_days_unique` (`car_id`,`min_days`),
  CONSTRAINT `cr_car_price_ranges_car_id_foreign` FOREIGN KEY (`car_id`) REFERENCES `cr_cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_car_transmissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `icon` varchar(191) DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'pending',
  `order` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cr_car_transmissions_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_car_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) DEFAULT NULL,
  `image` varchar(191) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'pending',
  `order` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cr_car_types_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_currencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(60) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `is_prefix_symbol` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `decimals` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `order` int(10) unsigned NOT NULL DEFAULT 0,
  `is_default` tinyint(4) NOT NULL DEFAULT 0,
  `exchange_rate` double NOT NULL DEFAULT 1,
  `number_format_style` varchar(20) NOT NULL DEFAULT 'western',
  `space_between_price_and_currency` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password` varchar(191) NOT NULL,
  `avatar` varchar(191) DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(25) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'published',
  `block_reason` varchar(400) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `email_verify_token` varchar(120) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_vendor` tinyint(1) NOT NULL DEFAULT 0,
  `referral_code` varchar(20) DEFAULT NULL,
  `referred_by` bigint(20) unsigned DEFAULT NULL,
  `customer_type` varchar(20) NOT NULL DEFAULT 'individual',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `verification_note` text DEFAULT NULL,
  `license_number` varchar(60) DEFAULT NULL,
  `license_region` varchar(120) DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `license_front_image` varchar(255) DEFAULT NULL,
  `license_back_image` varchar(255) DEFAULT NULL,
  `license_status` varchar(60) DEFAULT NULL,
  `license_submitted_at` datetime DEFAULT NULL,
  `license_reviewed_at` datetime DEFAULT NULL,
  `license_reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `license_rejected_reason` text DEFAULT NULL,
  `vendor_verified_at` datetime DEFAULT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bank_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `payout_payment_method` varchar(120) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cr_customers_email_unique` (`email`),
  UNIQUE KEY `cr_customers_referral_code_unique` (`referral_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_invoice_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` varchar(400) DEFAULT NULL,
  `qty` int(10) unsigned NOT NULL,
  `sub_total` decimal(15,2) unsigned NOT NULL,
  `tax_amount` decimal(15,2) unsigned NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) unsigned NOT NULL DEFAULT 0.00,
  `amount` decimal(15,2) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `customer_name` varchar(191) NOT NULL,
  `customer_email` varchar(191) DEFAULT NULL,
  `customer_phone` varchar(191) DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `reference_type` varchar(191) NOT NULL,
  `reference_id` bigint(20) unsigned NOT NULL,
  `code` varchar(191) NOT NULL,
  `sub_total` double NOT NULL,
  `tax_amount` double NOT NULL DEFAULT 0,
  `discount_amount` double NOT NULL DEFAULT 0,
  `amount` double NOT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `vendor_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cr_invoices_code_unique` (`code`),
  KEY `cr_invoices_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `cr_invoices_payment_id_index` (`payment_id`),
  KEY `cr_invoices_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `price` double unsigned NOT NULL DEFAULT 0,
  `currency_id` bigint(20) unsigned DEFAULT NULL,
  `price_type` varchar(191) NOT NULL DEFAULT 'once',
  `image` varchar(191) DEFAULT NULL,
  `logo` varchar(191) DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cr_services_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cr_taxes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `percentage` decimal(10,2) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount_type` varchar(20) NOT NULL DEFAULT 'once',
  `status` varchar(30) NOT NULL DEFAULT 'published',
  `priority` smallint(5) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `faqs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `currency` varchar(120) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `charge_id` varchar(191) DEFAULT NULL,
  `payment_channel` varchar(60) DEFAULT NULL,
  `description` varchar(400) DEFAULT NULL,
  `amount` decimal(15,2) unsigned NOT NULL,
  `payment_fee` decimal(15,2) DEFAULT 0.00,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(60) DEFAULT 'pending',
  `payment_type` varchar(191) DEFAULT 'confirm',
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `refunded_amount` decimal(15,2) unsigned DEFAULT NULL,
  `refund_note` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `customer_type` varchar(191) DEFAULT NULL,
  `metadata` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `author_id` bigint(20) unsigned DEFAULT NULL,
  `author_type` varchar(191) NOT NULL,
  `is_featured` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `image` varchar(191) DEFAULT NULL,
  `views` int(10) unsigned NOT NULL DEFAULT 0,
  `format_type` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `posts_status_index` (`status`),
  KEY `posts_author_id_index` (`author_id`),
  KEY `posts_author_type_index` (`author_type`),
  KEY `posts_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `post_categories` (
  `category_id` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned NOT NULL,
  KEY `post_categories_category_id_index` (`category_id`),
  KEY `post_categories_post_id_index` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `slugs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) NOT NULL,
  `reference_id` bigint(20) unsigned NOT NULL,
  `reference_type` varchar(191) NOT NULL,
  `prefix` varchar(120) DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `slugs_reference_id_index` (`reference_id`),
  KEY `slugs_key_index` (`key`),
  KEY `slugs_prefix_index` (`prefix`),
  KEY `slugs_reference_index` (`reference_id`,`reference_type`),
  KEY `idx_key_prefix` (`key`,`prefix`),
  KEY `idx_slugs_reference` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(191) DEFAULT NULL,
  `company` varchar(120) DEFAULT NULL,
  `status` varchar(60) NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(120) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `first_name` varchar(120) DEFAULT NULL,
  `last_name` varchar(120) DEFAULT NULL,
  `username` varchar(60) DEFAULT NULL,
  `avatar_id` bigint(20) unsigned DEFAULT NULL,
  `super_user` tinyint(1) NOT NULL DEFAULT 0,
  `manage_supers` tinyint(1) NOT NULL DEFAULT 0,
  `permissions` text DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `sessions_invalidated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
