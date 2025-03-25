<?php

namespace SilMock\DataStore\Sqlite;

use Exception;
use PDO;
use SilMock\exceptions\SqliteUtilsBadDataClassException;
use SilMock\exceptions\SqliteUtilsBadDataTypeException;
use SilMock\exceptions\SqliteUtilsSqlAffectedNoRowsException;
use SilMock\exceptions\SqliteUtilsSqlFailedException;

class SqliteUtils
{
    /**
     * The PDO connection to the database (or null if uninitialized).
     */
    private ?PDO $db = null;

    /**
     * The SQLite database file (path with file name).
     */
    private string $dbFile;

    private string $dbTable = 'google_service';

    /**
     * If needed, this creates the sqlite database and/or its structure
     *
     * @param string|null $dbFile path and filename of the database for Mock Google
     */
    public function __construct(?string $dbFile = null)
    {
        // default database path
        $this->dbFile = __DIR__ . '/Google_Service_Data.db';

        // if database path given, use it instead
        if ($dbFile) {
            $this->dbFile = $dbFile;
        }

        $this->createDbStructureAsNecessary();
    }

    /**
     * A utility function to get the Google Mock data out of the data
     *     file using json_decode for a particular Google class
     *
     * @param string $dataType -- the name of a Google mock service (e.g. 'directory')
     * @param string $dataClass -- the name of a Google mock class (e.g. 'users_alias')
     * @return array|null -- null if exception or if no data for that class name,
     *        otherwise the json_decode version of the Google mock data.
     *        If $dataType and $dataClass are not strings, returns everything
     *        from the data table.
     */
    public function getData(string $dataType, string $dataClass): ?array
    {
        if (! file_exists($this->dbFile)) {
            return null;
        }

        $whereClause = '';
        $whereArray = array();

        if (! empty($dataType)) {
            $whereClause = " WHERE type = :type";
            $whereArray[':type'] = $dataType;

            if (! empty($dataClass)) {
                $whereClause .= " AND class = :class";
                $whereArray[':class'] = $dataClass;
            }
        }

        if (! $whereClause) {
            return $this->runSql(
                "SELECT * FROM " . $this->dbTable,
                array(),
                false,
                true
            );
        }

        return $this->runSql(
            "SELECT * FROM " . $this->dbTable  . $whereClause,
            $whereArray,
            false,
            true
        );
    }

    /**
     * Finds and returns the first record in the database that matches the input values.
     *
     * @param string $dataType (e.g. "directory")
     * @param string $dataClass (e.g. "user")
     * @param string $dataKey  (e.g. "primaryEmail" or "id")
     * @param string|int $dataValue
     * @return array|null -- array for the matching database entry, null otherwise
     */
    public function getRecordByDataKey(string $dataType, string $dataClass, string $dataKey, $dataValue): ?array
    {
        $allOfClass = $this->getData($dataType, $dataClass);

        foreach ($allOfClass as $nextEntry) {
            $nextData = json_decode($nextEntry['data'], true);
            $nextDataValue = $nextData[$dataKey] ?? null;
            if ($nextDataValue === $dataValue) {
                return $nextEntry;
            }
        }

        return null;
    }

    /**
     * Finds and returns all records in the database that matches the input values.
     *
     * @param string $dataType (e.g. "directory")
     * @param string $dataClass (e.g. "user")
     * @param string $dataKey  (e.g. "primaryEmail" or "id")
     * @param string|int $dataValue
     * @return array -- an array for the matching database entry
     */
    public function getAllRecordsByDataKey(string $dataType, string $dataClass, string $dataKey, $dataValue): array
    {
        $allOfClass = $this->getData($dataType, $dataClass);

        $foundEntries = array();

        foreach ($allOfClass as $nextEntry) {
            $nextData = json_decode($nextEntry['data'], true);
            $nextDataValue = $nextData[$dataKey] ?? null;
            if ($nextDataValue === $dataValue) {
                $foundEntries[] = $nextEntry;
            }
        }

        return $foundEntries;
    }


    /**
     * Deletes the database record whose "id" field matches the input value
     *
     * @param int $recordId
     */
    public function deleteRecordById(int $recordId): void
    {
        $this->runSql(
            "DELETE FROM " .  $this->dbTable . " WHERE id = :id",
            [':id' => $recordId],
            true
        );
    }

    /**
     * A utility function to delete the Google Mock data based on a
     * particular data type and data class for a specific email.
     *
     * @param string $dataType -- the name of a Google mock service (e.g. 'directory')
     * @param string $dataClass -- the name of a Google mock class (e.g. 'users_alias')
     *        If empty, then all the matching $dataType records
     *          for the $emailAddress are deleted.
     *        If $dataType and $dataClass are empty strings, nothing is deleted.
     * @param string $emailAddress -- the primary email address.
     * @return void
     */
    public function deleteDataByEmail(string $dataType, string $dataClass, string $emailAddress): void
    {
        if (
            ! file_exists($this->dbFile)
            || empty($dataType)
            || empty($emailAddress)
        ) {
            return;
        }

        $matchingRecords = $this->getAllRecordsByDataKey($dataType, $dataClass, 'primaryEmail', $emailAddress);
        foreach ($matchingRecords as $matchingRecord) {
            $id = $matchingRecord['id'];
            $this->deleteRecordById($id);
        }
    }

    /**
     * Updates the "data" field of the database record whose id field matches
     *     the input value
     * @param int $recordId
     * @param string $newData
     * @return void
     */
    public function updateRecordById(int $recordId, string $newData): void
    {
        $this->runSql(
            "UPDATE " .  $this->dbTable . " SET data = :data WHERE id = :id",
            [':id' => $recordId, ':data' => $newData],
            true
        );
    }

    /**
     * Deletes all records from the database table
     *
     * @return void
     */
    public function deleteAllData(): void
    {
        $this->runSql(
            "DELETE FROM " . $this->dbTable . " WHERE id > -1"
        );
    }

    /**
     * Adds a record of data
     *
     * @param string $dataType The type of data e.g. "directory".
     * @param string $dataClass The class of data e.g. "user".
     * @param string $data The data itself (in json format).
     * @returns bool -- true if no errors/exceptions, exceptions otherwise
     * @throws Exception
     */
    public function recordData(string $dataType, string $dataClass, string $data): bool
    {
        if (empty($dataType)) {
            throw new SqliteUtilsBadDataTypeException("No data type given when trying to record " .
                "data.");
        }
        if (empty($dataClass)) {
            throw new SqliteUtilsBadDataClassException("No data class given when trying to record " .
                "data (data type: " . $dataType . ").");
        }

        // Add the record.
        $this->runSql(
            'INSERT INTO ' . $this->dbTable . ' (type, class, data)' .
            ' VALUES (:type, :class, :data)',
            [':type' => $dataType, ':class' => $dataClass, ':data' => $data],
            true
        );

        return true;
    }

    /**
     *  If the database file does not exist, creates it with an empty string
     *  with 0640 permissions.
     *
     * @returns void
     */
    public function createDbIfNotExists(): void
    {
        if (! file_exists($this->dbFile)) {
            file_put_contents($this->dbFile, '');
            chmod($this->dbFile, 0640);
        }
    }

    /**
     *  Database has one table with an id (int PK) column and three TEXT columns ...
     *    type (e.g. "directory"),
     *    class_name (e.g. "user"),
     *    data (json dump)
     *
     * @returns void
     */
    public function createDbStructureAsNecessary(): void
    {
        // Make sure the database file exists.
        $this->createDbIfNotExists();

        $this->runSql(
            "CREATE TABLE IF NOT EXISTS " . $this->dbTable . " (" .
            "id INTEGER PRIMARY KEY, " .
            "type TEXT, " .        // e.g. "directory"
            "class TEXT, " .    // e.g. "user"
            "data TEXT" .          // json
            ");"
        );
    }

    /**
     * Run the given SQL statement as a PDO prepared statement, using the given
     * array of data.
     *
     * @param string $sql The SQL statement.
     *                    Example: "SELECT * FROM table WHERE id = :id"
     * @param array $data (Optional) An associative array where the keys are
     * the placeholders in the SQL statement. Example: array(":id" => 1).
     * Defaults to an empty array (for when there are no placeholders in the
     * SQL statement).
     * @param bool $confirmAffectedRows (Optional) Whether to throw an
     * exception if PDOStatement::rowCount() indicates no rows were
     * affected. Defaults to false.
     * @param bool $returnData (Optional) Whether to retrieve (and return) the
     * resulting data by a call to PDOStatement::fetchAll($pdoFetchType).
     * Defaults to false.
     * @param int $pdoFetchType (Optional) The PDO FETCH_* constant defining
     * the desired return array configuration. See
     * http://www.php.net/manual/en/pdo.constants.php for options. Defaults
     * to PDO::FETCH_ASSOC.
     * @return array|null The array of returned results (if requested) as an
     * associative array, otherwise null.
     * @throws Exception
     */
    protected function runSql(
        string $sql,
        array $data = array(),
        bool $confirmAffectedRows = false,
        bool $returnData = false,
        int $pdoFetchType = PDO::FETCH_ASSOC
    ): ?array {
        // Make sure we're connected to the database.
        $this->setupDbConnIfNeeded();

        // Update the record in the database.
        $stmt = $this->db->prepare($sql);

        // Execute the prepared update statement with the desired data.
        $stmtSuccess = $stmt->execute($data);

        // If the statement was NOT successful...
        if ($stmtSuccess === false) {
            // Indicate failure.
            throw new SqliteUtilsSqlFailedException('SQL statement failed: ' . $sql);

            // If told to confirm that rows were affected
            // AND
            // if the statement didn't affect any rows...
        } elseif ($confirmAffectedRows && ($stmt->rowCount() < 1)) {
            // Indicate failure.
            throw new SqliteUtilsSqlAffectedNoRowsException('SQL statement affected no rows: ' . $sql);
        }

        // If told to return data, do so.
        if ($returnData) {
            return $stmt->fetchAll($pdoFetchType);
        } else {
            return null;
        }
    }

    protected function setupDbConnIfNeeded(): void
    {
        // If we have not yet set up the database connection...
        if (is_null($this->db)) {
            // Make sure the database itself exists.
            $this->createDbIfNotExists();

            // Connect to the SQLite database file.
            $this->db = new PDO('sqlite:' . $this->dbFile);

            // Set the error mode to exceptions.
            $this->db->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );
        }
    }
}
