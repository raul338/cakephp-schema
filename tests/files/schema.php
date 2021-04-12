<?php
/**
 * This file is auto-generated from the current state of the database.
 * This file is a snapshot of your database, to be used as fixture schema for testing.
 *
 * It's strongly recommended that you ignore this file in your version control system.
 */

// @codingStandardsIgnoreStart
return [
    'tables' => [
        'phinxlog' => [
            'version' => ['type' => 'biginteger', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
            'migration_name' => ['type' => 'string', 'length' => 100, 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
            'start_time' => ['type' => 'timestamp', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
            'end_time' => ['type' => 'timestamp', 'length' => null, 'precision' => null, 'null' => true, 'default' => null, 'comment' => ''],
            'breakpoint' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => '0', 'comment' => '', 'precision' => null],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['version'], 'length' => []],
            ],
            '_options' => [
'engine' => 'InnoDB', 'collation' => 'utf8_general_ci'
            ],
        ],
        'profiles' => [
            'id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
            'name' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
            ],
            '_options' => [
'engine' => 'InnoDB', 'collation' => 'utf8_general_ci'
            ],
        ],
        'users' => [
            'id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'autoIncrement' => true, 'precision' => null],
            'profile_id' => ['type' => 'integer', 'length' => null, 'unsigned' => false, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
            'name' => ['type' => 'string', 'length' => 255, 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'comment' => '', 'precision' => null],
            '_indexes' => [
                'profile_id' => ['type' => 'index', 'columns' => ['profile_id'], 'length' => []],
            ],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
                'users_profile_id' => ['type' => 'foreign', 'columns' => ['profile_id'], 'references' => ['profiles', 'id'], 'update' => 'restrict', 'delete' => 'restrict', 'length' => []],
            ],
            '_options' => [
'engine' => 'InnoDB', 'collation' => 'utf8_general_ci'
            ],
        ],

    ],
];
