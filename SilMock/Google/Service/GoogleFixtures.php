<?php
namespace SilMock\Google\Service;

use SilMock\DataStore\Sqlite\SqliteUtils;

class GoogleFixtures {

    /**
     * The SQLite database path and file.
     * @var string
     */
    private $dbfile;

    /**
     * @param string $dbFile path and filename of the database for Mock Google
     */
    public function __construct($dbFile=null)
    {
        $this->dbfile = $dbFile;
    }

    /**
     * Adds data to the database based on the input
     *
     * @param array of arrays $fixtures ...
     *    Each array should have 3 string elements ...
     *    - the type of Google Service (e.g. "directory")
     *    - the class (e.g. "user")
     *    - the corresponding json formatted data
     */
    public function addFixtures($fixtures)
    {
        $newSqlite = new SqliteUtils($this->dbfile);

        foreach ($fixtures as $nextFixture) {
            $type = $nextFixture[0];
            $class = $nextFixture[1];
            $data = $nextFixture[2];
            $newSqlite->recordData($type, $class, $data);
        }
    }

    /**
     *  Empties out the database table completely
     */
    public function removeAllFixtures()
    {
        $newSqlite = new SqliteUtils($this->dbfile);
        $newSqlite->deleteAllData();
    }

}