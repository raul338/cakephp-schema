<?php
declare(strict_types=1);

namespace Schema\Test\TestCase\Command;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class SchemaFixtureTest extends TestCase
{
    /**
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * @var string[]
     */
    public $fixtures = [
        'app.Profiles',
        'app.Users',
    ];

    public function testFixturesWorking(): void
    {
        $this->loadFixtures();
        $profiles = TableRegistry::getTableLocator()->get('Profiles');
        $query = $profiles->find();
        $this->assertSame($query->count(), 1, 'Profile not loaded from schema');
        $profile = $query->first();
        $this->assertSame($profile->get('name'), 'admin');
    }
}
