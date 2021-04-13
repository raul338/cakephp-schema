<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Schema\TestSuite\Fixture\SchemaFixture;

class UsersFixture extends SchemaFixture
{
    /**
     * @inheritdoc
     */
    public $schemaFile = TESTS . 'files' . DS . 'schema.php';
}
