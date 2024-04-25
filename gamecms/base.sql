-- Добавляем новые колонки в таблицу config__bank, если их нет
ALTER TABLE `config__bank` ADD `foxypay` INT(11) NULL DEFAULT '2';
ALTER TABLE `config__bank` ADD `foxypay_token` VARCHAR(255) NULL DEFAULT '0';
ALTER TABLE `config__bank` ADD `foxypay_currency` VARCHAR(255) NULL DEFAULT 'UAH';
ALTER TABLE `config__bank` ADD `site_currency` VARCHAR(255) NULL DEFAULT 'UAH';
