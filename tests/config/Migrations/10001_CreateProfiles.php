<?php
declare(strict_types=1);

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
use Migrations\AbstractMigration;

class CreateProfiles extends AbstractMigration
{
    /**
     * @return void
     */
    public function change()
    {
        $this->table('profiles')
            ->addColumn('name', 'string', ['null' => false])
            ->create();
    }
}
