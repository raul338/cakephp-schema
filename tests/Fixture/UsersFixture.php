<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Schema\TestSuite\Fixture\SchemaFixture;

class UsersFixture extends SchemaFixture
{
    public $seedFile = TESTS . 'files' . DS . 'seed.php';
}
