<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add offer applications (user applies to offer)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE offer_applications (id INT AUTO_INCREMENT NOT NULL, offer_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_423484CB53EC7A02 (offer_id), INDEX IDX_423484CBA76ED395 (user_id), UNIQUE INDEX uniq_offer_user (offer_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE offer_applications ADD CONSTRAINT FK_423484CB53EC7A02 FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE offer_applications ADD CONSTRAINT FK_423484CBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE offer_applications DROP FOREIGN KEY FK_423484CB53EC7A02');
        $this->addSql('ALTER TABLE offer_applications DROP FOREIGN KEY FK_423484CBA76ED395');
        $this->addSql('DROP TABLE offer_applications');
    }
}

