
ALTER TABLE `HostingSchemes` ADD `Discount` DOUBLE NOT NULL DEFAULT '-1' AFTER `CostMonth`;

-- SEPARATOR

ALTER TABLE `VPSSchemes` ADD `Discount` DOUBLE NOT NULL DEFAULT '-1' AFTER `CostInstall`;

-- SEPARATOR

ALTER TABLE `DSSchemes` ADD `Discount` DOUBLE NOT NULL DEFAULT '-1' AFTER `CostInstall`;

-- SEPARATOR

ALTER TABLE `ExtraIPSchemes` ADD `Discount` DOUBLE NOT NULL DEFAULT '-1' AFTER `CostInstall`;

-- SEPARATOR

ALTER TABLE `ISPswSchemes` ADD `Discount` DOUBLE NOT NULL DEFAULT '-1' AFTER `CostInstall`;

-- SEPARATOR

ALTER TABLE `DNSmanagerSchemes` ADD `Discount` DOUBLE NOT NULL DEFAULT '-1' AFTER `CostMonth`;







