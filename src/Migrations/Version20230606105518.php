<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230606105518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sylius_order_item ADD product_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sylius_order_item ADD CONSTRAINT FK_77B587ED4584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id)');
        $this->addSql('CREATE INDEX IDX_77B587ED4584665A ON sylius_order_item (product_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sylius_order_item DROP FOREIGN KEY FK_77B587ED4584665A');
        $this->addSql('DROP INDEX IDX_77B587ED4584665A ON sylius_order_item');
        $this->addSql('ALTER TABLE sylius_order_item DROP product_id');
    }
}
