<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210924032208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enterprise ADD slug VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE site ADD activity_area VARCHAR(30) NOT NULL');
        $this->addSql('ALTER TABLE smart_device ADD type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE smart_mod ADD slug VARCHAR(40) NOT NULL');
        $this->addSql('ALTER TABLE zone ADD slug VARCHAR(40) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enterprise DROP slug');
        $this->addSql('ALTER TABLE site DROP activity_area');
        $this->addSql('ALTER TABLE smart_device DROP type');
        $this->addSql('ALTER TABLE smart_mod DROP slug');
        $this->addSql('ALTER TABLE zone DROP slug');
    }
}
