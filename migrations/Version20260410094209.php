<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260410094209 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE questions_enquete DROP FOREIGN KEY `fk_attribut_enquete`');
        $this->addSql('ALTER TABLE reponse_enquete DROP FOREIGN KEY `fk_reponse_enquete`');
        $this->addSql('DROP TABLE abonnement');
        $this->addSql('DROP TABLE candidature');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE connexions_utilisateur');
        $this->addSql('DROP TABLE employeur');
        $this->addSql('DROP TABLE enquete');
        $this->addSql('DROP TABLE news');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE offers');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('DROP TABLE questions_enquete');
        $this->addSql('DROP TABLE reclamation');
        $this->addSql('DROP TABLE recommendation');
        $this->addSql('DROP TABLE reponse_enquete');
        $this->addSql('DROP TABLE reponse_reclamation');
        $this->addSql('DROP TABLE society');
        $this->addSql('ALTER TABLE cv CHANGE contenuOriginal contenuOriginal LONGTEXT NOT NULL, CHANGE contenuAmeliore contenuAmeliore LONGTEXT DEFAULT NULL, CHANGE dateUpload dateUpload DATETIME NOT NULL, CHANGE nombreAmeliorations nombreAmeliorations INT NOT NULL, CHANGE estPublic estPublic TINYINT NOT NULL');
        $this->addSql('ALTER TABLE cv ADD CONSTRAINT FK_B66FFE92FE6E88D7 FOREIGN KEY (idUser) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_B66FFE92FE6E88D7 ON cv (idUser)');
        $this->addSql('ALTER TABLE user CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE prenom prenom VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE password password VARCHAR(255) NOT NULL, CHANGE type type VARCHAR(255) NOT NULL, CHANGE actif actif TINYINT NOT NULL, CHANGE LocalDateTime LocalDateTime DATETIME DEFAULT NULL, CHANGE role role VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE abonnement (id INT AUTO_INCREMENT NOT NULL, etudiant_id INT NOT NULL, type ENUM(\'CV\', \'CV_LETTRE\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, montant NUMERIC(10, 2) NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, actif TINYINT DEFAULT 1, INDEX fk_abo_etudiant (etudiant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE candidature (id INT AUTO_INCREMENT NOT NULL, id_user INT DEFAULT NULL, email VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, offre_titre VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, cv_path VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, lettre_motivation TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, statut VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'\'\'en attente\'\'\' COLLATE `utf8mb4_general_ci`, date_candidature DATE DEFAULT \'curdate()\', PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, content VARCHAR(5000) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, news_id INT NOT NULL, INDEX news_id (news_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE connexions_utilisateur (id INT AUTO_INCREMENT NOT NULL, id_client INT NOT NULL, nombre_connexions INT DEFAULT 0, date_derniere_enquete DATETIME DEFAULT \'NULL\', date_premiere_connexion DATETIME DEFAULT \'current_timestamp()\', date_derniere_connexion DATETIME DEFAULT \'current_timestamp()\', UNIQUE INDEX id_client (id_client), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE employeur (id_employeur INT AUTO_INCREMENT NOT NULL, nom_employeur VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, mdp VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, domaine_travail VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, UNIQUE INDEX email (email), PRIMARY KEY (id_employeur)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE enquete (id_enquete INT AUTO_INCREMENT NOT NULL, sujet VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, statut ENUM(\'BROUILLON\', \'ACTIVE\', \'CLOTUREE\') CHARACTER SET utf8mb4 DEFAULT \'\'\'BROUILLON\'\'\' COLLATE `utf8mb4_general_ci`, anonyme TINYINT DEFAULT 1, date_creation DATETIME DEFAULT \'current_timestamp()\', type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, PRIMARY KEY (id_enquete)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE news (id INT AUTO_INCREMENT NOT NULL, title TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, content TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, creation_date DATE NOT NULL, user_id INT DEFAULT NULL, INDEX user_id (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, etudiant_id INT NOT NULL, paiement_id INT DEFAULT NULL, type ENUM(\'PAIEMENT\', \'OFFRE\', \'CANDIDATURE\', \'RECOMMANDATION\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, titre VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, contenu TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, lue TINYINT DEFAULT 0, date_envoi DATETIME DEFAULT \'current_timestamp()\', INDEX fk_notif_paiement (paiement_id), INDEX fk_notif_etudiant (etudiant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE offers (id INT NOT NULL, society_id INT NOT NULL, title VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, domain VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, salary NUMERIC(10, 2) DEFAULT \'NULL\', contract_type ENUM(\'Internship\', \'Freelance\', \'Part-time\', \'Full-time\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, location VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, experience_level ENUM(\'Junior\', \'Mid\', \'Senior\', \'Manager\') CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, expiration_date DATE DEFAULT \'NULL\', INDEX fk_society (society_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE paiement (id INT AUTO_INCREMENT NOT NULL, etudiant_id INT NOT NULL, abonnement_id INT NOT NULL, montant NUMERIC(10, 2) NOT NULL, devise VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT \'\'\'TND\'\'\' COLLATE `utf8mb4_unicode_ci`, methode ENUM(\'CARTE\', \'VIREMENT\', \'MOBILE\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, statut ENUM(\'EN_ATTENTE\', \'VALIDE\', \'ECHOUE\', \'REMBOURSE\') CHARACTER SET utf8mb4 DEFAULT \'\'\'EN_ATTENTE\'\'\' COLLATE `utf8mb4_unicode_ci`, reference VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, date_paiement DATETIME DEFAULT \'current_timestamp()\', UNIQUE INDEX reference (reference), INDEX fk_pai_etudiant (etudiant_id), INDEX fk_pai_abonnement (abonnement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE questions_enquete (id_attribut INT AUTO_INCREMENT NOT NULL, id_enquete INT DEFAULT NULL, nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX fk_attribut_enquete (id_enquete), PRIMARY KEY (id_attribut)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reclamation (id_reclamation INT AUTO_INCREMENT NOT NULL, sujet VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_creation DATETIME NOT NULL, date_modification DATETIME DEFAULT \'NULL\', statut ENUM(\'EN_ATTENTE\', \'RESOLUE\', \'EN_COURS\', \'REJETEE\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, type ENUM(\'PLATEFORME\', \'ENTREPRISE\', \'STAGE\', \'OFFRE_emploie\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY (id_reclamation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE recommendation (id INT AUTO_INCREMENT NOT NULL, email_candidat VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, offre_titre VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, score DOUBLE PRECISION NOT NULL, date_calcul DATETIME DEFAULT \'current_timestamp()\' NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reponse_enquete (id_reponse INT AUTO_INCREMENT NOT NULL, id_enquete INT DEFAULT NULL, id INT DEFAULT NULL, date_creation DATETIME DEFAULT \'current_timestamp()\', moyenne DOUBLE PRECISION DEFAULT \'NULL\', INDEX fk_reponse_user (id), UNIQUE INDEX unique_reponse_client (id_enquete, id), INDEX IDX_5FDF1410845C259 (id_enquete), PRIMARY KEY (id_reponse)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reponse_reclamation (id_reponse_reclamation INT AUTO_INCREMENT NOT NULL, id_reclamation INT NOT NULL, auteur_id INT NOT NULL, type_auteur ENUM(\'user\', \'admin\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, message TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_reponse DATETIME DEFAULT \'current_timestamp()\', INDEX id_reclamation (id_reclamation), PRIMARY KEY (id_reponse_reclamation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE society (id INT NOT NULL, name VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, password VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, phone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, address VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, domain VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, website VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE questions_enquete ADD CONSTRAINT `fk_attribut_enquete` FOREIGN KEY (id_enquete) REFERENCES enquete (id_enquete) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reponse_enquete ADD CONSTRAINT `fk_reponse_enquete` FOREIGN KEY (id_enquete) REFERENCES enquete (id_enquete) ON DELETE CASCADE');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE cv DROP FOREIGN KEY FK_B66FFE92FE6E88D7');
        $this->addSql('DROP INDEX IDX_B66FFE92FE6E88D7 ON cv');
        $this->addSql('ALTER TABLE cv CHANGE contenuOriginal contenuOriginal TEXT NOT NULL, CHANGE contenuAmeliore contenuAmeliore TEXT NOT NULL, CHANGE dateUpload dateUpload DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE nombreAmeliorations nombreAmeliorations INT DEFAULT 0 NOT NULL, CHANGE estPublic estPublic TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE nom nom VARCHAR(100) NOT NULL, CHANGE prenom prenom VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(100) NOT NULL, CHANGE password password VARCHAR(50) NOT NULL, CHANGE actif actif TINYINT DEFAULT 1 NOT NULL, CHANGE LocalDateTime LocalDateTime DATETIME DEFAULT \'current_timestamp()\' NOT NULL, CHANGE role role VARCHAR(25) DEFAULT \'NULL\', CHANGE type type VARCHAR(50) NOT NULL');
    }
}
