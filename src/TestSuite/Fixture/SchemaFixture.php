<?php
declare(strict_types=1);

namespace Schema\TestSuite\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Cake\Utility\Inflector;

/**
 * Base Fixture
 */
class SchemaFixture extends TestFixture
{
    /**
     * Seed file to use for the records
     *
     * @var string
     */
    public $seedFile = CONFIG . DS . 'seed.php';

    /**
     * Schema File to load fixture schema
     *
     * @var string
     */
    public $schemaFile = CONFIG . DS . 'schema.php';

    /**
     * Workaround to use cakephp-schema seed as fixtures
     *
     * @return void
     */
    public function init(): void
    {
        [$namespace, $className] = namespaceSplit(static::class);
        $className = substr($className, 0, strlen('Fixture') * -1);
        $className = Inflector::underscore($className);

        $ret = require $this->schemaFile;
        if (!is_array($ret)) {
            throw new \RuntimeException(sprintf('Schema file `%s` did not return an array.', $this->schemaFile));
        }
        if (array_key_exists($className, $ret['tables'])) {
            $this->fields = $ret['tables'][$className];
        }
        if (file_exists($this->seedFile)) {
            $ret = require $this->seedFile;
            if (!is_array($ret)) {
                throw new \RuntimeException(sprintf('Seed file `%s` did not return an array.', $this->seedFile));
            }
            if (array_key_exists($className, $ret)) {
                $this->records = $ret[$className];
            }
        }

        parent::init();
    }
}
