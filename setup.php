<?php
// setup.php - Database Initialization and Seeding Script

$host = 'localhost';
$user = 'root';
$pass = '';
$dbName = 'atamis_db';

try {
    // 1. Establish connection to MySQL
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL server successfully.<br>";

    // 2. Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database `$dbName` created or verified successfully.<br>";

    // 3. Connect to the database
    $pdo->exec("USE `$dbName`");

    // Drop existing tables to allow clean re-run of setup.php
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $tables = ['traditional_area', 'users', 'clans', 'families', 'family_members', 'marriages', 'traditional_positions', 'appointments', 'succession_history', 'documents', 'gallery', 'events', 'audit_logs', 'settings', 'towns'];
    foreach ($tables as $tblName) {
        $pdo->exec("DROP TABLE IF EXISTS `$tblName` CASCADE");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 4. Create Tables
    
    // Traditional Area metadata
    $pdo->exec("CREATE TABLE IF NOT EXISTS `traditional_area` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `paramountcy` VARCHAR(255) NOT NULL,
        `location` VARCHAR(255) DEFAULT NULL,
        `history` TEXT DEFAULT NULL,
        `vision` TEXT DEFAULT NULL,
        `mission` TEXT DEFAULT NULL,
        `logo` VARCHAR(255) DEFAULT NULL
    ) ENGINE=InnoDB;");
    echo "Table `traditional_area` created.<br>";

    // Users (Dashboard staff)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `full_name` VARCHAR(100) NOT NULL,
        `role` ENUM('Administrator', 'Traditional Council Secretary', 'Data Entry Officer', 'Research Officer', 'Council Member', 'Viewer') NOT NULL DEFAULT 'Viewer',
        `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    echo "Table `users` created.<br>";

    // Clans
    $pdo->exec("CREATE TABLE IF NOT EXISTS `clans` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `totem` VARCHAR(100) DEFAULT NULL,
        `totem_image` VARCHAR(255) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `history` TEXT DEFAULT NULL,
        `ancestor_name` VARCHAR(255) DEFAULT NULL,
        `clan_head_id` INT DEFAULT NULL,
        `stool_father_id` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    echo "Table `clans` created.<br>";

    // Families (Sub-clans / stool houses)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `families` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `clan_id` INT NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `family_head_id` INT DEFAULT NULL,
        `stool_father_id` INT DEFAULT NULL,
        `town_id` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`clan_id`) REFERENCES `clans`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;");
    echo "Table `families` created.<br>";

    // Family Members (The main registry)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `family_members` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `first_name` VARCHAR(100) NOT NULL,
        `last_name` VARCHAR(100) NOT NULL,
        `other_names` VARCHAR(100) DEFAULT NULL,
        `gender` ENUM('Male', 'Female') NOT NULL,
        `date_of_birth` DATE NOT NULL,
        `place_of_birth` VARCHAR(100) DEFAULT NULL,
        `date_of_death` DATE DEFAULT NULL,
        `status` ENUM('Alive', 'Deceased') NOT NULL DEFAULT 'Alive',
        `clan_id` INT DEFAULT NULL,
        `family_id` INT DEFAULT NULL,
        `father_id` INT DEFAULT NULL,
        `mother_id` INT DEFAULT NULL,
        `spouse_id` INT DEFAULT NULL,
        `phone` VARCHAR(20) DEFAULT NULL,
        `email` VARCHAR(100) DEFAULT NULL,
        `address` TEXT DEFAULT NULL,
        `photo` VARCHAR(255) DEFAULT NULL,
        `bio` TEXT DEFAULT NULL,
        `father_name` VARCHAR(255) DEFAULT NULL,
        `mother_name` VARCHAR(255) DEFAULT NULL,
        `spouse_name` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`clan_id`) REFERENCES `clans`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`family_id`) REFERENCES `families`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`father_id`) REFERENCES `family_members`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`mother_id`) REFERENCES `family_members`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`spouse_id`) REFERENCES `family_members`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB;");
    echo "Table `family_members` created.<br>";

    // Add back-references to Clans and Families for their respective heads
    $pdo->exec("ALTER TABLE `clans` ADD CONSTRAINT `fk_clan_head` FOREIGN KEY (`clan_head_id`) REFERENCES `family_members`(`id`) ON DELETE SET NULL;");
    $pdo->exec("ALTER TABLE `clans` ADD CONSTRAINT `fk_clan_stool_father` FOREIGN KEY (`stool_father_id`) REFERENCES `family_members`(`id`) ON DELETE SET NULL;");
    $pdo->exec("ALTER TABLE `families` ADD CONSTRAINT `fk_family_head` FOREIGN KEY (`family_head_id`) REFERENCES `family_members`(`id`) ON DELETE SET NULL;");
    $pdo->exec("ALTER TABLE `families` ADD CONSTRAINT `fk_family_stool_father` FOREIGN KEY (`stool_father_id`) REFERENCES `family_members`(`id`) ON DELETE SET NULL;");
    echo "Back-references on clans and families updated.<br>";

    // Marriages
    $pdo->exec("CREATE TABLE IF NOT EXISTS `marriages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `husband_id` INT NOT NULL,
        `wife_id` INT NOT NULL,
        `marriage_date` DATE DEFAULT NULL,
        `marriage_type` VARCHAR(50) DEFAULT 'Traditional',
        `status` ENUM('Married', 'Divorced', 'Widowed') DEFAULT 'Married',
        `end_date` DATE DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`husband_id`) REFERENCES `family_members`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`wife_id`) REFERENCES `family_members`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;");
    echo "Table `marriages` created.<br>";

    // Traditional Positions
    $pdo->exec("CREATE TABLE IF NOT EXISTS `traditional_positions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(100) NOT NULL,
        `hierarchy_level` INT NOT NULL,
        `description` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    echo "Table `traditional_positions` created.<br>";

    // Appointments
    $pdo->exec("CREATE TABLE IF NOT EXISTS `appointments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `position_id` INT NOT NULL,
        `member_id` INT NOT NULL,
        `start_date` DATE NOT NULL,
        `end_date` DATE DEFAULT NULL,
        `status` ENUM('Active', 'Retired', 'Deceased', 'Destooled') DEFAULT 'Active',
        `installation_details` TEXT DEFAULT NULL,
        `serves_under_id` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`position_id`) REFERENCES `traditional_positions`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`member_id`) REFERENCES `family_members`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`serves_under_id`) REFERENCES `family_members`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB;");
    echo "Table `appointments` created.<br>";

    // Succession History
    $pdo->exec("CREATE TABLE IF NOT EXISTS `succession_history` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `position_id` INT NOT NULL,
        `predecessor_id` INT DEFAULT NULL,
        `successor_id` INT NOT NULL,
        `succession_date` DATE NOT NULL,
        `reason` VARCHAR(255) DEFAULT NULL,
        `notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`position_id`) REFERENCES `traditional_positions`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`predecessor_id`) REFERENCES `family_members`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`successor_id`) REFERENCES `family_members`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;");
    echo "Table `succession_history` created.<br>";

    // Documents
    $pdo->exec("CREATE TABLE IF NOT EXISTS `documents` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `file_path` VARCHAR(255) NOT NULL,
        `category` ENUM('Minutes', 'Legal', 'Historical', 'Customary', 'General') NOT NULL DEFAULT 'General',
        `uploaded_by` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB;");
    echo "Table `documents` created.<br>";

    // Gallery
    $pdo->exec("CREATE TABLE IF NOT EXISTS `gallery` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `image_path` VARCHAR(255) NOT NULL,
        `category` VARCHAR(100) DEFAULT 'General',
        `uploaded_by` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB;");
    echo "Table `gallery` created.<br>";

    // Events
    $pdo->exec("CREATE TABLE IF NOT EXISTS `events` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `event_date` DATE NOT NULL,
        `location` VARCHAR(255) DEFAULT NULL,
        `category` ENUM('Festival', 'Meeting', 'Funeral', 'Customary', 'General') NOT NULL DEFAULT 'General',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");
    echo "Table `events` created.<br>";

    // Audit logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS `audit_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT DEFAULT NULL,
        `action` VARCHAR(100) NOT NULL,
        `details` TEXT DEFAULT NULL,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB;");
    echo "Table `audit_logs` created.<br>";

    // Settings
    $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
        `setting_key` VARCHAR(50) PRIMARY KEY,
        `setting_value` TEXT DEFAULT NULL,
        `description` TEXT DEFAULT NULL
    ) ENGINE=InnoDB;");
    echo "Table `settings` created.<br>";

    // Towns
    $pdo->exec("CREATE TABLE IF NOT EXISTS `towns` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `chief_id` INT DEFAULT NULL,
        `stool_name` VARCHAR(100) DEFAULT NULL,
        `population` INT DEFAULT NULL,
        `livelihood` VARCHAR(255) DEFAULT NULL,
        `landmark` VARCHAR(255) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `coordinates` VARCHAR(50) DEFAULT NULL,
        `image` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`chief_id`) REFERENCES `family_members`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB;");
    echo "Table `towns` created.<br>";

    // Add constraint on families for towns
    $pdo->exec("ALTER TABLE `families` ADD CONSTRAINT `fk_family_town` FOREIGN KEY (`town_id`) REFERENCES `towns`(`id`) ON DELETE SET NULL;");

    // 5. Seed Initial Data
    echo "Seeding initial data...<br>";

    // Traditional Area Info
    $stmt = $pdo->prepare("INSERT INTO `traditional_area` (name, paramountcy, location, history, vision, mission) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'Atsiame Traditional Area',
        'Atsiame Paramouncy',
        'Akatsi South Municipality, Anlo State, Volta Region, Ghana',
        'The Atsiame Traditional Area is one of the historic divisions of the Anlo State in the Volta Region of Ghana. The ancestors of Atsiame migrated from Hogbe (Notsie) in the 17th century under the leadership of royal elders. They settled in their current location in the Akatsi South Municipality, establishing strong clans, stool houses, and a rich cultural heritage centered on governance, farming, and unity.',
        'To preserve the rich cultural history and lineage of the Atsiame people through digital records, fostering development and traditional unity.',
        'To document and digitize the genealogy, stools, clans, and activities of the traditional area for future generations and administrative ease.'
    ]);
    
    // Default Users (admin/AdminPassword123, secretary/SecretaryPassword123)
    $adminPassword = password_hash('AdminPassword123', PASSWORD_DEFAULT);
    $secPassword = password_hash('SecretaryPassword123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO `users` (username, password, email, full_name, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['admin', $adminPassword, 'admin@atsiame.org', 'Administrator', 'Administrator', 'Active']);
    $stmt->execute(['secretary', $secPassword, 'secretary@atsiame.org', 'Kofi Atsiame Secretary', 'Traditional Council Secretary', 'Active']);
    
    // Clans
    $stmt = $pdo->prepare("INSERT INTO `clans` (name, totem, description, history, ancestor_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        'Atsiame Clan',
        'Leopard (Lãkle)',
        'The Royal Clan associated with stool leadership and paramount administration. Boldness and nobility are their core traits.',
        'The Atsiame Clan is one of the royal lineages of the Anlo state, trace their heritage back to Notsie. They carry the primary administrative stools of the area.',
        'Torgbui Atsiame'
    ]);
    $clanAdzoviaId = $pdo->lastInsertId();

    $stmt->execute([
        'Like Clan',
        'Falcon (Hevi)',
        'The warrior and strategist clan. Known for quick-witted solutions and traditional mechanics.',
        'The Like Clan settled in the early days of migration, providing tactical support and protecting the boundaries of the traditional area.',
        'Torgbui Like'
    ]);
    $clanLikeId = $pdo->lastInsertId();

    $stmt->execute([
        'Tovi Clan',
        'Buffalo (To)',
        'The agriculturalists and landlords. Known for strength, earth-relations, and massive resilience.',
        'The Tovi Clan settled down to tend the custom soils, securing vital agricultural lands and preserving traditional resource structures.',
        'Torgbui Tovi'
    ]);
    $clanToviId = $pdo->lastInsertId();
    
    // Families
    $stmt = $pdo->prepare("INSERT INTO `families` (clan_id, name, description) VALUES (?, ?, ?)");
    $stmt->execute([$clanAdzoviaId, 'Katsriku Family', 'Royal Family of the Paramount Stool']);
    $famKatsrikuId = $pdo->lastInsertId();
    $stmt->execute([$clanAdzoviaId, 'Awleshi Family', 'Royal Family of the Paramount Queen Mother']);
    $famAwleshiId = $pdo->lastInsertId();
    $stmt->execute([$clanLikeId, 'Sri Family', 'Stool family of the War Marshal']);
    $famSriId = $pdo->lastInsertId();
    $stmt->execute([$clanToviId, 'Basa Family', 'Stool family of the Left-Wing']);
    $famBasaId = $pdo->lastInsertId();

    // Family Members (Seeding three generations to show Genealogy clearly)
    // Generation 1 (Grandparents)
    $stmt = $pdo->prepare("INSERT INTO `family_members` (first_name, last_name, other_names, gender, date_of_birth, place_of_birth, date_of_death, status, clan_id, family_id, father_id, mother_id, spouse_id, phone, email, address, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Togbui Katsriku I (Deceased Grandfather)
    $stmt->execute(['Torgbui', 'Katsriku I', 'Kwami', 'Male', '1910-04-12', 'Atsiame', '1985-06-20', 'Deceased', $clanAdzoviaId, $famKatsrikuId, null, null, null, null, null, 'Royal Palace, Atsiame', 'Paramount Chief of Atsiame between 1945 and 1985. Promoted education and farming.']);
    $g1FatherId = $pdo->lastInsertId();

    // Mama Awleshi I (Deceased Grandmother)
    $stmt->execute(['Mama', 'Awleshi I', 'Abla', 'Female', '1915-08-22', 'Atsiame', '1990-11-05', 'Deceased', $clanAdzoviaId, $famAwleshiId, null, null, null, null, null, 'Royal Palace, Atsiame', 'Queen Mother who stabilized the area during transitions.']);
    $g1MotherId = $pdo->lastInsertId();
    
    // Link G1 spouses
    $pdo->exec("UPDATE `family_members` SET spouse_id = $g1MotherId WHERE id = $g1FatherId");
    $pdo->exec("UPDATE `family_members` SET spouse_id = $g1FatherId WHERE id = $g1MotherId");

    // Generation 2 (Parents)
    // Torgbui Katsriku II (Current Paramount Chief, Son of G1)
    $stmt->execute(['Torgbui', 'Katsriku II', 'Kofi', 'Male', '1948-02-15', 'Atsiame', null, 'Alive', $clanAdzoviaId, $famKatsrikuId, $g1FatherId, $g1MotherId, null, '0244123456', 'torgbui@atsiame.org', 'Royal Palace, Atsiame', 'Installed in 1988, retired civil servant. Oversees the traditional area.']);
    $g2FatherId = $pdo->lastInsertId();
    
    // Mama Katsriku II (Wife of Katsriku II - from Tovi Clan)
    $stmt->execute(['Mama', 'Katsriku II', 'Sena', 'Female', '1955-09-10', 'Akatsi', null, 'Alive', $clanToviId, $famBasaId, null, null, $g2FatherId, '0244223344', 'mama.sena@atsiame.org', 'Royal Palace, Atsiame', 'Queen Mother and entrepreneur.']);
    $g2MotherId = $pdo->lastInsertId();
    
    // Update Katsriku II's spouse_id
    $pdo->exec("UPDATE `family_members` SET spouse_id = $g2MotherId WHERE id = $g2FatherId");

    // Torgbui Sri IV (War Marshal - from Like Clan, another G2 member)
    $stmt->execute(['Torgbui', 'Sri IV', 'Nelson', 'Male', '1952-11-20', 'Atsiame', null, 'Alive', $clanLikeId, $famSriId, null, null, null, '0244556677', 'sri.war@atsiame.org', 'Sri Palace, Atsiame', 'The War Marshal / Avadada of Atsiame. Ex-military captain.']);
    $g2WarMarshalId = $pdo->lastInsertId();

    // Generation 3 (Children of G2Father and G2Mother)
    // Prince Yao Katsriku (Son)
    $stmt->execute(['Yao', 'Katsriku', 'Prince', 'Male', '1980-05-14', 'Atsiame', null, 'Alive', $clanAdzoviaId, $famKatsrikuId, $g2FatherId, $g2MotherId, null, '0207889900', 'prince.yao@atsiame.org', 'Accra, Ghana', 'Software engineer based in Accra, supporting digitizing traditional records.']);
    $g3SonId = $pdo->lastInsertId();

    // Princess Abla Katsriku (Daughter)
    $stmt->execute(['Abla', 'Katsriku', 'Princess', 'Female', '1985-07-30', 'Atsiame', null, 'Alive', $clanAdzoviaId, $famKatsrikuId, $g2FatherId, $g2MotherId, null, '0207998877', 'abla.kat@atsiame.org', 'Atsiame', 'Teacher at Atsiame Basic School.']);
    $g3DaughterId = $pdo->lastInsertId();
    
    // Update heads of Clan and Family
    $pdo->exec("UPDATE `clans` SET clan_head_id = $g2FatherId WHERE id = $clanAdzoviaId");
    $pdo->exec("UPDATE `clans` SET clan_head_id = $g2WarMarshalId WHERE id = $clanLikeId");
    $pdo->exec("UPDATE `families` SET family_head_id = $g2FatherId WHERE id = $famKatsrikuId");

    // Marriages Table seeding
    $stmt = $pdo->prepare("INSERT INTO `marriages` (husband_id, wife_id, marriage_date, marriage_type, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$g1FatherId, $g1MotherId, '1938-12-05', 'Traditional', 'Widowed']);
    $stmt->execute([$g2FatherId, $g2MotherId, '1975-06-18', 'Traditional', 'Married']);

    // Traditional Positions
    $stmt = $pdo->prepare("INSERT INTO `traditional_positions` (title, hierarchy_level, description) VALUES (?, ?, ?)");
    $stmt->execute(['Paramount Chief', 1, 'The supreme ruler and custodian of the paramouncy.']);
    $posParamountChiefId = $pdo->lastInsertId();
    
    $stmt->execute(['Paramount Queen Mother', 1, 'Co-ruler of the Traditional Area, counselor to the Chief and leader of women in Atsiame.']);
    $posQueenId = $pdo->lastInsertId();

    $stmt->execute(['War Marshal (Avadada)', 2, 'The commander-in-chief of the traditional army and defense wing of the paramouncy.']);
    $posWarId = $pdo->lastInsertId();
    
    $stmt->execute(['Left Wing Chief (Miafiaga)', 2, 'In charge of the Left-Wing division of governance and community defense.']);
    $posLeftId = $pdo->lastInsertId();

    $stmt->execute(['Right Wing Chief (Dusifiaga)', 2, 'In charge of the Right-Wing division of governance and community defense.']);
    $posRightId = $pdo->lastInsertId();

    $stmt->execute(['Linguist (Tsiami)', 3, 'The official spokesperson and orator for the stool.']);
    $posLinguistId = $pdo->lastInsertId();
    
    $stmt->execute(['Agbotadua', 3, 'Shield bearer, stool companion and main assistant to the chief.']);
    $posAgbotaduaId = $pdo->lastInsertId();

    $stmt->execute(['Stool Father (Zikpuitor)', 3, 'Advisor, kingmaker, and primary custodian of the stool.']);
    $posStoolFatherId = $pdo->lastInsertId();

    $stmt->execute(['Family Head', 4, 'Heads a stool family or sub-clan, coordinates family councils and rites.']);
    $posFamHeadId = $pdo->lastInsertId();

    $stmt->execute(['Principal Family Elder', 5, 'Notable elder of the royal family council who advises on customary matters.']);
    $posElderId = $pdo->lastInsertId();

    $stmt->execute(['Royal Youth Leader', 6, 'Represents the royal youth within the traditional area council.']);
    $posYouthLeaderId = $pdo->lastInsertId();

    // Appointments
    $stmt = $pdo->prepare("INSERT INTO `appointments` (position_id, member_id, start_date, status, installation_details) VALUES (?, ?, ?, ?, ?)");
    
    // G1 Father past appointment (Paramount Chief)
    $stmt->execute([$posParamountChiefId, $g1FatherId, '1945-05-10', 'Deceased', 'Installed after the passing of Torgbui Katsriku. Reigned for 40 years.']);
    
    // G1 Mother past appointment (Queen Mother)
    $stmt->execute([$posQueenId, $g1MotherId, '1948-09-12', 'Deceased', 'Stool father and family elders led installation.']);
    
    // G2 Father current appointment (Paramount Chief)
    $stmt->execute([$posParamountChiefId, $g2FatherId, '1988-11-20', 'Active', 'Installed following customary rituals and validation by the council.']);

    // G2 Mother current appointment (Queen Mother)
    $stmt->execute([$posQueenId, $g2MotherId, '1992-04-15', 'Active', 'Installed alongside Torgbui Katsriku II.']);
    
    // G2 War Marshal appointment
    $stmt->execute([$posWarId, $g2WarMarshalId, '2005-02-12', 'Active', 'Installed to succeed his uncle.']);

    // Succession History (Linking Togbui Katsriku I to Togbui Katsriku II)
    $stmt = $pdo->prepare("INSERT INTO `succession_history` (position_id, predecessor_id, successor_id, succession_date, reason, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$posParamountChiefId, $g1FatherId, $g2FatherId, '1988-11-20', 'Decease of predecessor', 'Succession went through three years of consultation and regent leadership after Katsriku I died in 1985.']);

    // Events
    $stmt = $pdo->prepare("INSERT INTO `events` (title, description, event_date, location, category) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        'Atsiame Traditional Council Meeting',
        'Monthly meeting of all divisional chiefs and queen mothers to discuss local community development, conflict resolution, and treasury records.',
        '2026-08-05',
        'Paramount Palace Conference Hall',
        'Meeting'
    ]);
    $stmt->execute([
        'Annual Atsiame Yam Festival (Te Za)',
        'Celebration of harvest, thanksgiving to ancestors, grand durbar of chiefs, and display of rich kente clothing and traditional drumming.',
        '2026-09-15',
        'Atsiame Community Durbar Grounds',
        'Festival'
    ]);

    // Documents
    $stmt = $pdo->prepare("INSERT INTO `documents` (title, description, file_path, category, uploaded_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        'Atsiame Customary Lands Declaration Act',
        'Official statement defining land boundaries, stool land administration, and family tenant guidelines.',
        'uploads/documents/customary_lands_act.pdf',
        'Legal',
        1
    ]);
    $stmt->execute([
        'Traditional Council Minutes - January 2026',
        'Minutes of the first general meeting of the traditional area representatives.',
        'uploads/documents/minutes_jan_2026.pdf',
        'Minutes',
        1
    ]);

    // Gallery
    $stmt = $pdo->prepare("INSERT INTO `gallery` (title, description, image_path, category, uploaded_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        'Grand Durbar of Chiefs',
        'Torgbui Katsriku II sitting in state during the Yam Festival.',
        'uploads/photos/durbar_chiefs.jpg',
        'Festival',
        1
    ]);
    $stmt->execute([
        'Royal Stool House',
        'A view of the stool house after traditional renovation.',
        'uploads/photos/stool_house.jpg',
        'Historical',
        1
    ]);
    $stmt->execute([
        'Atsiame Chiefs Procession',
        'Traditional chiefs and elders of the Atsiame State in full ceremonial regalia during a custom durbar.',
        'uploads/photos/front_hero.jpg',
        'Customary',
        1
    ]);

    // Settings
    $stmt = $pdo->prepare("INSERT INTO `settings` (setting_key, setting_value, description) VALUES (?, ?, ?)");
    $stmt->execute(['system_name', 'ATAMIS', 'System Name']);
    $stmt->execute(['traditional_area_name', 'Atsiame Traditional Area', 'Traditional Area Name']);
    $stmt->execute(['system_email', 'info@atsiame.org', 'System Email Address']);
    $stmt->execute(['currency', 'GHS', 'System Currency']);

    // Towns Seeding
    $stmt = $pdo->prepare("INSERT INTO `towns` (name, chief_id, stool_name, population, livelihood, landmark, description, coordinates) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Atsiame Capital', 3, 'Katsriku Paramount Stool', 4200, 'Administration, Farming, Trading', 'Paramount Palace, Sacred Aklor Grove', 'The administrative capital and heart of the Atsiame traditional state. Holds the ancient paramount seat of the Katsriku dynasty and coordinates customary activities.', '400,200']);
    $stmt->execute(['Torve', 6, 'Torve Divisional Stool', 2800, 'Clay Pottery, Cassava Farming', 'Torve Pottery Center, Community Market', 'Renowned for its traditional pottery, Torve supplies high-quality clay crafts and holds a vital agricultural position within the Dukor.', '200,150']);
    $stmt->execute(['Wute', 7, 'Wute Divisional Stool', 3100, 'Kente Weaving, Maize Cultivation', 'Wute Basic School, Wute Sacred Forest', 'A vibrant cultural town known for its high-quality Ewe Kente weavers and rich forest resources preservation.', '550,280']);
    $stmt->execute(['Xevi', null, 'Xevi Divisional Stool', 1900, 'Trading, Poultry Farming', 'Sacred Shrine of Xevi', 'Positioned along a historical trading path, Xevi is known for traditional spiritual rites and trade facilitation.', '280,350']);
    $stmt->execute(['Agorgbe', null, 'Agorgbe Clan Stool', 1500, 'Vegetable Farming, Livestock', 'Agorgbe Community Borehole', 'An agricultural hub producing vast yields of tomatoes, peppers, and livestock, fostering food security for the paramouncy.', '480,120']);

    echo "Data seeded successfully.<br><br>";
    echo "<strong>Database successfully setup!</strong><br>";
    echo "Default Admin: <code>admin</code> / <code>AdminPassword123</code><br>";
    echo "Default Secretary: <code>secretary</code> / <code>SecretaryPassword123</code><br>";

    // 6. Create directories
    $dirs = [
        'assets/css',
        'assets/js',
        'assets/images',
        'uploads/photos',
        'uploads/documents'
    ];

    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            echo "Created directory: `$dir`<br>";
        }
    }

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "<br>";
}
?>
