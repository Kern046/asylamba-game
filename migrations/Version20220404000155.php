<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220404000155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE color CHANGE sectors sectors SMALLINT UNSIGNED DEFAULT 0 NOT NULL, CHANGE regime regime SMALLINT UNSIGNED DEFAULT 0 NOT NULL, CHANGE electionStatement electionStatement SMALLINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE player CHANGE sex sex SMALLINT DEFAULT 1 NOT NULL, CHANGE level level SMALLINT UNSIGNED DEFAULT 0 NOT NULL, CHANGE victory victory INT UNSIGNED DEFAULT 0 NOT NULL, CHANGE defeat defeat INT UNSIGNED DEFAULT 0 NOT NULL, CHANGE statement statement SMALLINT UNSIGNED DEFAULT 0 NOT NULL, CHANGE stepTutorial stepTutorial SMALLINT UNSIGNED NOT NULL, CHANGE partNaturalSciences partNaturalSciences SMALLINT UNSIGNED DEFAULT 0 NOT NULL, CHANGE partLifeSciences partLifeSciences SMALLINT UNSIGNED DEFAULT 0 NOT NULL, CHANGE partSocialPoliticalSciences partSocialPoliticalSciences SMALLINT UNSIGNED DEFAULT 0 NOT NULL, CHANGE partInformaticEngineering partInformaticEngineering SMALLINT UNSIGNED DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE player RENAME INDEX name_unique TO UNIQ_98197A655E237E06');
        $this->addSql('ALTER TABLE player RENAME INDEX fkplayercolor TO IDX_98197A65969D9D99');
        $this->addSql('ALTER TABLE player RENAME INDEX fkplayerplayer TO IDX_98197A65A31019E7');
    }

    public function down(Schema $schema): void
    {
		$this->addSql('ALTER TABLE color CHANGE sectors sectors TINYINT(1) DEFAULT 0 NOT NULL, CHANGE regime regime TINYINT(1) NOT NULL, CHANGE electionStatement electionStatement TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE player CHANGE sex sex TINYINT(1) DEFAULT 1 NOT NULL, CHANGE level level TINYINT(1) DEFAULT 0, CHANGE victory victory INT UNSIGNED DEFAULT 0, CHANGE defeat defeat INT UNSIGNED DEFAULT 0, CHANGE statement statement TINYINT(1) DEFAULT 0 NOT NULL, CHANGE stepTutorial stepTutorial TINYINT(1) DEFAULT NULL, CHANGE partNaturalSciences partNaturalSciences TINYINT(1) DEFAULT 0 NOT NULL, CHANGE partLifeSciences partLifeSciences TINYINT(1) DEFAULT 0 NOT NULL, CHANGE partSocialPoliticalSciences partSocialPoliticalSciences TINYINT(1) DEFAULT 0 NOT NULL, CHANGE partInformaticEngineering partInformaticEngineering TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE player RENAME INDEX idx_98197a65a31019e7 TO fkPlayerPlayer');
        $this->addSql('ALTER TABLE player RENAME INDEX uniq_98197a655e237e06 TO name_UNIQUE');
        $this->addSql('ALTER TABLE player RENAME INDEX idx_98197a65969d9d99 TO fkPlayerColor');
    }
}
