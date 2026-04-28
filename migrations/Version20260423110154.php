<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260423110154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE offer_applications (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, offer_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_10F0694B53C674EE (offer_id), INDEX IDX_10F0694BA76ED395 (user_id), UNIQUE INDEX uniq_offer_user (offer_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payments (id INT AUTO_INCREMENT NOT NULL, amount INT NOT NULL, currency VARCHAR(10) NOT NULL, status VARCHAR(20) NOT NULL, provider_ref VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, subscription_id INT DEFAULT NULL, INDEX IDX_65D29B32A76ED395 (user_id), INDEX IDX_65D29B329A1887DC (subscription_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE subscriptions (id INT AUTO_INCREMENT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, currency VARCHAR(10) NOT NULL, amount INT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_4778A01A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE offer_applications ADD CONSTRAINT FK_10F0694B53C674EE FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE offer_applications ADD CONSTRAINT FK_10F0694BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B32A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B329A1887DC FOREIGN KEY (subscription_id) REFERENCES subscriptions (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE subscriptions ADD CONSTRAINT FK_4778A01A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv DROP FOREIGN KEY `FK_cv_idUser_user`');
        $this->addSql('ALTER TABLE cv ADD conseilsAi LONGTEXT DEFAULT NULL, CHANGE nombreAmeliorations nombreAmeliorations INT NOT NULL, CHANGE estPublic estPublic TINYINT NOT NULL, CHANGE pdfPath pdfPath VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE cv ADD CONSTRAINT FK_B66FFE92FE6E88D7 FOREIGN KEY (idUser) REFERENCES user (id)');
        $this->addSql('ALTER TABLE cv RENAME INDEX idx_cv_iduser TO IDX_B66FFE92FE6E88D7');
        $this->addSql('ALTER TABLE offers ADD created_at DATETIME NOT NULL, ADD is_active TINYINT NOT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE domain domain VARCHAR(150) DEFAULT NULL, CHANGE salary salary NUMERIC(10, 2) DEFAULT NULL, CHANGE contract_type contract_type VARCHAR(100) NOT NULL, CHANGE location location VARCHAR(150) DEFAULT NULL, CHANGE experience_level experience_level VARCHAR(100) DEFAULT NULL, CHANGE expiration_date expiration_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE offers ADD CONSTRAINT FK_DA460427E6389D24 FOREIGN KEY (society_id) REFERENCES society (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE offers RENAME INDEX fk_society TO IDX_DA460427E6389D24');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY `fk_reclamation_user`');
        $this->addSql('ALTER TABLE reclamation CHANGE description description LONGTEXT DEFAULT NULL, CHANGE date_modification date_modification DATETIME DEFAULT NULL, CHANGE statut statut VARCHAR(255) NOT NULL, CHANGE type type VARCHAR(255) NOT NULL, CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT FK_CE606404BF396750 FOREIGN KEY (id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reclamation RENAME INDEX fk_reclamation_user TO IDX_CE606404BF396750');
        $this->addSql('ALTER TABLE reponse_reclamation DROP type_auteur, CHANGE message message LONGTEXT NOT NULL, CHANGE date_reponse date_reponse DATETIME NOT NULL');
        $this->addSql('ALTER TABLE reponse_reclamation ADD CONSTRAINT FK_C7CB510160BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_C7CB510160BB6FE6 ON reponse_reclamation (auteur_id)');
        $this->addSql('ALTER TABLE reponse_reclamation RENAME INDEX id_reclamation TO IDX_C7CB5101D672A9F3');
        $this->addSql('ALTER TABLE society ADD is_active TINYINT NOT NULL, ADD created_at DATETIME DEFAULT NULL, ADD freeUsageCount INT NOT NULL, ADD subscriptionEndDate DATETIME DEFAULT NULL, CHANGE password password VARCHAR(255) NOT NULL, CHANGE phone phone VARCHAR(20) DEFAULT NULL, CHANGE address address VARCHAR(255) DEFAULT NULL, CHANGE domain domain VARCHAR(150) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE website website VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D6461F2E7927C74 ON society (email)');
        $this->addSql('ALTER TABLE user ADD avatarPath VARCHAR(255) DEFAULT NULL, ADD freeUsageCount INT NOT NULL, ADD subscriptionEndDate DATETIME DEFAULT NULL, CHANGE nom nom VARCHAR(255) DEFAULT NULL, CHANGE prenom prenom VARCHAR(255) DEFAULT NULL, CHANGE LocalDateTime LocalDateTime DATETIME DEFAULT NULL, CHANGE role role VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_user_email TO UNIQ_8D93D649E7927C74');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE offer_applications DROP FOREIGN KEY FK_10F0694B53C674EE');
        $this->addSql('ALTER TABLE offer_applications DROP FOREIGN KEY FK_10F0694BA76ED395');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B32A76ED395');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B329A1887DC');
        $this->addSql('ALTER TABLE subscriptions DROP FOREIGN KEY FK_4778A01A76ED395');
        $this->addSql('DROP TABLE offer_applications');
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE subscriptions');
        $this->addSql('ALTER TABLE cv DROP FOREIGN KEY FK_B66FFE92FE6E88D7');
        $this->addSql('ALTER TABLE cv DROP conseilsAi, CHANGE nombreAmeliorations nombreAmeliorations INT DEFAULT 0 NOT NULL, CHANGE estPublic estPublic TINYINT DEFAULT 0 NOT NULL, CHANGE pdfPath pdfPath VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE cv ADD CONSTRAINT `FK_cv_idUser_user` FOREIGN KEY (idUser) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv RENAME INDEX idx_b66ffe92fe6e88d7 TO IDX_cv_idUser');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE offers DROP FOREIGN KEY FK_DA460427E6389D24');
        $this->addSql('ALTER TABLE offers DROP created_at, DROP is_active, CHANGE id id INT NOT NULL, CHANGE description description TEXT NOT NULL, CHANGE domain domain VARCHAR(150) DEFAULT \'NULL\', CHANGE salary salary NUMERIC(10, 2) DEFAULT \'NULL\', CHANGE contract_type contract_type ENUM(\'Internship\', \'Freelance\', \'Part-time\', \'Full-time\') NOT NULL, CHANGE location location VARCHAR(150) DEFAULT \'NULL\', CHANGE experience_level experience_level ENUM(\'Junior\', \'Mid\', \'Senior\', \'Manager\') DEFAULT \'NULL\', CHANGE expiration_date expiration_date DATE DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE offers RENAME INDEX idx_da460427e6389d24 TO fk_society');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY FK_CE606404BF396750');
        $this->addSql('ALTER TABLE reclamation CHANGE description description TEXT DEFAULT NULL, CHANGE date_modification date_modification DATETIME DEFAULT \'NULL\', CHANGE statut statut ENUM(\'EN_ATTENTE\', \'RESOLUE\', \'EN_COURS\', \'REJETEE\') NOT NULL, CHANGE type type ENUM(\'PLATEFORME\', \'ENTREPRISE\', \'STAGE\', \'OFFRE_EMPLOIE\') NOT NULL, CHANGE id id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT `fk_reclamation_user` FOREIGN KEY (id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reclamation RENAME INDEX idx_ce606404bf396750 TO fk_reclamation_user');
        $this->addSql('ALTER TABLE reponse_reclamation DROP FOREIGN KEY FK_C7CB510160BB6FE6');
        $this->addSql('DROP INDEX IDX_C7CB510160BB6FE6 ON reponse_reclamation');
        $this->addSql('ALTER TABLE reponse_reclamation ADD type_auteur ENUM(\'USER\', \'ADMIN\') NOT NULL, CHANGE message message TEXT NOT NULL, CHANGE date_reponse date_reponse DATETIME DEFAULT \'current_timestamp()\'');
        $this->addSql('ALTER TABLE reponse_reclamation RENAME INDEX idx_c7cb5101d672a9f3 TO id_reclamation');
        $this->addSql('DROP INDEX UNIQ_D6461F2E7927C74 ON society');
        $this->addSql('ALTER TABLE society DROP is_active, DROP created_at, DROP freeUsageCount, DROP subscriptionEndDate, CHANGE password password VARCHAR(255) DEFAULT \'NULL\', CHANGE phone phone VARCHAR(20) DEFAULT \'NULL\', CHANGE address address VARCHAR(255) DEFAULT \'NULL\', CHANGE domain domain VARCHAR(150) DEFAULT \'NULL\', CHANGE description description TEXT DEFAULT NULL, CHANGE website website VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user DROP avatarPath, DROP freeUsageCount, DROP subscriptionEndDate, CHANGE nom nom VARCHAR(255) DEFAULT \'NULL\', CHANGE prenom prenom VARCHAR(255) DEFAULT \'NULL\', CHANGE LocalDateTime LocalDateTime DATETIME DEFAULT \'NULL\', CHANGE role role VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_8d93d649e7927c74 TO UNIQ_user_email');
    }
}
