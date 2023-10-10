<?php

namespace SilMock\Google\Service\Directory;

use DateTime;
use Google_Service_Directory_Alias as Alias;
use Google_Service_Directory_Aliases;
use SilMock\DataStore\Sqlite\SqliteUtils;
use Google_Service_Directory_User;
use SilMock\Google\Service\DbClass;

class UsersResource extends DbClass
{
    public function __construct($dbFile = null)
    {
        parent::__construct($dbFile, 'directory', 'user');
    }

    /**
     * Deletes a user (users.delete)
     *
     * @param string $userKey - The Email or immutable Id of the user
     * @return null|true depending on if the user was found.
     */
    public function delete($userKey)
    {
        $userEntry = $this->getDbUser($userKey);

        if ($userEntry === null) {
            return null;
        }

        $sqliteUtils = new SqliteUtils($this->dbFile);
        $sqliteUtils->deleteRecordById($userEntry['id']);
        return true;
    }

    /**
     * Retrieves a user (users.get) and sets its aliases property
     *     based on its aliases found in the database.
     *
     * NOTE: This should also find any account that has that email address as an
     * alias. See the documentation:
     * https://developers.google.com/admin-sdk/directory/v1/reference/users/get
     *
     * @param string $userKey - The Email or immutable Id of the user
     * @return \Google_Service_Directory_User|null -- built from db if exists,
     *                                                null otherwise
     */
    public function get($userKey)
    {
        $newUser = null;
        $userEntry = $this->getDbUser($userKey);

        if ($userEntry === null) {
            $userEntry = $this->getDbUserByAlias($userKey);
            if ($userEntry === null) {
                return null;
            }
        }

        $newUser = new \Google_Service_Directory_User();
        ObjectUtils::initialize($newUser, json_decode($userEntry['data'], true));
        
        // find its aliases in the database and populate its aliases property
        $aliases = $this->getAliasesForUser($userKey);

        if ($aliases) {
            $foundAliases = array();
            foreach ($aliases['aliases'] as $nextAlias) {
                $foundAliases[] = $nextAlias['alias'];
            }

            $newUser->aliases = $foundAliases;
        }

        return $newUser;
    }
    
    /**
     * @param $userKey
     * @return Google_Service_Directory_Aliases|null
     */
    protected function getAliasesForUser($userKey): ?Google_Service_Directory_Aliases
    {
        // If the $userKey is not an email address, then it's an id.
        $key = 'primaryEmail';
        if (! filter_var($userKey, FILTER_VALIDATE_EMAIL)) {
            $key = 'id';
        }
        
        $usersAliases = new UsersAliasesResource($this->dbFile);
        return $usersAliases->fetchAliasesByUser($key, $userKey);
    }
    
    /**
     * Get the database record of the user (if any) that has the given email
     * address as an alias.
     *
     * NOTE: This does NOT do things like populate the returned user info with
     * its list of aliases. That is left to the calling function (such as
     * `UsersResource->get()`).
     *
     * @param $userKey
     * @return null|Google_Service_Directory_User
     */
    protected function getDbUserByAlias($userKey)
    {
        if (! filter_var($userKey, FILTER_VALIDATE_EMAIL)) {
            // This function only makes sense for actual email addresses.
            return null;
        }
        
        $allUsers = $this->getAllDbUsers();
        
        foreach ($allUsers as $aUser) {
            if (! isset($aUser['data'])) {
                continue;
            }
            
            $userData = json_decode($aUser['data'], true);
            if ($userData === null) {
                continue;
            }
            
            $primaryEmail = isset($userData['primaryEmail']) ? $userData['primaryEmail'] : null;
            
            $aliasesResource = $this->getAliasesForUser($primaryEmail);
            if ($aliasesResource) {
                foreach ($aliasesResource['aliases'] as $aliasResource) {
                    $alias = $aliasResource['alias'];
                    if (strcasecmp($alias, $userKey) === 0) {
                        return $aUser;
                    }
                }
            }
        }
        
        return null;
    }
    
    protected function getAllDbUsers()
    {
        $sqliteUtils = new SqliteUtils($this->dbFile);
        return $sqliteUtils->getData($this->dataType, $this->dataClass);
    }
    
    /**
     * Creates a user (users.insert) and sets its aliases property if any
     *     are given.
     *
     * @param Google_Service_Directory_User|UsersResource $postBody
     * @return Google_Service_Directory_User|null
     * @throws \Exception with code 201407101120, if the user already exists
     */
    public function insert($postBody)
    {
        $currentDateTime = new DateTime('now');
        $defaults = array(
            'id' => str_replace(array(' ', '.'), '', microtime()),
            'suspended' => false,
            'changePasswordAtNextLogin' => false,
            'isAdmin' => false,
            'isDelegatedAdmin' => false,
            'lastLoginTime' => $currentDateTime->format('c'),
            'creationTime' => $currentDateTime->format('c'),
            'agreedToTerms' => false,
            'isEnforcedIn2Sv' => 'false',
            'isEnrolledIn2Sv' => 'false',
        );

        // array_merge will not work, since $postBody is an object which only
        // implements ArrayAccess
        foreach ($defaults as $key => $value) {
            if (!isset($postBody[$key])) {
                $postBody[$key] = $value;
            }
        }

        $currentUser = $this->get($postBody->primaryEmail);

        if ($currentUser) {
            throw new \Exception(
                "Account already exists: " . $postBody['primaryEmail'],
                201407101120
            );
        }

        $newUser = new \Google_Service_Directory_User();
        ObjectUtils::initialize($newUser, $postBody);
        $userData = json_encode($newUser);

        // record the user in the database
        $sqliteUtils = new SqliteUtils($this->dbFile);
        $sqliteUtils->recordData($this->dataType, $this->dataClass, $userData);

        // record the user's aliases in the database
        if ($postBody->aliases) {
            $usersAliases = new UsersAliasesResource($this->dbFile);

            foreach ($postBody->aliases as $alias) {
                $newAlias = new Alias();
                $newAlias->alias = $alias;
                $newAlias->kind = "personal";
                $newAlias->primaryEmail = $postBody->primaryEmail;

                $usersAliases->insertAssumingUserExists($newAlias);
            }
        }

        // Get (and return) the new user that was just created back out of the database
        return $this->get($postBody->primaryEmail);
    }

    /**
     * Updates a user (users.update) in the database as well as its aliases
     *
     * @param string $userKey - The Email or immutable Id of the user.
     * @param Google_Service_Directory_User $postBody
     * @return Google_Service_Directory_User|null
     * @throws \Exception with code 201407101130 if a matching user is not found
     */
    public function update($userKey, $postBody)
    {
        $userEntry = $this->getDbUser($userKey);
        if ($userEntry === null) {
            throw new \Exception(
                "Account doesn't exist: " . json_encode($userKey, true),
                201407101130
            );
        }

        /*
         * only keep the non-null properties of the $postBody user,
         * except for suspensionReason.
         */
        $dbUserProps = json_decode($userEntry['data'], true);
        $newUserProps = get_object_vars($postBody);

        foreach ($newUserProps as $key => $value) {
            if ($value !== null || $key === "suspensionReason") {
                $dbUserProps[$key] = $value;
            }
        }
        if (!isset($dbUserProps['isEnforcedIn2Sv'])) {
            $dbUserProps['isEnforcedIn2Sv'] = 'false';
        }
        if (!isset($dbUserProps['isEnrolledIn2Sv'])) {
            $dbUserProps['isEnrolledIn2Sv'] = 'false';
        }

        // Delete the user's old aliases before adding the new ones
        $usersAliases = new UsersAliasesResource($this->dbFile);
        $aliasesObject = $usersAliases->listUsersAliases($userKey);

        if ($aliasesObject && isset($aliasesObject['aliases'])) {
            foreach ($aliasesObject['aliases'] as $nextAliasObject) {
                $usersAliases->delete($userKey, $nextAliasObject['alias']);
            }
        }

        $sqliteUtils = new SqliteUtils($this->dbFile);
        $sqliteUtils->updateRecordById($userEntry['id'], json_encode($dbUserProps));

        // Save the user's aliases
        if (isset($postBody->aliases) && $postBody->aliases) {
            foreach ($postBody->aliases as $alias) {
                $newAlias = new \Google_Service_Directory_Alias();
                $newAlias->alias = $alias;
                $newAlias->kind = "personal";
                $newAlias->primaryEmail = $postBody->primaryEmail;

                $insertedAlias = $usersAliases->insertAssumingUserExists($newAlias);
            }
        }

        return $this->get($userKey);
    }

    /**
     * Retrieves a user record from the database (users.delete)
     *
     * @param string $userKey - The Email or immutable Id of the user
     * @return null|array -- nested array for the matching database entry
     */
    private function getDbUser(string $userKey)
    {

        $key = 'primaryEmail';
        if (! filter_var($userKey, FILTER_VALIDATE_EMAIL)) {
            $key = 'id';
        }

        $sqliteUtils = new SqliteUtils($this->dbFile);
        return $sqliteUtils->getRecordByDataKey(
            $this->dataType,
            $this->dataClass,
            $key,
            $userKey
        );
    }

    /**
     * This mocks the Google_Service_Directory_Users_Resource's listUser
     * functionality.
     *
     * @param array $parameters -- This will have three keys.
     *     domain: The domain to limit the search to. It's ignored.
     *     maxResults: Used to limit the number of results.
     *                 It defaults to 100.
     *     query: A string of the form "foo:baz[*]".
     *            Where foo is a field to search on.
     *            And baz is what to partially match on.
     *            The '*' syntax is ignored.
     *
     * @return \Google_Service_Directory_Users
     */
    public function listUsers($parameters = [])
    {
        $results = new \Google_Service_Directory_Users();
        if (!key_exists('domain', $parameters)) {
            $parameters['domain'] = 'ZZZZZZZ';
        }
        if (!key_exists('maxResults', $parameters)) {
            $parameters['maxResults'] = 100;
        }
        if (!key_exists('query', $parameters)) {
            $parameters['query'] = '';
        }
        $parameters['query'] = urldecode($parameters['query']);
        $sqliteUtils = new SqliteUtils($this->dbFile);
        $allData = $sqliteUtils->getData($this->dataType, $this->dataClass);
        foreach ($allData as $userRecord) {
            $userEntry = json_decode($userRecord['data'], true);
            if ($this->doesUserMatch($userEntry, $parameters['query'])) {
                /** @var \Google_Service_Directory_UserName $newName */
                $newName = new \Google_Service_Directory_UserName([
                    'familyName' => $userEntry['name']['familyName'],
                    'fullName'   =>
                        $userEntry['name']['fullName'] ??
                        $userEntry['name']['givenName'] . ' ' . $userEntry['name']['familyName'],
                    'givenName'  => $userEntry['name']['givenName'],
                ]);
                /** @var \Google_Service_Directory_User $newEntry */
                $newEntry = new \Google_Service_Directory_User(array(
                    'primaryEmail' => $userEntry['primaryEmail'],
                    'customerId' => $userEntry['primaryEmail'],
                ));
                $newEntry->setName($newName);
                
                $allResultsUsers = $results->getUsers();
                $allResultsUsers[] = $newEntry;
                $results->setUsers($allResultsUsers);
            }
            if (count($results->getUsers()) >= $parameters['maxResults']) {
                break;
            }
        }
        return $results;
    }

    private function doesUserMatch($entry, $query = '')
    {
        if ($query === '') {
            return true;
        }
        $query = str_replace('*', '', $query);
        if (mb_strpos($query, '=') !== false) {
            $separator = '=';
        } else {
            $separator = ':';
        }
        list($field, $value) = explode($separator, $query);
        $field = trim($field);
        $value = trim($value);
        if (isset($entry[$field])) {
            $checkValue = $entry[$field];
            if (is_array($checkValue) && $field === 'name') {
                $checkIndividualValues = array();
                if (isset($checkValue['givenName'])) {
                    $checkIndividualValues[] = $checkValue['givenName'];
                }
                if (isset($checkValue['familyName'])) {
                    $checkIndividualValues[] = $checkValue['familyName'];
                }
                if (isset($checkValue['fullName'])) {
                    $checkIndividualValues[] = $checkValue['fullName'];
                }
                $checkValue = '';
                foreach ($checkIndividualValues as $checkIndividualValue) {
                    if (mb_strpos($checkIndividualValue, $value) !== false) {
                        $checkValue = $checkIndividualValue;
                        break;
                    }
                }
            } elseif (is_array($checkValue)) {
                throw new \Exception(
                    "Did not expect something other than name as an array. Got VALUE: " . var_dump($checkValue)
                );
            }
        } elseif (isset($entry['name'][$field])) {
            $checkValue = $entry['name'][$field];
        } elseif ($field === 'email' && isset($entry['primaryEmail'])) {
            $checkValue = $entry['primaryEmail'];
        } else {
            $checkValue = '';
        }
        if ($checkValue === null) {
            $checkValue = '';
        }
        if (! is_string($checkValue)) {
            throw new \Exception(sprintf(
                "Expecting a string.\nGot Entry: %s\nGot Field: %s\nGot VALUE: %s (%s)",
                var_export($entry, true),
                var_export($field, true),
                var_export($checkValue, true),
                gettype($checkValue)
            ));
        }
        if (mb_strpos($checkValue, $value) === 0) {
            return true;
        } else {
            return false;
        }
    }
}
