-- NGO and campaign map coordinates (run once on donate_now)
USE donate_now;

ALTER TABLE ngo_profiles
  ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER address,
  ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude;

ALTER TABLE campaigns
  ADD COLUMN location_label VARCHAR(255) NULL AFTER description,
  ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER location_label,
  ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude;
