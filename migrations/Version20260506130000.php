<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notifications and sms_log tables';
    }

    public function up(Schema $schema): void
    {
        // notifications (in-app bell)
        $this->addSql('CREATE TABLE notifications (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message LONGTEXT NOT NULL,
            link VARCHAR(500) DEFAULT NULL,
            is_read TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_NOTIFICATIONS_USER (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_NOTIFICATIONS_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');

        // sms_log (trace SMS)
        $this->addSql('CREATE TABLE sms_log (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            phone_number VARCHAR(20) NOT NULL,
            message LONGTEXT NOT NULL,
            success TINYINT(1) NOT NULL,
            subscription_end_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\',
            sent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_SMS_LOG_USER (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sms_log ADD CONSTRAINT FK_SMS_LOG_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sms_log');
        $this->addSql('DROP TABLE notifications');
    }
}

