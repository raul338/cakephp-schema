<?php
declare(strict_types=1);

namespace Schema\Command;

use Bake\Command\SimpleBakeCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Inflector;
use Riimu\Kit\PHPEncoder\PHPEncoder;
use Schema\Helper;

class SeedGenerateCommand extends SimpleBakeCommand
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_config = [
        'connection' => 'default',
        'seed' => 'config/seed.php',
        'path' => 'config/schema.php',
        'count' => false,
        'conditions' => '1=1',
    ];

    /**
     * @var \Schema\Helper
     */
    protected $helper = null;

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'seed';
    }

    /**
     * @inheritDoc
     */
    public function fileName(string $name): string
    {
        return $this->_config['seed'];
    }

    /**
     * @inheritDoc
     */
    public function template(): string
    {
        return 'Schema.config/seed';
    }

    /**
     * @inheritDoc
     */
    public function getPath(Arguments $arguments): string
    {
        return ROOT . DS;
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $this->_config = [
            'connection' => $args->getOption('connection'),
            'seed' => $args->getOption('seed'),
            'path' => $args->getOption('path'),
            'count' => $args->getOption('count'),
            'conditions' => $args->getOption('conditions'),
        ];

        if (!is_string($this->_config['path']) || !file_exists($this->_config['path'])) {
            throw new \InvalidArgumentException(sprintf('Schema file "%s" does not exist.', $this->_config['path']));
        }

        $this->helper = new Helper();

        parent::bake('seed', $args, $io);

        return self::CODE_SUCCESS;
    }

    /**
     * @inheritDoc
     */
    public function templateData(Arguments $arguments): array
    {
        $seedData = [];

        $connection = ConnectionManager::get($this->_config['connection']);
        $tables = $connection->getSchemaCollection()->listTables();
        $excludedTables = Configure::read('Schema.GenerateSeed.excludedTables', []);
        if (!is_array($excludedTables)) {
            throw new \InvalidArgumentException('Schema.GenerateSeed.excludedTables is not an array');
        }

        foreach ($tables as $tableName) {
            if (in_array($tableName, $excludedTables)) {
                continue;
            }
            $model = Inflector::camelize($tableName);
            $data = $this->getRecordsFromTable($model, $tableName)->toArray();
            if (empty($data)) {
                continue;
            }
            $seedData[$tableName] = $this->stringifyRecords($data);
        }

        return [
            'seedData' => $seedData,
        ];
    }

    /**
     * Interact with the user to get a custom SQL condition and use that to extract data
     * to build a fixture.
     *
     * @param string $modelName name of the model to take records from.
     * @param string $useTable Name of table to use.
     * @return \Cake\ORM\Query Array of records.
     */
    public function getRecordsFromTable(string $modelName, string $useTable)
    {
        $recordCount = (filter_var($this->_config['count'], FILTER_VALIDATE_INT) ?? false);
        $conditions = ($this->_config['conditions'] ?? '1=1');
        $model = $this->helper->findModel($this->_config['connection'], $modelName, $useTable);

        $records = $model->find('all')
            ->where($conditions)
            ->enableHydration(false);

        if ($recordCount) {
            $records->limit($recordCount);
        }

        return $records;
    }

    /**
     * Generates the PHP array string for an array of records. Will use
     * var_export() and PHPEncoder for more sophisticated types.
     *
     * @param array<mixed> $records Array of seed records
     * @return string PHP Code
     */
    public function stringifyRecords(array $records)
    {
        $out = "[\n";
        $out = '';
        $encoder = new PHPEncoder();

        foreach ($records as $record) {
            $values = [];
            foreach ($record as $field => $value) {
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d H:i:s');
                }
                if (is_array($value)) {
                    // FIXME: the encoder will forget precisions of floats
                    $val = $encoder->encode($value, [
                        'array.inline' => false,
                        'array.omit' => false,
                        'array.indent' => 4,
                        'boolean.capitalize' => false,
                        'null.capitalize' => false,
                        'string.escape' => false,
                        'array.base' => 12,
                        'float.integers' => 'all',
                        'float.precision' => false,
                    ]);
                } else {
                    $val = var_export($value, true);
                }

                if ($val === 'NULL') {
                    $val = 'null';
                }
                $values[] = "            '$field' => $val";
            }
            $out .= "        [\n";
            $out .= implode(",\n", $values);
            $out .= "\n        ],\n";
        }
        #$out .= "    ]";
        return $out;
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
            ])
            ->addOption('count', [
                'help' => 'Limit recrods to be saved.',
                'default' => 10,
                'short' => 'n',
            ])
            ->addOption('seed', [
                'help' => 'Path to the seed.php file.',
                'short' => 's',
                'default' => 'config/seed.php',
            ])
            ->addOption('conditions', [
                'help' => 'SQL Conditions for the records to be saved',
                'default' => '1=1',
            ]);
    }
}
