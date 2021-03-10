<?php
declare(strict_types=1);

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
use Migrations\AbstractMigration;

class CreateUsers extends AbstractMigration
{
    /**
     * @return void
     */
    public function change()
    {
        $this->table('users')
            ->addColumn('profile_id', 'integer', ['null' => false])
            ->addIndex(['profile_id'], ['name' => 'profile_id'])
            ->addForeignKey(['profile_id'], 'profiles', ['id'], [
                'constraint' => 'users_profile_id',
            ])
            ->addColumn('name', 'string', ['null' => false])
            ->create();
    }
}
