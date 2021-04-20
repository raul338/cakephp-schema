<?php
declare(strict_types=1);

namespace Schema\Command;

use Bake\Command\SimpleBakeCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\ConnectionManager;

class SchemaSaveCommand extends SimpleBakeCommand
{
    /**
     * Default configuration.
     *
     * @var array<mixed>
     */
    private $_config = [
        'path' => 'config/schema.php',
    ];

    /**
     * @var array<string,\Cake\Database\Schema\TableSchema|\Cake\Database\Schema\TableSchemaInterface>
     */
    private $tables;

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'schema';
    }

    /**
     * @inheritDoc
     */
    public function fileName($name): string
    {
        return $this->_config['path'];
    }

    /**
     * @inheritDoc
     */
    public function template(): string
    {
        return 'Schema.config/schema';
    }

    /**
     * @inheritDoc
     */
    public function getPath(Arguments $args): string
    {
        return ROOT . DS;
    }

    /**
     * @inheritDoc
     */
    public function templateData(Arguments $args): array
    {
        $tables = '';

        foreach ($this->tables as $name => $table) {
            $schema = $this->_generateSchema($table);
            $tables .= "        '$name' => $schema,\n";
        }

        return [
            'tables' => $tables,
        ];
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->_config = [
            'path' => $args->getOption('path') ?: 'config/schema.php',
        ];
        $this->tables = $this->_describeTables($args, $io);

        $filePath = $this->getPath($args) . $this->fileName('schema');
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        parent::bake('schema', $args, $io);

        return self::CODE_SUCCESS;
    }

    /**
     * Returns list of all tables and their Schema objects.
     *
     * @param \Cake\Console\Arguments $args Command Arguments
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return array List of tables schema indexed by table name.
     */
    protected function _describeTables(Arguments $args, ConsoleIo $io)
    {
        $connectionName = $args->getOption('connection');
        if (!is_string($connectionName)) {
            throw new \InvalidArgumentException('connection option must be an string');
        }
        $io->out(sprintf(
            'Reading the schema from the `%s` database ',
            $connectionName
        ), 0);

        $connection = ConnectionManager::get($connectionName, false);
        $schemaCollection = $connection->getSchemaCollection();
        $tables = $schemaCollection->listTables();

        $data = [];
        foreach ($tables as $table) {
            $io->out('.', 0);
            $data[$table] = $schemaCollection->describe($table);
        }

        $io->out(); // New line

        return $data;
    }

    /**
     * Generates a string representation of a schema.
     *
     * @param \Cake\Database\Schema\TableSchemaInterface $table Table schema.
     * @return string fields definitions
     */
    protected function _generateSchema(TableSchemaInterface $table)
    {
        $cols = $indexes = $constraints = [];
        foreach ($table->columns() as $field) {
            $fieldData = $table->getColumn($field);
            $properties = implode(', ', $this->_values($fieldData));
            $cols[] = "            '$field' => [$properties],";
        }
        foreach ($table->indexes() as $index) {
            $fieldData = $table->getIndex($index);
            $properties = implode(', ', $this->_values($fieldData));
            $indexes[] = "                '$index' => [$properties],";
        }
        foreach ($table->constraints() as $index) {
            $fieldData = $table->getConstraint($index);
            $properties = implode(', ', $this->_values($fieldData));
            $constraints[] = "                '$index' => [$properties],";
        }
        $options = $this->_values($table->getOptions());

        $content = implode("\n", $cols) . "\n";
        if (!empty($indexes)) {
            $content .= "            '_indexes' => [\n" . implode("\n", $indexes) . "\n            ],\n";
        }
        if (!empty($constraints)) {
            $content .= "            '_constraints' => [\n" . implode("\n", $constraints) . "\n            ],\n";
        }
        if (!empty($options)) {
            $content .= "            '_options' => [\n" . implode(', ', $options) . "\n            ],\n";
        }

        return "[\n$content        ]";
    }

    /**
     * Formats Schema columns from Model Object
     *
     * @param array|null $values Options keys(type, null, default, key, length, extra).
     * @return array Formatted values
     */
    protected function _values($values = null)
    {
        $vals = [];
        if (!is_array($values)) {
            return $vals;
        }
        foreach ($values as $key => $val) {
            if (is_array($val)) {
                $vals[] = "'{$key}' => [" . implode(', ', $this->_values($val)) . ']';
            } else {
                $val = var_export($val, true);
                if ($val === 'NULL') {
                    $val = 'null';
                }
                if (!is_numeric($key)) {
                    $vals[] = "'{$key}' => {$val}";
                } else {
                    $vals[] = "{$val}";
                }
            }
        }

        return $vals;
    }

    /**
     * @inheritDoc
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->addOption('connection', [
                'default' => 'default',
                'short' => 'c',
            ])
            ->addOption('path', [
                'default' => 'config/schema.php',
            ]);
    }
}
