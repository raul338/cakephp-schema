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
        $className = Inflector::tableize($className);

        $file = ROOT . DS . 'config' . DS . 'schema.php';
        if (!file_exists($file)) {
            throw new \RuntimeException('Seed file does not exists. Please run bin/cake schema generateseed');
        }
        $ret = require $file;
        if (!array_key_exists($className, $ret['tables'])) {
            throw new \RuntimeException(sprintf('Unable to retrieve fixture: Table `%s` does not exist in saved schema.', $className));
        }
        $this->fields = $ret['tables'][$className];
        if (file_exists($this->seedFile)) {
            $ret = require $this->seedFile;
            if (array_key_exists($className, $ret)) {
                $this->records = $ret[$className];
            }
        }
        parent::init();
    }
}
