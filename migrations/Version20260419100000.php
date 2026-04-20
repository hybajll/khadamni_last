<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add subscription fields to society (freeUsageCount, subscriptionEndDate).';
    }

    public function up(Schema $schema): void
    {
        // Note: keep column names in camelCase for consistency with existing XAMPP schema choices.
        $this->addSql('ALTER TABLE society ADD freeUsageCount INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE society ADD subscriptionEndDate DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE society DROP freeUsageCount');
        $this->addSql('ALTER TABLE society DROP subscriptionEndDate');
    }
}

