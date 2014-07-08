<?php
namespace SilMock\Google\Service\Directory;

use SilMock\DataStore\Sqlite\SqliteUtils;
class Users_Resource {

    private $_dataType = 'directory';
    private $_dataClass = 'user';

    /**
     * Delete user (users.delete)
     *
     * @param string $userKey
     * Email or immutable Id of the user
     * @param array $optParams Optional parameters.
     */
    public function delete($userKey, $optParams = array())
    {
        //TODO: consider doing something with the $params
        $params = array('userKey' => $userKey);
        $params = array_merge($params, $optParams);

        if (filter_var($userKey, FILTER_VALIDATE_EMAIL)) {
            $userEntry = $this->getDbUser('primaryEmail', $userKey);
        } else {
            $userEntry = $this->getDbUser('id', $userKey);
        }

        if ($userEntry === null) {
            return null;
        }

        $sqliteUtils = new SqliteUtils();
        $sqliteUtils->deleteRecordById($userEntry['id']);
        return true;
    }

    /**
     * retrieve user (users.get)
     *
     * @param string $userKey
     * Email or immutable Id of the user
     * @param array $optParams Optional parameters.
     * @return Google_Service_Directory_User
     */
    public function get($userKey, $optParams = array())
    {
        //TODO: consider doing something with the $params
        $params = array('userKey' => $userKey);
        $params = array_merge($params, $optParams);

        $sqliteUtils = new SqliteUtils();
        $allUsers = $sqliteUtils->getData($this->_dataType, $this->_dataClass);
        $newUser = null;

        if (filter_var($userKey, FILTER_VALIDATE_EMAIL)) {
            $userEntry = $this->getDbUser('primaryEmail', $userKey);
        } else {
            $userEntry = $this->getDbUser('id', $userKey);
        }

        if ($userEntry === null) {
            return null;
        }

        $newUser = new User();
        $newUser->initialize(json_decode($userEntry['data'], true));

        return $newUser;
    }

    /**
     * create user. (users.insert)
     *
     * @param Google_User $postBody
     * @param array $optParams Optional parameters.
     * @return Google_Service_Directory_User
     */
    public function insert($postBody, $optParams = array())
    {
        //TODO: consider doing something with the $params
        $params = array('postBody' => $postBody);
        $params = array_merge($params, $optParams);

        $userData = json_encode($postBody);

        $sqliteUtils = new SqliteUtils();
        $sqliteUtils->recordData($this->_dataType, $this->_dataClass, $userData);

        return $this->get($postBody->primaryEmail);
    }

    /**
     * update user (users.update)
     *
     * @param string $userKey
     * Email or immutable Id of the user. If Id, it should match with id of user object
     * @param Google_User $postBody
     * @param array $optParams Optional parameters.
     * @return Google_Service_Directory_User
     */
    public function update($userKey, $postBody, $optParams = array())
    {
        //TODO: consider doing something with the $params
        $params = array('userKey' => $userKey, 'postBody' => $postBody);
        $params = array_merge($params, $optParams);

        if (filter_var($userKey, FILTER_VALIDATE_EMAIL)) {
            $userEntry = $this->getDbUser('primaryEmail', $userKey);
        } else {
            $userEntry = $this->getDbUser('id', $userKey);
        }

        if ($userEntry === null) {
            return null;
        }

        $sqliteUtils = new SqliteUtils();
        $sqliteUtils->updateRecordById($userEntry['id'], json_encode($postBody));
        return $this->get($userKey);

    }


    private function getDbUser($key, $value)
    {

        $sqliteUtils = new SqliteUtils();
        $allUsers = $sqliteUtils->getData($this->_dataType, $this->_dataClass);

        foreach ($allUsers as $userEntry) {
            $userData = json_decode($userEntry['data'], true);
            if (isset($userData[$key]) &&
                $userData[$key] === $value) {
                return $userEntry;
            }
        }

        return null;
    }

} 