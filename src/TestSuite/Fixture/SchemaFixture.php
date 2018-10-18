<?php
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
    public $seedFile = ROOT . DS . 'config' . DS . 'seed.php';

    /**
     * Workaround to use cakephp-schema seed as fixtures
     * @return void
     */
    public function init()
    {
        list($namespace, $className) = namespaceSplit(get_called_class());
        $className = substr($className, 0, strlen('Fixture') * -1);
        $className = Inflector::underscore($className);

        $ret = require ROOT . DS . 'config' . DS . 'schema.php';
        if (array_key_exists($className, $ret['tables'])) {
            $this->fields = $ret['tables'][$className];
        }
        if (file_exists($this->seedFile)) {
            $ret = require $this->seedFile;
            if (array_key_exists($className, $ret)) {
                $this->records = $ret[$className];
            }
        }
        parent::init();
    }
}
