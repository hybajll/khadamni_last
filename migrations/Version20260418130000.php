<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add subscription/payment system + user usage counters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD freeUsageCount INT NOT NULL, ADD subscriptionEndDate DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE subscriptions (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, start_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', end_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', currency VARCHAR(10) NOT NULL, amount INT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_A3C664D6A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payments (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, subscription_id INT DEFAULT NULL, amount INT NOT NULL, currency VARCHAR(10) NOT NULL, status VARCHAR(20) NOT NULL, provider_ref VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_65D29B32A76ED395 (user_id), INDEX IDX_65D29B32C54C8C93 (subscription_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE subscriptions ADD CONSTRAINT FK_A3C664D6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B32A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B32C54C8C93 FOREIGN KEY (subscription_id) REFERENCES subscriptions (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscriptions DROP FOREIGN KEY FK_A3C664D6A76ED395');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B32A76ED395');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B32C54C8C93');
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE subscriptions');
        $this->addSql('ALTER TABLE user DROP freeUsageCount, DROP subscriptionEndDate');
    }
}

