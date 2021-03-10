<?php
declare(strict_types=1);

namespace Schema;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class Helper
{
    /**
     * Return a Table instance for the given model.
     *
     * @param string $connectionName Conection Name
     * @param string $modelName Camelized model name
     * @param string $useTable Table to use
     * @return \Cake\ORM\Table
     */
    public function findModel(string $connectionName, string $modelName, string $useTable): Table
    {
        $options = ['connectionName' => $connectionName];
        $model = TableRegistry::getTableLocator()->get($modelName, $options);
        // This means we have not found a Table implementation in the app namespace
        // Iterate through loaded plugins and try to find the table
        if (get_class($model) === 'Cake\ORM\Table') {
            foreach (\Cake\Core\Plugin::loaded() as $plugin) {
                $ret = TableRegistry::getTableLocator()->get("{$plugin}.{$modelName}", $options);
                if (get_class($ret) !== 'Cake\ORM\Table') {
                    $model = $ret;
                }
            }
        }
        /*
        if (get_class($model) === 'Cake\ORM\Table') {
            $this->out('Warning: Using Auto-Table for ' . $modelName);
        }
        */

        return $model;
    }
}
