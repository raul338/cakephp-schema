<?php
declare(strict_types=1);

namespace Schema\Command;

use Bake\Command\SimpleBakeCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

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
    public function bake(string $name, Arguments $args, ConsoleIo $io): void
    {
        // Hook into the bake process to load our SchemaHelper
        EventManager::instance()->on('Bake.initialize', function (Event $event) {
            /** @var \Cake\View\View $view */
            $view = $event->getSubject();
            $view->loadHelper('Schema.Schema');
        });

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

        parent::bake($name, $args, $io);
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
            $seedData[$tableName] = $data;
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
        $model = $this->findModel($modelName, $useTable);

        $records = $model->find('all')
            ->where($conditions)
            ->enableHydration(false);

        if ($recordCount) {
            $records->limit($recordCount);
        }

        return $records;
    }

    /**
     * Return a Table instance for the given model.
     *
     * @param string $modelName Camelized model name
     * @param string $useTable Table to use
     * @return \Cake\ORM\Table
     */
    public function findModel(string $modelName, string $useTable)
    {
        $options = ['connectionName' => $this->_config['connection']];
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
