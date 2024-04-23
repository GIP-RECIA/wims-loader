<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240423145434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE grouping_classes_user (grouping_classes_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_3114D94A3DB8CE1B (grouping_classes_id), INDEX IDX_3114D94AA76ED395 (user_id), PRIMARY KEY(grouping_classes_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE grouping_classes_user ADD CONSTRAINT FK_3114D94A3DB8CE1B FOREIGN KEY (grouping_classes_id) REFERENCES grouping_classes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE grouping_classes_user ADD CONSTRAINT FK_3114D94AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE grouping_classes_user DROP FOREIGN KEY FK_3114D94A3DB8CE1B');
        $this->addSql('ALTER TABLE grouping_classes_user DROP FOREIGN KEY FK_3114D94AA76ED395');
        $this->addSql('DROP TABLE grouping_classes_user');
    }
}
