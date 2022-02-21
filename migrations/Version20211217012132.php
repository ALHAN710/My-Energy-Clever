<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211217012132 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE genset_data ADD nb_performed_start_ups INT DEFAULT NULL, ADD nb_stop INT DEFAULT NULL, CHANGE total_running_hours total_running_hours DOUBLE PRECISION DEFAULT NULL, CHANGE total_energy total_energy DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE genset_data DROP nb_performed_start_ups, DROP nb_stop, CHANGE total_running_hours total_running_hours INT DEFAULT NULL, CHANGE total_energy total_energy INT DEFAULT NULL');
    }
}
