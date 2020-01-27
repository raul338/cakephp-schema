<?php
namespace Schema\Shell\Task;

use Cake\Console\Shell;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Schema\TableSchema as Schema;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\Filesystem\File;
use Exception;
use Schema\Table;

class SchemaLoadTask extends Shell
{
    /**
     * Default configuration.
     *
     * @var array
     */
    private $_config = [
        'connection' => 'default',
        'path' => 'config/schema.php',
        'no-interaction' => true,
    ];

    /**
     * Save the schema into lock file.
     *
     * @param array $options Set connection name and path to save the schema.php file.
     * @return void
     */
    public function load($options = [])
    {
        $this->_config = array_merge($this->_config, $this->params, $options);

        $path = $this->_config['path'];
        $data = $this->_readSchema($path);

        $this->_loadTables($data['tables']);
    }

    /**
     * Drop all tables in the database.
     *
     * @param array $options Set connection name and path to save the schema.php file.
     * @return void
     */
    public function drop($options = [])
    {
        $this->_config = array_merge($this->_config, $this->params, $options);

        $path = $this->_config['path'];
        $data = $this->_readSchema($path);

        $queries = $this->_generateDropQueries();

        if ($queries === false) {
            $this->_io->err(sprintf('<error>Deletion was terminated by the user.</error>.'), 2);

            return;
        }

        $this->_execute($this->_connection(), $queries);
        $this->_io->out(); // New line
        $this->_io->out(sprintf('<success>%d queries were executed.</success> ', count($queries)));
    }

    /**
     * Drop existing tables and load new tables into database.
     *
     * @param  array $tables List of tables and their fields, indexes, ...
     * @return void
     */
    protected function _loadTables($tables)
    {
        $this->_io->out('Loading the schema from the file ', 0);

        $db = $this->_connection();
        $queries = $this->_generateDropQueries($db);

        if ($queries === false) {
            $this->_io->err(sprintf('<error>Schema was not loaded</error>.'), 2);

            return;
        }

        $tableSchemes = [];

        // Insert tables from the schema.php file
        foreach ($tables as $name => $table) {
            $schema = $this->_schemaFromFields($name, $table);
            $tableSchemes[] = $schema;
            $createSql = $schema->createSql($db);
            $queries = array_merge($queries, $createSql);
        }

        // Add all foreign key constraints
        foreach ($tableSchemes as $table) {
            $foreignKeys = $table->addConstraintSql($db);
            $queries = array_merge($queries, $foreignKeys);
        }

        $this->_execute($db, $queries);

        $this->_io->out(); // New line
        $this->_io->out(sprintf('<success>%d tables were loaded.</success> ', count($tables)));
    }

    /**
     * Returns queries to drop all tables in the database.
     *
     * @param \Cake\Database\Connection $db Connection to run the SQL queries on.
     * @param  bool $ask Ask question before generating queries. If false reply, no query is generated.
     * @return array|false List of SQL statements dropping tables or false if user stopped the deletion.
     */
    protected function _generateDropQueries($db = null, $ask = true)
    {
        if ($db === null) {
            $db = $this->_connection();
        }

        $schemaCollection = $db->getSchemaCollection();
        $tables = $schemaCollection->listTables();

        if (count($tables) > 0 && !$this->_config['no-interaction']) {
            $this->_io->out();
            $this->_io->out(sprintf(
                '<warning>Database is not empty. %d tables will be deleted.</warning>',
                count($tables)
            ));
            $key = $this->_io->askChoice('Do you want to continue?', ['y', 'n'], 'y');
            if ($key === 'n') {
                return false;
            }
        }

        $dropForeignKeys = [];
        $dropTables = [];

        foreach ($tables as $tableName) {
            $this->_io->out('.', 0);
            $table = $schemaCollection->describe($tableName);
            if (!$table instanceof TableSchema) {
                trigger_error("Table $tableName did not return \Cake\Database\Schema\TableSchema", E_USER_WARNING);
                continue;
            }

            $dropKeys = $this->_generateDropForeignKeys($db, $table);
            $dropForeignKeys = array_merge($dropForeignKeys, $dropKeys);

            $dropSql = $table->dropSql($db);
            $dropTables = array_merge($dropTables, $dropSql);
        }

        return array_merge($dropForeignKeys, $dropTables);
    }

    /**
     * Generates SQL statements dropping foreign keys for the table.
     *
     * @param \Cake\Database\Connection $db Connection to run the SQL queries on.
     * @param  \Cake\Database\Schema\TableSchema $table Drop foreign keys for this table.
     * @return array List of SQL statements dropping foreign keys.
     */
    protected function _generateDropForeignKeys($db, Schema $table)
    {
        $type = 'other';
        if ($db->getDriver() instanceof Mysql) {
            $type = 'mysql';
        }

        $queries = [];
        foreach ($table->constraints() as $constraintName) {
            $constraint = $table->getConstraint($constraintName);
            if ($constraint['type'] === Schema::CONSTRAINT_FOREIGN) {
                // TODO: Move this into the driver
                if ($type === 'mysql') {
                    $template = 'ALTER TABLE %s DROP FOREIGN KEY %s';
                } else {
                    $template = 'ALTER TABLE %s DROP CONSTRAINT %s';
                }
                $queries[] = sprintf($template, $table->name(), $constraintName);
            }
        }

        return $queries;
    }

    /**
     * Executes list of quries in one transaction.
     *
     * @param \Cake\Database\Connection $db Connection to run the SQL queries on.
     * @param  array $queries List of SQL statements.
     * @return void
     */
    protected function _execute($db, $queries = null)
    {
        $logQueries = $db->isQueryLoggingEnabled();
        if ($logQueries) {
            $db->enableQueryLogging(false);
        }

        $db->transactional(function ($db) use ($queries) {
            $db->disableForeignKeys();
            foreach ($queries as $query) {
                $this->_io->out('.', 0);
                $db->execute($query)->closeCursor();
            }
            $db->enableForeignKeys();
        });

        if ($logQueries) {
            $db->enableQueryLogging(true);
        }
    }

    /**
     * Returns the database connection.
     *
     * @return \Cake\Database\Connection Object.
     * @throws  \RuntimeException If the connection does not implement schemaCollection()
     */
    protected function _connection()
    {
        $db = ConnectionManager::get($this->_config['connection'], false);
        if (!method_exists($db, 'schemaCollection')) {
            throw new \RuntimeException(
                'Cannot generate fixtures for connections that do not implement schemaCollection()'
            );
        }

        return $db;
    }

    /**
     * Returns the schema array.
     *
     * @param  string $path Path to the schema.php file.
     * @return array Schema array.
     */
    protected function _readSchema($path)
    {
        if (!file_exists($path)) {
            throw new Exception(sprintf('Schema file "%s" does not exist.', $path));
        }

        $return = include $path;
        if (is_array($return)) {
            return $return;
        }

        throw new Exception(sprintf('Schema file "%s" did not return an array.', $path));
    }

    /**
     * Build the fixtures table schema from the fields property.
     *
     * @param  string $tableName Name of the table.
     * @param  array $fields Fields saved into the schema.php file.
     * @return \Cake\Database\Schema\TableSchema Table schema
     */
    protected function _schemaFromFields($tableName, $fields)
    {
        $schema = new Table($tableName);
        foreach ($fields as $field => $data) {
            if ($field === '_constraints' || $field === '_indexes' || $field === '_options') {
                continue;
            }
            $schema->addColumn($field, $data);
        }
        if (!empty($fields['_constraints'])) {
            foreach ($fields['_constraints'] as $name => $data) {
                $schema->addConstraint($name, $data);
            }
        }
        if (!empty($fields['_indexes'])) {
            foreach ($fields['_indexes'] as $name => $data) {
                $schema->addIndex($name, $data);
            }
        }
        if (!empty($fields['_options'])) {
            $schema->setOptions($fields['_options']);
        }

        return $schema;
    }
}
