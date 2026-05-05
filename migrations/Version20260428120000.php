<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add phone column to user table.';
    }

    public function up(Schema $schema): void
    {
        // `user` is a reserved keyword in MySQL, so we quote it.
        $this->addSql('ALTER TABLE `user` ADD phone VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP phone');
    }
}

