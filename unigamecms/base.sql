-- Добавляем новые колонки в таблицу config__bank, если их нет
ALTER TABLE config__bank
    ADD COLUMN foxypay INT(11) DEFAULT 1,
    ADD COLUMN foxypay_token VARCHAR(255),
    ADD COLUMN foxypay_currency VARCHAR(10);
