<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Schema\TestSuite\Fixture\SchemaFixture;

class ProfilesFixture extends SchemaFixture
{
    /**
     * @inheritDoc
     */
    public $schemaFile = TESTS . 'files' . DS . 'schema.php';
}
