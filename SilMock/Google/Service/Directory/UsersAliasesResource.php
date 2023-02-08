<?php

namespace SilMock\Google\Service\Directory;

use Exception;
use Google_Service_Directory_Alias as Alias;
use Google_Service_Directory_Aliases;
use SilMock\DataStore\Sqlite\SqliteUtils;
use SilMock\Google\Service\Directory;

class UsersAliasesResource
{

    private $dbFile;  // string for the path (with file name) for the Sqlite database
    private $_dataType = 'directory';  // string to put in the 'type' field in the database
    private $_dataClass = 'users_alias'; // string to put in the 'class' field in the database


    public function __construct($dbFile = null)
    {
        $this->dbFile = $dbFile;
    }

    /**
     * Remove a alias for the user (aliases.delete)
     *
     * @param string $userKey  The email or immutable Id of the user
     * @param string $alias  The alias to be removed
     * @return true|null depending on if an alias was deleted
     * @throws Exception with code 201407101645
     */
    public function delete($userKey, $alias)
    {
        // If the $userKey is not an email address, it must be an id
        $key = 'primaryEmail';
        if (! filter_var($userKey, FILTER_VALIDATE_EMAIL)) {
            $key = 'id';
        }

        // ensure that user exists in db
        $dir = new Directory('anything', $this->dbFile);
        $matchingUsers = $dir->users->get($userKey);

        if ($matchingUsers === null) {
            throw new Exception("Account doesn't exist: " . $userKey, 201407101645);
        }

        // Get all the aliases for that user
        $sqliteUtils = new SqliteUtils($this->dbFile);
        $aliases =  $sqliteUtils->getAllRecordsByDataKey(
            $this->_dataType,
            $this->_dataClass,
            $key,
            $userKey
        );

        if (! $aliases) {
            return null;
        }

        // Check the data of each alias and when there is a match,
        // delete that alias and return true
        foreach ($aliases as $nextAlias) {
            $aliasData = json_decode($nextAlias['data'], true);
            if ($aliasData['alias'] === $alias) {
                $sqliteUtils->deleteRecordById(intval($nextAlias['id']));
                return true;
            }
        }

        return null;
    }

    /**
     * Add an alias for the user (aliases.insert)
     *
     * @param string $userKey  The email or immutable Id of the user
     * @param Alias $postBody  The array/object with the data for that alias
     * @return Alias - a real Google_Service_Directory_Alias instance
     * @throws Exception with code 201407110830 if a matching user is not found.
     */
    public function insert($userKey, $postBody)
    {
        // If the $userKey is not an email address, it must be an id
        $key = 'primaryEmail';
        if (! filter_var($userKey, FILTER_VALIDATE_EMAIL)) {
            $key = 'id';
        }

        // ensure that user exists in db
        $dir = new Directory('anything', $this->dbFile);
        $matchingUsers = $dir->users->get($userKey);

        if ($matchingUsers === null) {
            throw new Exception("Account doesn't exist: " . $userKey, 201407110830);
        }

        if ($postBody->$key === null) {
            $postBody->$key = $userKey;
        }

        return $this->insertAssumingUserExists($postBody);
    }

    /**
     * Adds an alias for a user that it assumes is already in the database (aliases.insert)
     *
     * @param Alias $postBody  The array/object with the data for that alias
     * @return Alias - a real Google_Service_Directory_Alias instance
     */
    public function insertAssumingUserExists($postBody)
    {
        $entryData = json_encode(get_object_vars($postBody));
        $sqliteUtils = new SqliteUtils($this->dbFile);
        $sqliteUtils->recordData(
            $this->_dataType,
            $this->_dataClass,
            $entryData
        );
        $allAliases = $sqliteUtils->getData($this->_dataType, $this->_dataClass);

        if (! $allAliases) {
            return null;
        }

        $newAlias = new Alias();
        ObjectUtils::initialize($newAlias, $postBody);

        return $newAlias;
    }

    /**
     * Gets a Google_Service_Directory_Aliases instance with its
     *     aliases property populated with Google_Service_Directory_Alias
     *     instances for that user
     *
     * @param string $userKey - The Email or immutable Id of the user
     * @return Google_Service_Directory_Aliases|null --  a real Google_Service_Directory_Aliases instance
     * @throws Exception with code 201407101420 if a matching user is not found.
     */
    public function listUsersAliases($userKey)
    {
        // If the $userKey is not an email address, it must be an id
        $key = 'primaryEmail';
        if (! filter_var($userKey, FILTER_VALIDATE_EMAIL)) {
            $key = 'id';
        }
        // ensure that user exists in db
        $dir = new Directory('anything', $this->dbFile);
        $matchingUsers = $dir->users->get($userKey);

        if ($matchingUsers === null) {
            throw new Exception("Account doesn't exist: " . $userKey, 201407101420);
        }

        $foundAliases =  $this->fetchAliasesByUser($key, $userKey);

        return $foundAliases;
    }

    /**
     * Gets a Google_Service_Directory_Aliases instance with its
     *     aliases property populated with Google_Service_Directory_Alias
     *     instances for that user
     *
     * @param string $keyType - "Email" or "Id"
     * @param string $userKey - The Email or immutable Id of the user
     * @return null|Google_Service_Directory_Aliases -- null if not found, Google_Service_Directory_Aliases otherwise.
     */
    public function fetchAliasesByUser($keyType, $userKey)
    {
        $sqliteUtils = new SqliteUtils($this->dbFile);
        $aliases =  $sqliteUtils->getAllRecordsByDataKey(
            $this->_dataType,
            $this->_dataClass,
            $keyType,
            $userKey
        );

        if (! $aliases) {
            return null;
        }

        $foundAliases = [];

        foreach ($aliases as $nextAlias) {
            $newAlias = new Alias();
            ObjectUtils::initialize($newAlias, json_decode($nextAlias['data'], true));

            $foundAliases[] = $newAlias;
        }

        $newUsersAliases = new Google_Service_Directory_Aliases();
        $newUsersAliases->setAliases($foundAliases);
        return $newUsersAliases;
    }
}
