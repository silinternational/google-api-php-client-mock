<?php

namespace SilMock\tests\Google\Service;

use PHPUnit\Framework\TestCase;
use SilMock\Google\Service\GoogleFixtures;
use SilMock\DataStore\Sqlite\SqliteUtils;

class GoogleFixturesTest extends TestCase
{
    public $dataFile = DATAFILE3;

    public function testAddFixtures()
    {
        $sqliteClass = new SqliteUtils($this->dataFile);
        $sqliteClass->deleteAllData();

        $fixturesClass = new GoogleFixtures($this->dataFile);

        $fixtures = [
           ['directory', 'user', 'user1 test data'],
           ['directory', 'users_alias', 'users_alias2 test data'],
           ['app_engine', 'webapp', 'webapp3 test data'],
           ['directory', 'user', 'user4 test data'],
        ];

        $fixturesClass->addFixtures($fixtures);

        $expected = [
            [
                'id' => 1,
                'type' => 'directory',
                'class' => 'user',
                'data' => 'user1 test data',
            ],
            [
                'id' => 2,
                'type' => 'directory',
                'class' => 'users_alias',
                'data' => 'users_alias2 test data',
            ],
            [
                'id' => 3,
                'type' => 'app_engine',
                'class' => 'webapp',
                'data' => 'webapp3 test data',
            ],
            [
                'id' => 4,
                'type' => 'directory',
                'class' => 'user',
                'data' => 'user4 test data',
            ],
        ];
        $results = $sqliteClass->getData('', '');

        $msg = " *** Mismatching fixtures arrays";
        self::assertEquals($expected, $results, $msg);
    }

    public function testRemoveAllFixtures()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);

        $fixtures = [
            ['directory', 'user', 'user1 test data'],
        ];

        $fixturesClass->addFixtures($fixtures);
        $fixturesClass->removeAllFixtures();

        $sqliteClass = new SqliteUtils($this->dataFile);
        $results = $sqliteClass->getData('','');
        $expected = [];

        $msg = " *** Mismatching fixtures arrays";
        self::assertEquals($expected, $results, $msg);
    }

} 