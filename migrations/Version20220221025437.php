<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220221025437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE genset_data ADD va DOUBLE PRECISION DEFAULT NULL, ADD vb DOUBLE PRECISION DEFAULT NULL, ADD vc DOUBLE PRECISION DEFAULT NULL, ADD pamax DOUBLE PRECISION DEFAULT NULL, ADD pbmax DOUBLE PRECISION DEFAULT NULL, ADD pcmax DOUBLE PRECISION DEFAULT NULL, ADD pmax DOUBLE PRECISION DEFAULT NULL, ADD qa DOUBLE PRECISION DEFAULT NULL, ADD qamax DOUBLE PRECISION DEFAULT NULL, ADD qb DOUBLE PRECISION DEFAULT NULL, ADD qbmax DOUBLE PRECISION DEFAULT NULL, ADD qc DOUBLE PRECISION DEFAULT NULL, ADD qcmax DOUBLE PRECISION DEFAULT NULL, ADD q DOUBLE PRECISION DEFAULT NULL, ADD samax DOUBLE PRECISION DEFAULT NULL, ADD sbmax DOUBLE PRECISION DEFAULT NULL, ADD scmax DOUBLE PRECISION DEFAULT NULL, ADD smax DOUBLE PRECISION DEFAULT NULL, ADD cosfiamin DOUBLE PRECISION DEFAULT NULL, ADD cosfibmin DOUBLE PRECISION DEFAULT NULL, ADD cosficmin DOUBLE PRECISION DEFAULT NULL, ADD cosfimin DOUBLE PRECISION DEFAULT NULL, ADD eaa DOUBLE PRECISION DEFAULT NULL, ADD eab DOUBLE PRECISION DEFAULT NULL, ADD eac DOUBLE PRECISION DEFAULT NULL, ADD era DOUBLE PRECISION DEFAULT NULL, ADD erb DOUBLE PRECISION DEFAULT NULL, ADD erc DOUBLE PRECISION DEFAULT NULL, ADD er DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE load_energy_data ADD pamax DOUBLE PRECISION DEFAULT NULL, ADD pbmax DOUBLE PRECISION DEFAULT NULL, ADD pcmax DOUBLE PRECISION DEFAULT NULL, ADD samax DOUBLE PRECISION DEFAULT NULL, ADD sbmax DOUBLE PRECISION DEFAULT NULL, ADD scmax DOUBLE PRECISION DEFAULT NULL, ADD smax DOUBLE PRECISION DEFAULT NULL, ADD qamax DOUBLE PRECISION DEFAULT NULL, ADD qbmax DOUBLE PRECISION DEFAULT NULL, ADD qcmax DOUBLE PRECISION DEFAULT NULL, ADD qmax DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE genset_data DROP va, DROP vb, DROP vc, DROP pamax, DROP pbmax, DROP pcmax, DROP pmax, DROP qa, DROP qamax, DROP qb, DROP qbmax, DROP qc, DROP qcmax, DROP q, DROP samax, DROP sbmax, DROP scmax, DROP smax, DROP cosfiamin, DROP cosfibmin, DROP cosficmin, DROP cosfimin, DROP eaa, DROP eab, DROP eac, DROP era, DROP erb, DROP erc, DROP er');
        $this->addSql('ALTER TABLE load_energy_data DROP pamax, DROP pbmax, DROP pcmax, DROP samax, DROP sbmax, DROP scmax, DROP smax, DROP qamax, DROP qbmax, DROP qcmax, DROP qmax');
    }
}
