<?php
declare(strict_types=1);

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
use Migrations\AbstractMigration;

class CreateItems extends AbstractMigration
{
    /**
     * @return void
     */
    public function change()
    {
        $this->table('items')
            ->addColumn('parent_id', 'integer', ['null' => true])
            ->addIndex(['parent_id'], ['name' => 'parent_id'])
            ->addForeignKey(['parent_id'], 'items', ['id'], [
                'constraint' => 'items_parent_id',
            ])
            ->addColumn('lft', 'integer', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('rght', 'integer', [
                'null' => true,
                'default' => null,
            ])
            ->addIndex(['lft'], ['name' => 'lft'])
            ->addColumn('name', 'string', ['null' => false])
            ->create();
    }
}
