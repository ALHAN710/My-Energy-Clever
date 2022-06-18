<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220617024241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE genset_data ADD batt_voltage DOUBLE PRECISION DEFAULT NULL, ADD batt_energy DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE genset_real_time_data ADD batt_state TINYINT(1) DEFAULT NULL, ADD batt_energy DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE genset_data DROP batt_voltage, DROP batt_energy');
        $this->addSql('ALTER TABLE genset_real_time_data DROP batt_state, DROP batt_energy');
    }
}
