<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211102134138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE load_energy_data ADD working_genset SMALLINT DEFAULT NULL, ADD depassement INT DEFAULT NULL');
        $this->addSql('ALTER TABLE site ADD has_one_smart_mod TINYINT(1) DEFAULT NULL, CHANGE subscription_usage subscription_usage VARCHAR(100) NOT NULL');
        //$this->addSql("UPDATE load_energy_data SET depassement=FLOOR(RAND()*(10-0+1)+0), working_genset=FLOOR(RAND()*(1-0+1)+0) WHERE 1");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE load_energy_data DROP working_genset, DROP depassement');
        $this->addSql('ALTER TABLE site DROP has_one_smart_mod, CHANGE subscription_usage subscription_usage VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
