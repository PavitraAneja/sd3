-- Add required tables to existing sd3 database
-- Run this in your existing sd3 database

USE sd3;

-- 1. Token storage table for Trestle API (if it doesn't exist)
CREATE TABLE IF NOT EXISTS `token_store` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token_type` varchar(50) NOT NULL,
  `access_token` text NOT NULL,
  `expires_at` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_type` (`token_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Check if rets_property table exists, if not create it
CREATE TABLE IF NOT EXISTS `rets_property` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `L_ListingID` varchar(255) NOT NULL,
  `L_DisplayId` varchar(255) DEFAULT NULL,
  `L_Address` text,
  `L_Zip` varchar(20) DEFAULT NULL,
  `LM_char10_70` varchar(255) DEFAULT NULL,
  `L_AddressStreet` varchar(255) DEFAULT NULL,
  `L_City` varchar(100) DEFAULT NULL,
  `L_State` varchar(10) DEFAULT NULL,
  `L_Class` varchar(50) DEFAULT NULL,
  `L_Type_` varchar(100) DEFAULT NULL,
  `L_Keyword2` int(11) DEFAULT NULL,
  `LM_Dec_3` decimal(10,2) DEFAULT NULL,
  `L_Keyword1` decimal(10,2) DEFAULT NULL,
  `L_Keyword5` int(11) DEFAULT NULL,
  `L_Keyword7` text,
  `L_SystemPrice` decimal(15,2) DEFAULT NULL,
  `LM_Int2_3` int(11) DEFAULT NULL,
  `L_ListingDate` datetime DEFAULT NULL,
  `ListingContractDate` datetime DEFAULT NULL,
  `LMD_MP_Latitude` decimal(10,8) DEFAULT NULL,
  `LMD_MP_Longitude` decimal(11,8) DEFAULT NULL,
  `LA1_UserFirstName` varchar(100) DEFAULT NULL,
  `LA1_UserLastName` varchar(100) DEFAULT NULL,
  `L_Status` varchar(50) DEFAULT NULL,
  `LO1_OrganizationName` varchar(255) DEFAULT NULL,
  `L_Remarks` text,
  `L_Photos` text,
  `PhotoTime` datetime DEFAULT NULL,
  `PhotoCount` int(11) DEFAULT NULL,
  `L_alldata` longtext,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `L_ListingID` (`L_ListingID`),
  KEY `idx_status` (`L_Status`),
  KEY `idx_city` (`L_City`),
  KEY `idx_state` (`L_State`),
  KEY `idx_price` (`L_SystemPrice`),
  KEY `idx_bedrooms` (`L_Keyword2`),
  KEY `idx_bathrooms` (`LM_Dec_3`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Open house table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS `rets_openhouse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `L_ListingID` varchar(255) NOT NULL,
  `L_DisplayId` varchar(255) DEFAULT NULL,
  `OpenHouseDate` datetime DEFAULT NULL,
  `OH_StartTime` datetime DEFAULT NULL,
  `OH_EndTime` datetime DEFAULT NULL,
  `OH_StartDate` datetime DEFAULT NULL,
  `OH_EndDate` datetime DEFAULT NULL,
  `updated_date` datetime DEFAULT NULL,
  `API_OH_StartDate` datetime DEFAULT NULL,
  `API_OH_EndDate` datetime DEFAULT NULL,
  `all_data` longtext,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `L_DisplayId` (`L_DisplayId`),
  KEY `idx_openhouse_date` (`OpenHouseDate`),
  KEY `idx_listing_id` (`L_ListingID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Show all tables in the database
SHOW TABLES; 