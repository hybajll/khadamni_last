<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260507232256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE candidature (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(150) NOT NULL, cv_path VARCHAR(255) NOT NULL, offer_id INT NOT NULL, INDEX IDX_E33BD3B853C674EE (offer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notifications (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, link VARCHAR(500) DEFAULT NULL, is_read TINYINT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_6000B0D3A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offer_applications (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, offer_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_10F0694B53C674EE (offer_id), INDEX IDX_10F0694BA76ED395 (user_id), UNIQUE INDEX uniq_offer_user (offer_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payments (id INT AUTO_INCREMENT NOT NULL, amount INT NOT NULL, currency VARCHAR(10) NOT NULL, status VARCHAR(20) NOT NULL, provider_ref VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, subscription_id INT DEFAULT NULL, INDEX IDX_65D29B32A76ED395 (user_id), INDEX IDX_65D29B329A1887DC (subscription_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE recommendation (id INT AUTO_INCREMENT NOT NULL, score DOUBLE PRECISION NOT NULL, candidature_id INT NOT NULL, UNIQUE INDEX UNIQ_433224D2B6121583 (candidature_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sms_log (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, phone_number VARCHAR(20) NOT NULL, message LONGTEXT NOT NULL, success TINYINT NOT NULL, subscription_end_date DATE DEFAULT NULL, sent_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_A9E43D70A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE subscriptions (id INT AUTO_INCREMENT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, currency VARCHAR(10) NOT NULL, amount INT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_4778A01A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE candidature ADD CONSTRAINT FK_E33BD3B853C674EE FOREIGN KEY (offer_id) REFERENCES offers (id)');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE offer_applications ADD CONSTRAINT FK_10F0694B53C674EE FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE offer_applications ADD CONSTRAINT FK_10F0694BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B32A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B329A1887DC FOREIGN KEY (subscription_id) REFERENCES subscriptions (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE recommendation ADD CONSTRAINT FK_433224D2B6121583 FOREIGN KEY (candidature_id) REFERENCES candidature (id)');
        $this->addSql('ALTER TABLE sms_log ADD CONSTRAINT FK_A9E43D70A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subscriptions ADD CONSTRAINT FK_4778A01A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE abonnement');
        $this->addSql('ALTER TABLE cv DROP FOREIGN KEY `FK_cv_idUser_user`');
        $this->addSql('ALTER TABLE cv DROP FOREIGN KEY `FK_cv_idUser_user`');
        $this->addSql('ALTER TABLE cv ADD cvPhotoPath VARCHAR(255) DEFAULT NULL, CHANGE nombreAmeliorations nombreAmeliorations INT NOT NULL, CHANGE estPublic estPublic TINYINT NOT NULL, CHANGE pdfPath pdfPath VARCHAR(255) DEFAULT NULL, CHANGE contenuAmeliore conseilsAi LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE cv ADD CONSTRAINT FK_B66FFE92FE6E88D7 FOREIGN KEY (idUser) REFERENCES user (id)');
        $this->addSql('DROP INDEX fk_cv_iduser_user ON cv');
        $this->addSql('CREATE INDEX IDX_B66FFE92FE6E88D7 ON cv (idUser)');
        $this->addSql('ALTER TABLE cv ADD CONSTRAINT `FK_cv_idUser_user` FOREIGN KEY (idUser) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY `FK_CE606404A76ED395`');
        $this->addSql('DROP INDEX fk_ce606404a76ed395 ON reclamation');
        $this->addSql('CREATE INDEX IDX_CE606404A76ED395 ON reclamation (user_id)');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT `FK_CE606404A76ED395` FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reponse_reclamation DROP FOREIGN KEY `FK_REPLY_SOCIETY`');
        $this->addSql('ALTER TABLE reponse_reclamation DROP FOREIGN KEY `FK_REPLY_SOCIETY`');
        $this->addSql('ALTER TABLE reponse_reclamation ADD CONSTRAINT FK_C7CB510147F0C839 FOREIGN KEY (society_auteur_id) REFERENCES society (id)');
        $this->addSql('DROP INDEX fk_reply_society ON reponse_reclamation');
        $this->addSql('CREATE INDEX IDX_C7CB510147F0C839 ON reponse_reclamation (society_auteur_id)');
        $this->addSql('ALTER TABLE reponse_reclamation ADD CONSTRAINT `FK_REPLY_SOCIETY` FOREIGN KEY (society_auteur_id) REFERENCES society (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE user ADD phone VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE abonnement (id INT AUTO_INCREMENT NOT NULL, etudiant_id INT NOT NULL, type ENUM(\'CV\', \'CV_LETTRE\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, montant NUMERIC(10, 2) NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, actif TINYINT DEFAULT 1, INDEX fk_abo_etudiant (etudiant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE candidature DROP FOREIGN KEY FK_E33BD3B853C674EE');
        $this->addSql('ALTER TABLE notifications DROP FOREIGN KEY FK_6000B0D3A76ED395');
        $this->addSql('ALTER TABLE offer_applications DROP FOREIGN KEY FK_10F0694B53C674EE');
        $this->addSql('ALTER TABLE offer_applications DROP FOREIGN KEY FK_10F0694BA76ED395');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B32A76ED395');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B329A1887DC');
        $this->addSql('ALTER TABLE recommendation DROP FOREIGN KEY FK_433224D2B6121583');
        $this->addSql('ALTER TABLE sms_log DROP FOREIGN KEY FK_A9E43D70A76ED395');
        $this->addSql('ALTER TABLE subscriptions DROP FOREIGN KEY FK_4778A01A76ED395');
        $this->addSql('DROP TABLE candidature');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE offer_applications');
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE recommendation');
        $this->addSql('DROP TABLE sms_log');
        $this->addSql('DROP TABLE subscriptions');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE cv DROP FOREIGN KEY FK_B66FFE92FE6E88D7');
        $this->addSql('ALTER TABLE cv DROP FOREIGN KEY FK_B66FFE92FE6E88D7');
        $this->addSql('ALTER TABLE cv DROP cvPhotoPath, CHANGE nombreAmeliorations nombreAmeliorations INT DEFAULT 0 NOT NULL, CHANGE estPublic estPublic TINYINT DEFAULT 0 NOT NULL, CHANGE pdfPath pdfPath VARCHAR(255) DEFAULT \'NULL\', CHANGE conseilsAi contenuAmeliore LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE cv ADD CONSTRAINT `FK_cv_idUser_user` FOREIGN KEY (idUser) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_b66ffe92fe6e88d7 ON cv');
        $this->addSql('CREATE INDEX FK_cv_idUser_user ON cv (idUser)');
        $this->addSql('ALTER TABLE cv ADD CONSTRAINT FK_B66FFE92FE6E88D7 FOREIGN KEY (idUser) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY FK_CE606404A76ED395');
        $this->addSql('DROP INDEX idx_ce606404a76ed395 ON reclamation');
        $this->addSql('CREATE INDEX FK_CE606404A76ED395 ON reclamation (user_id)');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT FK_CE606404A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reponse_reclamation DROP FOREIGN KEY FK_C7CB510147F0C839');
        $this->addSql('ALTER TABLE reponse_reclamation DROP FOREIGN KEY FK_C7CB510147F0C839');
        $this->addSql('ALTER TABLE reponse_reclamation ADD CONSTRAINT `FK_REPLY_SOCIETY` FOREIGN KEY (society_auteur_id) REFERENCES society (id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX idx_c7cb510147f0c839 ON reponse_reclamation');
        $this->addSql('CREATE INDEX FK_REPLY_SOCIETY ON reponse_reclamation (society_auteur_id)');
        $this->addSql('ALTER TABLE reponse_reclamation ADD CONSTRAINT FK_C7CB510147F0C839 FOREIGN KEY (society_auteur_id) REFERENCES society (id)');
        $this->addSql('ALTER TABLE user DROP phone');
    }
}
