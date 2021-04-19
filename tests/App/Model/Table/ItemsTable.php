<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class ItemsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('items');
        $this->setPrimaryKey('id');
        $this->setDisplayField('name');
        $this->addBehavior('Tree');
    }
}
