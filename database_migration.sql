-- Run once in the users_db database.
ALTER TABLE users
    ADD COLUMN account_status VARCHAR(20) NOT NULL DEFAULT 'Active';

ALTER TABLE orders
    ADD COLUMN cancelled_from_status VARCHAR(20) NULL DEFAULT NULL;

ALTER TABLE orders
    ADD COLUMN cancellation_reason VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE orders
    ADD COLUMN cancelled_by VARCHAR(20) NULL DEFAULT NULL;