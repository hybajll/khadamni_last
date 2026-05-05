<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260416191712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('DROP TABLE abonnement');
        $this->addSql('DROP TABLE candidature');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE comment_likes');
        $this->addSql('DROP TABLE employeur');
        $this->addSql('DROP TABLE news');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE offers');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('DROP TABLE recommendation');
        $this->addSql('DROP TABLE society');
        $this->addSql('DROP TABLE user2');
        $this->addSql('ALTER TABLE cv DROP FOREIGN KEY `FK_cv_idUser_user`');
        $this->addSql('ALTER TABLE cv CHANGE nombreAmeliorations nombreAmeliorations INT NOT NULL, CHANGE estPublic estPublic TINYINT NOT NULL, CHANGE pdfPath pdfPath VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE cv ADD CONSTRAINT FK_B66FFE92FE6E88D7 FOREIGN KEY (idUser) REFERENCES user (id)');
        $this->addSql('ALTER TABLE cv RENAME INDEX idx_cv_iduser TO IDX_B66FFE92FE6E88D7');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY `fk_user_reclamation`');
        $this->addSql('DROP INDEX fk_user_reclamation ON reclamation');
        $this->addSql('ALTER TABLE reclamation ADD user_id INT NOT NULL, DROP id, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE date_modification date_modification DATETIME DEFAULT NULL, CHANGE statut statut VARCHAR(255) NOT NULL, CHANGE type type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT FK_CE606404A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_CE606404A76ED395 ON reclamation (user_id)');
        $this->addSql('ALTER TABLE reponse_reclamation DROP type_auteur, CHANGE message message LONGTEXT NOT NULL, CHANGE date_reponse date_reponse DATETIME NOT NULL');
        $this->addSql('ALTER TABLE reponse_reclamation ADD CONSTRAINT FK_C7CB510160BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_C7CB510160BB6FE6 ON reponse_reclamation (auteur_id)');
        $this->addSql('ALTER TABLE reponse_reclamation RENAME INDEX id_reclamation TO IDX_C7CB5101D672A9F3');
        $this->addSql('ALTER TABLE user CHANGE nom nom VARCHAR(255) DEFAULT NULL, CHANGE prenom prenom VARCHAR(255) DEFAULT NULL, CHANGE actif actif TINYINT NOT NULL, CHANGE LocalDateTime LocalDateTime DATETIME DEFAULT NULL, CHANGE role role VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_user_email TO UNIQ_8D93D649E7927C74');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE abonnement (id INT NOT NULL, etudiant_id INT NOT NULL, type ENUM(\'CV\', \'CV_LETTRE\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, montant NUMERIC(10, 2) NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, actif TINYINT DEFAULT 1, INDEX fk_abo_etudiant (etudiant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE candidature (id INT NOT NULL, id_user INT DEFAULT NULL, email VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, offre_titre VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, cv_path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, lettre_motivation TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, statut VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'\'\'en attente\'\'\' COLLATE `utf8mb4_general_ci`, date_candidature DATE DEFAULT \'curdate()\', PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, content VARCHAR(5000) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, news_id INT NOT NULL, INDEX news_id (news_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE comment_likes (comment_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY (comment_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE employeur (id_employeur INT NOT NULL, nom_employeur VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, mdp VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, domaine_travail VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, UNIQUE INDEX email (email), PRIMARY KEY (id_employeur)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE news (id INT AUTO_INCREMENT NOT NULL, title TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, content TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, creation_date DATE NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE notification (id INT NOT NULL, etudiant_id INT NOT NULL, paiement_id INT DEFAULT NULL, type ENUM(\'PAIEMENT\', \'OFFRE\', \'CANDIDATURE\', \'RECOMMANDATION\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, titre VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, contenu TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, lue TINYINT DEFAULT 0, date_envoi DATETIME DEFAULT \'current_timestamp()\', INDEX fk_notif_paiement (paiement_id), INDEX fk_notif_etudiant (etudiant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE offers (id INT NOT NULL, society_id INT NOT NULL, title VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, domain VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, salary NUMERIC(10, 2) DEFAULT \'NULL\', contract_type ENUM(\'Internship\', \'Freelance\', \'Part-time\', \'Full-time\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, location VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, experience_level ENUM(\'Junior\', \'Mid\', \'Senior\', \'Manager\') CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, expiration_date DATE DEFAULT \'NULL\', INDEX fk_society (society_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE paiement (id INT AUTO_INCREMENT NOT NULL, etudiant_id INT NOT NULL, abonnement_id INT NOT NULL, montant NUMERIC(10, 2) NOT NULL, devise VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT \'\'\'TND\'\'\' COLLATE `utf8mb4_unicode_ci`, methode ENUM(\'CARTE\', \'VIREMENT\', \'MOBILE\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, statut ENUM(\'EN_ATTENTE\', \'VALIDE\', \'ECHOUE\', \'REMBOURSE\') CHARACTER SET utf8mb4 DEFAULT \'\'\'EN_ATTENTE\'\'\' COLLATE `utf8mb4_unicode_ci`, reference VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, date_paiement DATETIME DEFAULT \'current_timestamp()\', PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE recommendation (id INT AUTO_INCREMENT NOT NULL, email_candidat VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, offre_titre VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, score DOUBLE PRECISION NOT NULL, date_calcul DATETIME DEFAULT \'current_timestamp()\' NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE society (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, password VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, phone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, address VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, domain VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, website VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user2 (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, prenom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, role VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'\'\'USER\'\'\' COLLATE `utf8mb4_general_ci`, actif TINYINT DEFAULT 1, date_inscription DATETIME DEFAULT \'current_timestamp()\', photo LONGBLOB DEFAULT NULL, UNIQUE INDEX email (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE cv DROP FOREIGN KEY FK_B66FFE92FE6E88D7');
        $this->addSql('ALTER TABLE cv CHANGE nombreAmeliorations nombreAmeliorations INT DEFAULT 0 NOT NULL, CHANGE estPublic estPublic TINYINT DEFAULT 0 NOT NULL, CHANGE pdfPath pdfPath VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE cv ADD CONSTRAINT `FK_cv_idUser_user` FOREIGN KEY (idUser) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cv RENAME INDEX idx_b66ffe92fe6e88d7 TO IDX_cv_idUser');
        $this->addSql('ALTER TABLE reclamation DROP FOREIGN KEY FK_CE606404A76ED395');
        $this->addSql('DROP INDEX IDX_CE606404A76ED395 ON reclamation');
        $this->addSql('ALTER TABLE reclamation ADD id INT DEFAULT NULL, DROP user_id, CHANGE description description TEXT DEFAULT NULL, CHANGE date_modification date_modification DATETIME DEFAULT \'NULL\', CHANGE statut statut ENUM(\'EN_ATTENTE\', \'RESOLUE\', \'EN_COURS\', \'REJETEE\') NOT NULL, CHANGE type type ENUM(\'PLATEFORME\', \'ENTREPRISE\', \'STAGE\', \'OFFRE_EMPLOIE\') NOT NULL');
        $this->addSql('ALTER TABLE reclamation ADD CONSTRAINT `fk_user_reclamation` FOREIGN KEY (id) REFERENCES user2 (id)');
        $this->addSql('CREATE INDEX fk_user_reclamation ON reclamation (id)');
        $this->addSql('ALTER TABLE reponse_reclamation DROP FOREIGN KEY FK_C7CB510160BB6FE6');
        $this->addSql('DROP INDEX IDX_C7CB510160BB6FE6 ON reponse_reclamation');
        $this->addSql('ALTER TABLE reponse_reclamation ADD type_auteur ENUM(\'USER\', \'ADMIN\') NOT NULL, CHANGE message message TEXT NOT NULL, CHANGE date_reponse date_reponse DATETIME DEFAULT \'current_timestamp()\'');
        $this->addSql('ALTER TABLE reponse_reclamation RENAME INDEX idx_c7cb5101d672a9f3 TO id_reclamation');
        $this->addSql('ALTER TABLE user CHANGE nom nom VARCHAR(255) DEFAULT \'NULL\', CHANGE prenom prenom VARCHAR(255) DEFAULT \'NULL\', CHANGE actif actif TINYINT DEFAULT 1 NOT NULL, CHANGE LocalDateTime LocalDateTime DATETIME DEFAULT \'NULL\', CHANGE role role VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_8d93d649e7927c74 TO UNIQ_user_email');
    }
}
