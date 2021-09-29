<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210924003554 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE clever_box (id INT AUTO_INCREMENT NOT NULL, site_id INT DEFAULT NULL, zone_id INT DEFAULT NULL, name VARCHAR(20) NOT NULL, box_id VARCHAR(20) NOT NULL, slug VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', edited_at DATETIME DEFAULT NULL, INDEX IDX_CB134DE1F6BD1646 (site_id), INDEX IDX_CB134DE19F2C3FAB (zone_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE enterprise (id INT AUTO_INCREMENT NOT NULL, social_reason VARCHAR(20) NOT NULL, niu VARCHAR(20) DEFAULT NULL, rccm VARCHAR(20) DEFAULT NULL, address VARCHAR(50) DEFAULT NULL, email VARCHAR(20) NOT NULL, phone_number VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', logo VARCHAR(50) DEFAULT NULL, country VARCHAR(20) NOT NULL, edited_at DATETIME DEFAULT NULL, is_active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE genset_data (id INT AUTO_INCREMENT NOT NULL, smart_mod_id INT DEFAULT NULL, p DOUBLE PRECISION DEFAULT NULL, pamoy DOUBLE PRECISION DEFAULT NULL, pbmoy DOUBLE PRECISION DEFAULT NULL, pcmoy DOUBLE PRECISION DEFAULT NULL, samoy DOUBLE PRECISION DEFAULT NULL, sbmoy DOUBLE PRECISION DEFAULT NULL, scmoy DOUBLE PRECISION DEFAULT NULL, smoy DOUBLE PRECISION DEFAULT NULL, cosfia DOUBLE PRECISION DEFAULT NULL, cosfib DOUBLE PRECISION DEFAULT NULL, cosfic DOUBLE PRECISION DEFAULT NULL, cosfi DOUBLE PRECISION DEFAULT NULL, total_running_hours INT DEFAULT NULL, total_energy INT DEFAULT NULL, nb_mains_interruption INT DEFAULT NULL, date_time DATETIME NOT NULL, fuel_level INT DEFAULT NULL, INDEX IDX_EC1169D62CFA4C13 (smart_mod_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE genset_real_time_data (id INT AUTO_INCREMENT NOT NULL, smart_mod_id INT DEFAULT NULL, l12_g DOUBLE PRECISION DEFAULT NULL, l13_g DOUBLE PRECISION DEFAULT NULL, l23_g DOUBLE PRECISION DEFAULT NULL, l1_n DOUBLE PRECISION DEFAULT NULL, l2_n DOUBLE PRECISION DEFAULT NULL, l3_n DOUBLE PRECISION NOT NULL, l12_m DOUBLE PRECISION DEFAULT NULL, l13_m DOUBLE PRECISION DEFAULT NULL, l23_m DOUBLE PRECISION DEFAULT NULL, freq DOUBLE PRECISION DEFAULT NULL, fuel_level INT DEFAULT NULL, water_level INT DEFAULT NULL, oil_level INT DEFAULT NULL, water_temperature DOUBLE PRECISION DEFAULT NULL, cooler_temperature DOUBLE PRECISION DEFAULT NULL, batt_voltage DOUBLE PRECISION DEFAULT NULL, hours_to_maintenance INT DEFAULT NULL, genset_running INT DEFAULT NULL, cg INT DEFAULT NULL, mains_presence INT DEFAULT NULL, cr INT DEFAULT NULL, maintenance_request INT DEFAULT NULL, low_fuel INT DEFAULT NULL, date_time DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_9AEAA4D32CFA4C13 (smart_mod_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE load_energy_data (id INT AUTO_INCREMENT NOT NULL, smart_mod_id INT NOT NULL, date_time DATETIME NOT NULL, vamoy DOUBLE PRECISION DEFAULT NULL, vbmoy DOUBLE PRECISION DEFAULT NULL, vcmoy DOUBLE PRECISION DEFAULT NULL, pamoy DOUBLE PRECISION DEFAULT NULL, pbmoy DOUBLE PRECISION DEFAULT NULL, pcmoy DOUBLE PRECISION DEFAULT NULL, pmoy DOUBLE PRECISION DEFAULT NULL, qamoy DOUBLE PRECISION DEFAULT NULL, qbmoy DOUBLE PRECISION DEFAULT NULL, qcmoy DOUBLE PRECISION DEFAULT NULL, qmoy DOUBLE PRECISION DEFAULT NULL, samoy DOUBLE PRECISION DEFAULT NULL, sbmoy DOUBLE PRECISION DEFAULT NULL, scmoy DOUBLE PRECISION DEFAULT NULL, smoy DOUBLE PRECISION DEFAULT NULL, cosfia DOUBLE PRECISION DEFAULT NULL, cosfib DOUBLE PRECISION DEFAULT NULL, cosfic DOUBLE PRECISION DEFAULT NULL, cosfi DOUBLE PRECISION DEFAULT NULL, eaa DOUBLE PRECISION DEFAULT NULL, eab DOUBLE PRECISION DEFAULT NULL, eac DOUBLE PRECISION DEFAULT NULL, ea DOUBLE PRECISION DEFAULT NULL, era DOUBLE PRECISION DEFAULT NULL, erb DOUBLE PRECISION DEFAULT NULL, erc DOUBLE PRECISION DEFAULT NULL, er DOUBLE PRECISION DEFAULT NULL, INDEX IDX_B7FD8E362CFA4C13 (smart_mod_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE site (id INT AUTO_INCREMENT NOT NULL, enterprise_id INT NOT NULL, name VARCHAR(50) NOT NULL, slug VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', edited_at DATETIME DEFAULT NULL, power_subscribed DOUBLE PRECISION NOT NULL, currency VARCHAR(10) NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, subscription VARCHAR(10) NOT NULL, subscription_type VARCHAR(10) NOT NULL, INDEX IDX_694309E4A97D1AC3 (enterprise_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE site_user (site_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_B6096BB0F6BD1646 (site_id), INDEX IDX_B6096BB0A76ED395 (user_id), PRIMARY KEY(site_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE smart_device (id INT AUTO_INCREMENT NOT NULL, clever_box_id INT DEFAULT NULL, name VARCHAR(20) NOT NULL, specification VARCHAR(20) DEFAULT NULL, module_id VARCHAR(20) NOT NULL, slug VARCHAR(40) NOT NULL, programming JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', edited_at DATETIME DEFAULT NULL, INDEX IDX_165BDB50F080FFA (clever_box_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE smart_mod (id INT AUTO_INCREMENT NOT NULL, site_id INT DEFAULT NULL, name VARCHAR(20) NOT NULL, module_id VARCHAR(20) NOT NULL, mod_type VARCHAR(20) NOT NULL, fuel_price DOUBLE PRECISION DEFAULT NULL, level_zone INT DEFAULT NULL, nb_phases INT DEFAULT NULL, sub_type VARCHAR(20) DEFAULT NULL, power DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', edited_at DATETIME DEFAULT NULL, INDEX IDX_786B66EEF6BD1646 (site_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE smart_mod_zone (smart_mod_id INT NOT NULL, zone_id INT NOT NULL, INDEX IDX_D703B6422CFA4C13 (smart_mod_id), INDEX IDX_D703B6429F2C3FAB (zone_id), PRIMARY KEY(smart_mod_id, zone_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, enterprise_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, avatar VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', edited_at DATETIME DEFAULT NULL, first_name VARCHAR(20) NOT NULL, last_name VARCHAR(50) NOT NULL, phone_number VARCHAR(20) NOT NULL, country_code VARCHAR(10) NOT NULL, verification_code VARCHAR(10) DEFAULT NULL, is_verified TINYINT(1) NOT NULL, is_active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D649A97D1AC3 (enterprise_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE zone (id INT AUTO_INCREMENT NOT NULL, site_id INT DEFAULT NULL, name VARCHAR(10) NOT NULL, psous DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', edited_at DATETIME DEFAULT NULL, INDEX IDX_A0EBC007F6BD1646 (site_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE zone_user (zone_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_1F6B17EC9F2C3FAB (zone_id), INDEX IDX_1F6B17ECA76ED395 (user_id), PRIMARY KEY(zone_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE clever_box ADD CONSTRAINT FK_CB134DE1F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE clever_box ADD CONSTRAINT FK_CB134DE19F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id)');
        $this->addSql('ALTER TABLE genset_data ADD CONSTRAINT FK_EC1169D62CFA4C13 FOREIGN KEY (smart_mod_id) REFERENCES smart_mod (id)');
        $this->addSql('ALTER TABLE genset_real_time_data ADD CONSTRAINT FK_9AEAA4D32CFA4C13 FOREIGN KEY (smart_mod_id) REFERENCES smart_mod (id)');
        $this->addSql('ALTER TABLE load_energy_data ADD CONSTRAINT FK_B7FD8E362CFA4C13 FOREIGN KEY (smart_mod_id) REFERENCES smart_mod (id)');
        $this->addSql('ALTER TABLE site ADD CONSTRAINT FK_694309E4A97D1AC3 FOREIGN KEY (enterprise_id) REFERENCES enterprise (id)');
        $this->addSql('ALTER TABLE site_user ADD CONSTRAINT FK_B6096BB0F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE site_user ADD CONSTRAINT FK_B6096BB0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE smart_device ADD CONSTRAINT FK_165BDB50F080FFA FOREIGN KEY (clever_box_id) REFERENCES clever_box (id)');
        $this->addSql('ALTER TABLE smart_mod ADD CONSTRAINT FK_786B66EEF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE smart_mod_zone ADD CONSTRAINT FK_D703B6422CFA4C13 FOREIGN KEY (smart_mod_id) REFERENCES smart_mod (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE smart_mod_zone ADD CONSTRAINT FK_D703B6429F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649A97D1AC3 FOREIGN KEY (enterprise_id) REFERENCES enterprise (id)');
        $this->addSql('ALTER TABLE zone ADD CONSTRAINT FK_A0EBC007F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE zone_user ADD CONSTRAINT FK_1F6B17EC9F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE zone_user ADD CONSTRAINT FK_1F6B17ECA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE smart_device DROP FOREIGN KEY FK_165BDB50F080FFA');
        $this->addSql('ALTER TABLE site DROP FOREIGN KEY FK_694309E4A97D1AC3');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649A97D1AC3');
        $this->addSql('ALTER TABLE clever_box DROP FOREIGN KEY FK_CB134DE1F6BD1646');
        $this->addSql('ALTER TABLE site_user DROP FOREIGN KEY FK_B6096BB0F6BD1646');
        $this->addSql('ALTER TABLE smart_mod DROP FOREIGN KEY FK_786B66EEF6BD1646');
        $this->addSql('ALTER TABLE zone DROP FOREIGN KEY FK_A0EBC007F6BD1646');
        $this->addSql('ALTER TABLE genset_data DROP FOREIGN KEY FK_EC1169D62CFA4C13');
        $this->addSql('ALTER TABLE genset_real_time_data DROP FOREIGN KEY FK_9AEAA4D32CFA4C13');
        $this->addSql('ALTER TABLE load_energy_data DROP FOREIGN KEY FK_B7FD8E362CFA4C13');
        $this->addSql('ALTER TABLE smart_mod_zone DROP FOREIGN KEY FK_D703B6422CFA4C13');
        $this->addSql('ALTER TABLE site_user DROP FOREIGN KEY FK_B6096BB0A76ED395');
        $this->addSql('ALTER TABLE zone_user DROP FOREIGN KEY FK_1F6B17ECA76ED395');
        $this->addSql('ALTER TABLE clever_box DROP FOREIGN KEY FK_CB134DE19F2C3FAB');
        $this->addSql('ALTER TABLE smart_mod_zone DROP FOREIGN KEY FK_D703B6429F2C3FAB');
        $this->addSql('ALTER TABLE zone_user DROP FOREIGN KEY FK_1F6B17EC9F2C3FAB');
        $this->addSql('DROP TABLE clever_box');
        $this->addSql('DROP TABLE enterprise');
        $this->addSql('DROP TABLE genset_data');
        $this->addSql('DROP TABLE genset_real_time_data');
        $this->addSql('DROP TABLE load_energy_data');
        $this->addSql('DROP TABLE site');
        $this->addSql('DROP TABLE site_user');
        $this->addSql('DROP TABLE smart_device');
        $this->addSql('DROP TABLE smart_mod');
        $this->addSql('DROP TABLE smart_mod_zone');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE zone');
        $this->addSql('DROP TABLE zone_user');
    }
}
