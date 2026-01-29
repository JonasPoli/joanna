<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260128194219 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bible_version (id INT AUTO_INCREMENT NOT NULL, version VARCHAR(50) NOT NULL, info VARCHAR(255) DEFAULT NULL, copyright VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE book (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, abbrev VARCHAR(10) NOT NULL, testament_id INT NOT NULL, INDEX IDX_CBE5A331386D1BF0 (testament_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE joanna_reference (id INT AUTO_INCREMENT NOT NULL, joanna_chapter VARCHAR(255) DEFAULT NULL, bible_chapter INT DEFAULT NULL, bible_verse_start INT DEFAULT NULL, bible_verse_end INT DEFAULT NULL, reference_type VARCHAR(50) NOT NULL, citation LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, work_id INT NOT NULL, bible_book_id INT NOT NULL, created_by_id INT NOT NULL, INDEX IDX_82B434A7BB3453DB (work_id), INDEX IDX_82B434A767FFD77 (bible_book_id), INDEX IDX_82B434A7B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE joanna_work (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, publication_year INT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE testament (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE verse (id INT AUTO_INCREMENT NOT NULL, chapter INT NOT NULL, verse INT NOT NULL, text LONGTEXT NOT NULL, version_id INT NOT NULL, book_id INT NOT NULL, INDEX IDX_D2F7E69F4BBC2705 (version_id), INDEX IDX_D2F7E69F16A2B381 (book_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE verse_reference (id INT AUTO_INCREMENT NOT NULL, referer VARCHAR(255) NOT NULL, verse_id INT NOT NULL, INDEX IDX_5341CC34BBF309FA (verse_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A331386D1BF0 FOREIGN KEY (testament_id) REFERENCES testament (id)');
        $this->addSql('ALTER TABLE joanna_reference ADD CONSTRAINT FK_82B434A7BB3453DB FOREIGN KEY (work_id) REFERENCES joanna_work (id)');
        $this->addSql('ALTER TABLE joanna_reference ADD CONSTRAINT FK_82B434A767FFD77 FOREIGN KEY (bible_book_id) REFERENCES book (id)');
        $this->addSql('ALTER TABLE joanna_reference ADD CONSTRAINT FK_82B434A7B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE verse ADD CONSTRAINT FK_D2F7E69F4BBC2705 FOREIGN KEY (version_id) REFERENCES bible_version (id)');
        $this->addSql('ALTER TABLE verse ADD CONSTRAINT FK_D2F7E69F16A2B381 FOREIGN KEY (book_id) REFERENCES book (id)');
        $this->addSql('ALTER TABLE verse_reference ADD CONSTRAINT FK_5341CC34BBF309FA FOREIGN KEY (verse_id) REFERENCES verse (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book DROP FOREIGN KEY FK_CBE5A331386D1BF0');
        $this->addSql('ALTER TABLE joanna_reference DROP FOREIGN KEY FK_82B434A7BB3453DB');
        $this->addSql('ALTER TABLE joanna_reference DROP FOREIGN KEY FK_82B434A767FFD77');
        $this->addSql('ALTER TABLE joanna_reference DROP FOREIGN KEY FK_82B434A7B03A8386');
        $this->addSql('ALTER TABLE verse DROP FOREIGN KEY FK_D2F7E69F4BBC2705');
        $this->addSql('ALTER TABLE verse DROP FOREIGN KEY FK_D2F7E69F16A2B381');
        $this->addSql('ALTER TABLE verse_reference DROP FOREIGN KEY FK_5341CC34BBF309FA');
        $this->addSql('DROP TABLE bible_version');
        $this->addSql('DROP TABLE book');
        $this->addSql('DROP TABLE joanna_reference');
        $this->addSql('DROP TABLE joanna_work');
        $this->addSql('DROP TABLE testament');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE verse');
        $this->addSql('DROP TABLE verse_reference');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
