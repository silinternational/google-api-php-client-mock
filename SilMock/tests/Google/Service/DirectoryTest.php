<?php

namespace SilMock\tests\Google\Service;

use Composer\InstalledVersions;
use PHPUnit\Framework\TestCase;
use Google_Service_Directory_Alias;
use Google_Service_Directory_User;
use SilMock\Google\Service\Directory;
use SilMock\Google\Service\Directory\ObjectUtils;
use SilMock\DataStore\Sqlite\SqliteUtils;
use SilMock\Google\Service\GoogleFixtures;

class DirectoryTest extends TestCase
{
    use SampleUser;

    public $dataFile = DATAFILE2;

    public function getProperties($object, $propKeys = null)
    {
        if ($propKeys === null) {
            $propKeys = [
                "changePasswordAtNextLogin",
                "hashFunction",
                "id",
                "password",
                "primaryEmail",
                "suspended",
                "isEnforcedIn2Sv",
                "isEnrolledIn2Sv",
                "aliases",
            ];
        }

        $outArray = [];

        foreach ($propKeys as $key) {
            $outArray[$key] = $object->$key;
        }

        return $outArray;
    }

    public function testDirectory()
    {
        $expectedKeys = [
            'asps',
            'tokens',
            'users',
            'users_aliases',
            'verificationCodes',
        ];
        $errorMessage = " *** Directory was not initialized properly";
        
        $directory = new Directory('whatever', $this->dataFile);
        
        $directoryAsJson = json_encode($directory);
        $directoryInfo = json_decode($directoryAsJson, true);
        foreach ($expectedKeys as $expectedKey) {
            self::assertArrayHasKey($expectedKey, $directoryInfo, $errorMessage);
            self::assertEmpty($directoryInfo[$expectedKey], $errorMessage);
        }
    }

    public function testUsersInsert()
    {
        $newUser = $this->setupSampleUser($this->dataFile, false);
        $results = $this->getProperties($newUser);

        $expected = [
            "changePasswordAtNextLogin" => false,
            "hashFunction" => "SHA-1",
            "id" => 999991,
            "password" => "testP4ss",
            "primaryEmail" => "user_test1@sil.org",
            "suspended" => false,
            "isEnforcedIn2Sv" => false,
            "isEnrolledIn2Sv" => true,
            "aliases" => null,
        ];
        $msg = " *** Bad returned user";
        self::assertEquals($expected, $results, $msg);

        $sqliteClass = new SqliteUtils($this->dataFile);
        $sqliteData = $sqliteClass->getData('', '');
        $sqliteDataValues = array_values($sqliteData);
        $lastDataEntry = end($sqliteDataValues);
        $dataObj = json_decode($lastDataEntry['data']);
        $results = $this->getProperties($dataObj);

        $expected = array (
            "changePasswordAtNextLogin" => false,
            "hashFunction" => "SHA-1",
            "id" => 999991,
            "password" => "testP4ss",
            "primaryEmail" => "user_test1@sil.org",
            "suspended" => false,
            "isEnforcedIn2Sv" => false,
            "isEnrolledIn2Sv" => true,
            "aliases" => null,
        );

        $msg = " *** Bad data from sqlite database";
        self::assertEquals($expected, $results, $msg);
    }

    public function getGoogleApiClientVersion(): string
    {
        return InstalledVersions::getVersion('google/apiclient');
    }

    public function testUsersInsert_WithAlias()
    {
        $newUser = $this->setupSampleUser($this->dataFile, true);

        $results =  $this->getProperties($newUser);

        $expected = [
            "changePasswordAtNextLogin" => false,
            "hashFunction" => "SHA-1",
            "id" => 999991,
            "password" => "testP4ss",
            "primaryEmail" => "user_test1@sil.org",
            "suspended" => false,
            "isEnforcedIn2Sv" => false,
            "isEnrolledIn2Sv" => true,
        ];
        var_dump($this->getGoogleApiClientVersion());
        if ($this->getGoogleApiClientVersion() === '1.1.9.0') {
            $expected['aliases'] = [
                'aliases' => [
                    // This is an array representing the Google_Service_Directory_Alias object
                    [
                        'alias' => "user_alias1@sil.org",
                        'kind' => 'personal',
                        'primaryEmail' => 'user_test1@sil.org',
                        'etag' => null,
                        'id' => null,
                    ],
                ],
                'etag' => null,
                'kind' => null,
            ];
        } else {
            $expected['aliases'] = [
                // This is an array representing the Google_Service_Directory_Alias object
                [
                    'alias' => "user_alias1@sil.org",
                    'etag' => null,
                    'id' => null,
                    'kind' => 'personal',
                    'primaryEmail' => 'user_test1@sil.org',
                ]
            ];
        }

        $msg = " *** Bad returned user";
        self::assertEquals($expected, $results, $msg);

        // The last entry is an alias entry.
        $sqliteClass = new SqliteUtils($this->dataFile);
        $sqliteData = $sqliteClass->getData('', '');
        $sqliteDataValues = array_values($sqliteData);
        $lastDataEntry = end($sqliteDataValues);
        $lastAliases = json_decode($lastDataEntry['data'], true);

        if ($this->getGoogleApiClientVersion() === '1.1.9.0') {
            $results = $lastAliases['aliases']['aliases'][0];
        } else {
            $results = $lastAliases['alias'];
        }

        $expected = [
            "alias" => "user_alias1@sil.org",
            "kind" => "personal",
            "primaryEmail" => "user_test1@sil.org",
            'etag' => null,
            'id' => null,
        ];

        $msg = " *** Bad data from sqlite database";
        self::assertEquals($expected, $results, $msg);
    }

    public function getFixtures()
    {
        $user4Data = '{"changePasswordAtNextLogin":false,' .
            '"hashFunction":"SHA-1",' .
            '"id":"999991","password":"testP4ss",' .
            '"primaryEmail":"user_test4@sil.org",' .
            '"isEnforcedIn2Sv":false,' .
            '"isEnrolledIn2Sv":true,' .
            '"suspended":false,"suspensionReason":null}';

        $alias2 = new Google_Service_Directory_Alias();
        $alias2->setAlias("users_alias2@sil.org");
        $alias2->setPrimaryEmail("user_test1@sil.org");

        $alias6 = new Google_Service_Directory_Alias();
        $alias6->setAlias("users_alias6@sil.org");
        $alias6->setId("1");

        $fixtures = [
            [
                'directory',
                'user',
                '{"primaryEmail":"user_test1@sil.org","id":"999990"}'
            ],
            ['directory', 'users_alias', json_encode($alias2)],
            ['app_engine', 'webapp', 'webapp3 test data'],
            ['directory', 'user', $user4Data],
            ['directory', 'user', 'user5 test data'],
            ['directory', 'users_alias', json_encode($alias6)],
        ];

        return $fixtures;
    }

    public function getAliasFixture($alias, $email, ?string $id)
    {
        $newAlias = new Google_Service_Directory_Alias();
        $newAlias->setAlias($alias);
        if ($email) {
            $newAlias->setPrimaryEmail($email);
        }

        if (! empty($id)) {
            $newAlias->setId($id);
        }

        return $newAlias;
    }

    public function testUsersGet()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $primaryEmail = 'user_test4@sil.org';

        $userData = [
            "changePasswordAtNextLogin" => false,
            "hashFunction" => "SHA-1",
            "id" => 999991,
            "password" => "testP4ss",
            "primaryEmail" => $primaryEmail,
            "suspended" => false,
            "isEnforcedIn2Sv" => false,
            "isEnrolledIn2Sv" => true,
            "aliases" => null,
        ];

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);

        $newDir = new Directory('anyclient', $this->dataFile);

        $newUser = $newDir->users->get($primaryEmail);
        $results = $this->getProperties($newUser);
        $expected = $userData;
        $msg = " *** Bad user data returned";
        self::assertEquals($expected, $results, $msg);
    }

    public function testUsersGet_ById()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $userId = '999991';

        $userData = [
            "changePasswordAtNextLogin" => false,
            "hashFunction" => "SHA-1",
            "id" => $userId,
            "password" => "testP4ss",
            "primaryEmail" => "user_test4@sil.org",
            "suspended" => false,
            "isEnforcedIn2Sv" => false,
            "isEnrolledIn2Sv" => true,
            "aliases" => null,
        ];

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);

        $newDir = new Directory('anyclient', $this->dataFile);
        $newUser = $newDir->users->get($userId);

        $results = $this->getProperties($newUser);
        $expected = $userData;
        $msg = " *** Bad user data returned";
        self::assertEquals($expected, $results, $msg);
    }


    public function testUsersGet_Aliases()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();
        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);

        $userId = '999991';
        $email = "user_test4@sil.org";

        $userData = [
            "changePasswordAtNextLogin" => false,
            "hashFunction" => "SHA-1",
            "id" => $userId,
            "password" => "testP4ss",
            "primaryEmail" => $email,
            "suspended" => false,
            "isEnforcedIn2Sv" => false,
            "isEnrolledIn2Sv" => true,
            "aliases" => ["users_alias1A@sil.org", "users_alias1B@sil.org"],
        ];


        $aliasA = $this->getAliasFixture("users_alias1A@sil.org", $email, null);
        $aliasB = $this->getAliasFixture("users_alias1B@sil.org", $email, null);

        $newFixtures = [
            ['directory', 'users_alias', json_encode($aliasA)],
            ['directory', 'users_alias', json_encode($aliasB)],
        ];
        $fixturesClass->addFixtures($newFixtures);

        $newDir = new Directory('anyclient', $this->dataFile);
        $newUser = $newDir->users->get($email);
        $results = $this->getProperties($newUser);

        $expected = $userData;
        $msg = " *** Bad user data returned";
        self::assertEquals($expected, $results, $msg);
    }

    public function testUsersGet_ByAlias()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();
        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);
        
        $email = "user_test4@sil.org";
        
        $aliasA = $this->getAliasFixture("users_alias1A@sil.org", $email, null);
        $aliasB = $this->getAliasFixture("users_alias1B@sil.org", $email, null);
        
        $newFixtures = [
            ['directory', 'users_alias', json_encode($aliasA)],
            ['directory', 'users_alias', json_encode($aliasB)],
        ];
        $fixturesClass->addFixtures($newFixtures);
        
        $newDir = new Directory('anyclient', $this->dataFile);
        $newUser = $newDir->users->get('users_alias1A@sil.org');
        
        self::assertNotNull(
            $newUser,
            'Failed to get user by an alias'
        );
        self::assertEquals(
            $email,
            $newUser['primaryEmail'],
            'Failed to get correct user by an alias'
        );
    }
    
    public function testUsersUpdate()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $primaryEmail = "user_test4@sil.org";

        $userData = [
            "changePasswordAtNextLogin" => false,
            "hashFunction" => "SHA-1",
            "id" => 999991,
            "password" => "testP4ss",
            "primaryEmail" => $primaryEmail,
            "suspended" => false,
            "isEnforcedIn2Sv" => false,
            "isEnrolledIn2Sv" => true,
            "aliases" => [],
        ];

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);

        $newUser = new Google_Service_Directory_User();
        ObjectUtils::initialize($newUser, $userData);

        $newDir = new Directory('anyclient', $this->dataFile);
        $newDir->users->update($primaryEmail, $newUser);

        $newUser = $newDir->users->get($primaryEmail);
        $results = $this->getProperties($newUser);
        $expected = $userData;
        $msg = " *** Bad user data returned";
        self::assertEquals($expected, $results, $msg);
    }


    public function testUsersUpdate_ById()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $userId = '999991';

        $userData = [
            "changePasswordAtNextLogin" => false,
            "hashFunction" => "SHA-1",
            "id" => $userId,
            "password" => "testP4ss",
            "primaryEmail" => "user_test4@sil.org",
            "suspended" => false,
            "isEnforcedIn2Sv" => false,
            "isEnrolledIn2Sv" => true,
            "aliases" => [],
        ];

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);

        $newUser = new Google_Service_Directory_User();
        ObjectUtils::initialize($newUser, $userData);

        $newDir = new Directory('anyclient', $this->dataFile);
        $newDir->users->update($userId, $newUser);
        $newUser = $newDir->users->get($userId);

        $results = $this->getProperties($newUser);
        $expected = $userData;
        $msg = " *** Bad user data returned";
        self::assertEquals($expected, $results, $msg);
    }

    public function testUsersUpdate_WithAlias()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $primaryEmail = "user_test4@sil.org";

        $userData = [
            "changePasswordAtNextLogin" => false,
            "hashFunction" => "SHA-1",
            "id" => 999991,
            "password" => "testP4ss",
            "primaryEmail" => $primaryEmail,
            "suspended" => false,
            "isEnrolledIn2Sv" => true,
            "isEnforcedIn2Sv" => false,
            "aliases" => ['user_alias4B@sil.org'],
        ];

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);

        $newUser = new Google_Service_Directory_User();
        ObjectUtils::initialize($newUser, $userData);

        $newDir = new Directory('anyclient', $this->dataFile);
        $newDir->users->update($primaryEmail, $newUser);
        $newUser = $newDir->users->get($primaryEmail);

        $results = $this->getProperties($newUser);
        $expected = $userData;
        $msg = " *** Bad user data returned";
        self::assertEquals($expected, $results, $msg);
    }

    public function testUsersUpdate_WithDifferentAliases()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $primaryEmail = "user_test4@sil.org";

        $aliasFixture = $this->getAliasFixture(
            "users_alias4B@sil.org",
            $primaryEmail,
            null
        );
        $newFixtures = [
            ['directory', 'users_alias', json_encode($aliasFixture)],
        ];
        $fixturesClass->addFixtures($newFixtures);

        // Different aliases
        $userData = [
            "changePasswordAtNextLogin" => false,
            "hashFunction" => "SHA-1",
            "id" => 999991,
            "password" => "testP4ss",
            "primaryEmail" => $primaryEmail,
            "suspended" => false,
            "isEnrolledIn2Sv" => true,
            "isEnforcedIn2Sv" => false,
            "aliases" => ['user_alias4C@sil.org', 'user_alias4D@sil.org'],
        ];

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);
        $fixturesClass->addFixtures($fixtures);

        $newUser = new Google_Service_Directory_User();
        ObjectUtils::initialize($newUser, $userData);

        $newDir = new Directory('anyclient', $this->dataFile);
        $newDir->users->update($primaryEmail, $newUser);
        $newUser = $newDir->users->get($primaryEmail);

        $results = $this->getProperties($newUser);
        $expected = $userData;

        $msg = " *** Bad user data returned";
        self::assertEquals($expected, $results, $msg);
    }

    public function testUsersUpdate_NotThere()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $userId = 999999;

        $userData = [
            "changePasswordAtNextLogin" => false,
            "hashFunction" => "SHA-1",
            "id" => $userId,
            "password" => "testP4ss",
            "primaryEmail" => "user_test4@sil.org",
            "suspended" => false,
        ];

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);

        $newUser = new Google_Service_Directory_User();
        ObjectUtils::initialize($newUser, $userData);

        $newDir = new Directory('anyclient', $this->dataFile);
        
        $this->expectExceptionCode(201407101130);
        $newDir->users->update($userId, $newUser);
        // the assert is in the doc comment
    }

    public function testUsersDelete()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $primaryEmail = "user_test4@sil.org";

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);

        $newDir = new Directory('anyclient', $this->dataFile);
        $newDir->users->delete($primaryEmail);

        $sqliteClass = new SqliteUtils($this->dataFile);
        $results = $sqliteClass->getData('', '');

        $expected = [
            [
                'id' => 1,
                'type' => 'directory',
                'class' => 'user',
                'data' => '{"primaryEmail":"user_test1@sil.org","id":"999990"}'
            ],
            [
                'id' => 2,
                'type' => 'directory',
                'class' => 'users_alias',
                'data' => '{"alias":"users_alias2@sil.org","etag":null,'
                    . '"id":null,"kind":null,'
                    . '"primaryEmail":"user_test1@sil.org"}'
            ],
            [
                'id' => 3,
                'type' => 'app_engine',
                'class' => 'webapp',
                'data' => 'webapp3 test data'
            ],
            [
                'id' => 5,
                'type' => 'directory',
                'class' => 'user',
                'data' => 'user5 test data'
            ],
            [
                'id' => 6,
                'type' => 'directory',
                'class' => 'users_alias',
                'data' => '{"alias":"users_alias6@sil.org","etag":null,'
                    . '"id":"1","kind":null,"primaryEmail":null}'
            ],
        ];

        $msg = " *** Bad database data returned";
        self::assertEquals($expected, $results, $msg);
    }

    public function testUsersDelete_ById()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $userId = 999991;

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);

        $newDir = new Directory('anyclient', $this->dataFile);
        $newDir->users->delete($userId);

        $sqliteClass = new SqliteUtils($this->dataFile);
        $results = $sqliteClass->getData('', '');

        $expected = [
            [
                'id' => 1,
                'type' => 'directory',
                'class' => 'user',
                'data' => '{"primaryEmail":"user_test1@sil.org","id":"999990"}',
            ],
            [
                'id' => 2,
                'type' => 'directory',
                'class' => 'users_alias',
                'data' => '{"alias":"users_alias2@sil.org","etag":null,'
                    . '"id":null,"kind":null,'
                    . '"primaryEmail":"user_test1@sil.org"}',
            ],
            [
                'id' => 3,
                'type' => 'app_engine',
                'class' => 'webapp',
                'data' => 'webapp3 test data'
            ],
            [
                'id' => 5,
                'type' => 'directory',
                'class' => 'user',
                'data' => 'user5 test data'
            ],
            [
                'id' => 6,
                'type' => 'directory',
                'class' => 'users_alias',
                'data' => '{"alias":"users_alias6@sil.org","etag":null,'
                    . '"id":"1","kind":null,"primaryEmail":null}',
            ],
        ];

        $msg = " *** Bad database data returned";
        self::assertEquals($expected, $results, $msg);
    }


    public function testUsersAliasesInsert()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);

        $newAlias = new Google_Service_Directory_Alias();
        $newAlias->alias = "users_alias1@sil.org";
        $newAlias->kind = "personal";

        $newDir = new Directory('anyclient', $this->dataFile);
        $newAlias = $newDir->users_aliases->insert("user_test1@sil.org", $newAlias);

        $results = json_encode($newAlias);
        $expected = '{"alias":"users_alias1@sil.org","etag":null,"id":null,' .
                    '"kind":"personal","primaryEmail":"user_test1@sil.org"}'
        ;
        $msg = " *** Bad returned alias";
        self::assertEquals($expected, $results, $msg);


        $sqliteClass = new SqliteUtils($this->dataFile);
        $sqliteData = $sqliteClass->getData('', '');
        $sqliteDataValues = array_values($sqliteData);
        $lastDataEntry = end($sqliteDataValues);
        $results = $lastDataEntry['data'];

        $msg = " *** Bad data from sqlite database";
        self::assertEquals($expected, $results, $msg);
    }

    public function testUsersAliasesInsert_UserNotThere()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);

        $newAlias = new Google_Service_Directory_Alias();
        $newAlias->alias = "users_alias1@sil.org";
        $newAlias->kind = "personal";

        $newDir = new Directory('anyclient', $this->dataFile);
        
        $this->expectExceptionCode(201407110830);
        $newAlias = $newDir->users_aliases->insert("no_user@sil.org", $newAlias);
    }

    public function testUsersAliasesListUsersAliases_Email()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);

        $aliasFixture = $this->getAliasFixture(
            "users_alias7@sil.org",
            "user_test1@sil.org",
            "1"
        );

        $newFixtures = [
            ['directory', 'users_alias', json_encode($aliasFixture)],
        ];
        $fixturesClass->addFixtures($newFixtures);

        $newDir = new Directory('anyclient', $this->dataFile);
        $aliases = $newDir->users_aliases->listUsersAliases("user_test1@sil.org");

        $results = [];
        foreach ($aliases['aliases'] as $nextAlias) {
            $results[] = json_encode($nextAlias);
        }

        $expected = [
            '{"alias":"users_alias2@sil.org","etag":null,"id":null,' .
              '"kind":null,"primaryEmail":"user_test1@sil.org"}',
            '{"alias":"users_alias7@sil.org","etag":null,"id":"1",' .
              '"kind":null,"primaryEmail":"user_test1@sil.org"}'
        ];

        $msg = " *** Bad returned Aliases";
        self::assertEquals($expected, $results, $msg);
    }

    public function testUsersAliasesListUsersAliases_ID()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);
        $email = "user_test7@sil.org";

        $aliasB = $this->getAliasFixture("users_alias7b@sil.org", $email, "7");
        $aliasC = $this->getAliasFixture("users_alias7c@sil.org", null, "7");

        $newFixtures = [
            [
                'directory',
                'user',
                '{"id":"7","primaryEmail":"' . $email . '","aliases":[]}'
            ],
            ['directory', 'users_alias', json_encode($aliasB)],
            ['directory', 'users_alias', json_encode($aliasC)],
        ];
        $fixturesClass->addFixtures($newFixtures);

        $newDir = new Directory('anyclient', $this->dataFile);
        $aliases = $newDir->users_aliases->listUsersAliases("7");

        $results = [];
        foreach ($aliases['aliases'] as $nextAlias) {
            $results[] = json_encode($nextAlias);
        }

        $expected = [
            '{"alias":"users_alias7b@sil.org","etag":null,"id":"7","kind":null,' .
               '"primaryEmail":"user_test7@sil.org"}',
            '{"alias":"users_alias7c@sil.org","etag":null,"id":"7","kind":null,' .
               '"primaryEmail":null}',
        ];

        $msg = " *** Bad returned Aliases";
        self::assertEquals($expected, $results, $msg);
    }

    public function testUsersAliasesListUsersAliases_Structure()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);
        $email = "user_test1@sil.org";

        $alias = $this->getAliasFixture("users_alias7@sil.org", $email, "1");
        $newFixtures = [
            ['directory', 'users_alias', json_encode($alias)],
        ];

        $fixturesClass->addFixtures($newFixtures);

        $newDir = new Directory('anyclient', $this->dataFile);
        $aliases = $newDir->users_aliases->listUsersAliases("user_test1@sil.org");

        $results = isset($aliases['aliases']);
        self::assertTrue($results, ' *** The aliases property is not accessible');

        $results = is_array($aliases['aliases']);
        self::assertTrue($results, ' *** The aliases property is not an array');

        $user_aliases = [];

        foreach ($aliases['aliases'] as $alias) {
            $user_aliases[] = $alias['alias'];
        }

        $results = $user_aliases;
        $expected = ["users_alias2@sil.org", "users_alias7@sil.org"];
        $msg = " *** Bad returned Aliases";
        self::assertEquals($expected, $results, $msg);
    }

    public function testUsersAliasesListUsersAliases_UserNotThere()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);
        $alias = $this->getAliasFixture(
            "users_alias7@sil.org",
            "user_test1@sil.org",
            "1"
        );
        $newFixtures = [
            ['directory', 'users_alias', json_encode($alias)],
        ];
        $fixturesClass->addFixtures($newFixtures);

        $newDir = new Directory('anyclient', $this->dataFile);
        
        $this->expectExceptionCode(201407101420);
        $aliases = $newDir->users_aliases->listUsersAliases("no_user@sil.org");
    }

    public function testUsersAliasesDelete()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();

        $fixtures = $this->getFixtures();
        $fixturesClass->addFixtures($fixtures);
        $email = "user_test1@sil.org";

        $alias = $this->getAliasFixture("users_alias7@sil.org", $email, "1");

        $newFixtures = [
            ['directory', 'users_alias', json_encode($alias)],
        ];
        $fixturesClass->addFixtures($newFixtures);

        $newDir = new Directory('anyclient', $this->dataFile);
        $results = $newDir->users_aliases->delete(
            "user_test1@sil.org",
            "users_alias2@sil.org"
        );

        self::assertTrue($results, " *** Didn't appear to delete the alias.");

        $sqliteUtils = new SqliteUtils($this->dataFile);
        $results = $sqliteUtils->getData('directory', 'users_alias');

        $expected = [
            [
                'id' => '6',
                'type' => 'directory',
                'class' => 'users_alias',
                'data' => '{"alias":"users_alias6@sil.org","etag":null,'
                    . '"id":"1","kind":null,"primaryEmail":null}',
            ],
            [
                'id' => '7',
                'type' => 'directory',
                'class' => 'users_alias',
                'data' => '{"alias":"users_alias7@sil.org","etag":null,'
                    . '"id":"1","kind":null,"primaryEmail":"' . $email . '"}'
            ],
        ];
        $msg = " *** Mismatching users_aliases in db";
        self::assertEquals($expected, $results, $msg);
    }

    public function testUserArrayAccess()
    {
        $user = new Google_Service_Directory_User();
        $user->suspended = false;

        self::assertFalse($user->suspended, ' *** class access failed');
        self::assertFalse($user['suspended'], ' *** array access failed');
    }

    public function testUserClassAccess()
    {
        $user = new Google_Service_Directory_User();
        $user['suspended'] = false;

        self::assertFalse($user->suspended, ' *** class access failed');
        self::assertFalse($user['suspended'], ' *** array access failed');
    }

    public function testAliasArrayAccess()
    {
        $alias = new Google_Service_Directory_Alias();
        $email = 'user_test@sil.org';
        $alias->primaryEmail = $email;

        self::assertEquals($email, $alias->primaryEmail, ' *** class access failed');
        self::assertEquals($email, $alias['primaryEmail'], ' *** array access failed');
    }

    public function testAliasClassAccess()
    {
        $alias = new Google_Service_Directory_Alias();
        $email = 'user_test@sil.org';
        $alias['primaryEmail'] = $email;

        self::assertEquals($email, $alias->primaryEmail, ' *** class access failed');
        self::assertEquals($email, $alias['primaryEmail'], ' *** array access failed');
    }
}
