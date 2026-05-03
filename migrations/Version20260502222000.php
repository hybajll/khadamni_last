<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502222000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cvPhotoPath column to cv table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cv ADD cvPhotoPath VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cv DROP cvPhotoPath');
    }
}

