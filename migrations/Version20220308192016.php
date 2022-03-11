<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220308192016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alarm (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(20) NOT NULL, label VARCHAR(255) NOT NULL, type VARCHAR(30) NOT NULL, media VARCHAR(20) NOT NULL, alerte VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE alarm_reporting (id INT AUTO_INCREMENT NOT NULL, alarm_id INT NOT NULL, smart_mod_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_1900779325830571 (alarm_id), INDEX IDX_190077932CFA4C13 (smart_mod_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contacts (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, site_id INT NOT NULL, first_name VARCHAR(50) NOT NULL, last_name VARCHAR(50) NOT NULL, email VARCHAR(100) NOT NULL, phone_number VARCHAR(30) NOT NULL, country_code VARCHAR(10) NOT NULL, INDEX IDX_33401573A76ED395 (user_id), INDEX IDX_33401573F6BD1646 (site_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE alarm_reporting ADD CONSTRAINT FK_1900779325830571 FOREIGN KEY (alarm_id) REFERENCES alarm (id)');
        $this->addSql('ALTER TABLE alarm_reporting ADD CONSTRAINT FK_190077932CFA4C13 FOREIGN KEY (smart_mod_id) REFERENCES smart_mod (id)');
        $this->addSql('ALTER TABLE contacts ADD CONSTRAINT FK_33401573A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE contacts ADD CONSTRAINT FK_33401573F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alarm_reporting DROP FOREIGN KEY FK_1900779325830571');
        $this->addSql('DROP TABLE alarm');
        $this->addSql('DROP TABLE alarm_reporting');
        $this->addSql('DROP TABLE contacts');
    }
}
