<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241107152424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cohort_user DROP FOREIGN KEY FK_91FD5019A76ED395');
        $this->addSql('ALTER TABLE cohort_user DROP FOREIGN KEY FK_91FD501935983C93');
        $this->addSql('DROP TABLE cohort_user');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cohort_user (cohort_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_91FD5019A76ED395 (user_id), INDEX IDX_91FD501935983C93 (cohort_id), PRIMARY KEY(cohort_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE cohort_user ADD CONSTRAINT FK_91FD5019A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cohort_user ADD CONSTRAINT FK_91FD501935983C93 FOREIGN KEY (cohort_id) REFERENCES cohort (id) ON DELETE CASCADE');
    }
}
