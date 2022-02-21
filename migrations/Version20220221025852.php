<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220221025852 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE genset_data ADD qmax DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE load_energy_data ADD cosfiamin DOUBLE PRECISION DEFAULT NULL, ADD cosfibmin DOUBLE PRECISION DEFAULT NULL, ADD cosficmin DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE genset_data DROP qmax');
        $this->addSql('ALTER TABLE load_energy_data DROP cosfiamin, DROP cosfibmin, DROP cosficmin');
    }
}
