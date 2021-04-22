<?php
declare(strict_types=1);

namespace Schema;

use Cake\Database\Connection;
use Cake\Database\Schema\TableSchema;

/**
 * Custom table object for better manipulation with foreign keys.
 */
class Table extends TableSchema
{
    /**
     * Foreign keys constraints
     *
     * @var array<mixed>
     */
    protected $_foreignKeys = [];

    /**
     * Generate the SQL to create the Table without foreign keys.
     *
     * Uses the connection to access the schema dialect
     * to generate platform specific SQL.
     *
     * @param \Cake\Database\Connection $connection The connection to generate SQL for.
     * @return array<string> List of SQL statements to create the table and the
     *    required indexes.
     */
    public function createSql(Connection $connection): array
    {
        $this->_extractForeignKeys();

        return parent::createSql($connection);
    }

    /**
     * Returns list of ALTER TABLE statements to add foreign key constraints.
     *
     * @return void
     */
    public function restoreForeignKeys()
    {
        foreach ($this->_foreignKeys as $name => $attrs) {
            $this->_constraints[$name] = $attrs;
        }
    }

    /**
     * Refresh the protected foreign keys variable.
     * All foreign keys are removed from the original constraints.
     *
     * @return void
     */
    protected function _extractForeignKeys()
    {
        foreach ($this->_constraints as $name => $attrs) {
            if ($attrs['type'] === static::CONSTRAINT_FOREIGN) {
                $this->_foreignKeys[$name] = $attrs;
                unset($this->_constraints[$name]);
            }
        }
    }
}
