<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240416155802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE grouping_classes (id INT AUTO_INCREMENT NOT NULL, uai VARCHAR(8) NOT NULL, id_wims VARCHAR(7) NOT NULL, siren VARCHAR(14) NOT NULL, name VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_ID (id), UNIQUE INDEX UNIQ_IDENTIFIER_UAI (uai), UNIQUE INDEX UNIQ_IDENTIFIER_ID_WIMS (id_wims), UNIQUE INDEX UNIQ_IDENTIFIER_SIREN (siren), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE grouping_classes');
    }
}
