<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260129002207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reference_approval (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, reference_id INT NOT NULL, approved_by_id INT NOT NULL, INDEX IDX_CEEACE541645DEA9 (reference_id), INDEX IDX_CEEACE542D234F6A (approved_by_id), UNIQUE INDEX unique_approval (reference_id, approved_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE reference_approval ADD CONSTRAINT FK_CEEACE541645DEA9 FOREIGN KEY (reference_id) REFERENCES joanna_reference (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reference_approval ADD CONSTRAINT FK_CEEACE542D234F6A FOREIGN KEY (approved_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reference_approval DROP FOREIGN KEY FK_CEEACE541645DEA9');
        $this->addSql('ALTER TABLE reference_approval DROP FOREIGN KEY FK_CEEACE542D234F6A');
        $this->addSql('DROP TABLE reference_approval');
    }
}
