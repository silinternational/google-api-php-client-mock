google-api-php-client-mock
==========================
A small scale intelligent mock of the Google API PHP Client for unit and
functional testing.

Overview
--------
This is intended to mock a portion of the Google APIs related to
Google Workspace accounts, particularly calls relating to users and users'
aliases.

## Directory
Properties of a Google Service Directory (GSD) include...

1. $asps, which gets set to a GSD Asps_Resource
2. $memebers, which gets set to a GSD Members_Resource
3. $users, which gets set to a GSD Users_Resource
4. $users_aliases, which gets set to a GSD UsersAliases_Resource
5. $tokens, which gets set to a GSD Tokens_Resource
6. $twoStepVerification, which gets set to a GSD TwoStepVerification_Resource
7. $verificationCodes, which gets set to a GSD VerificationCodes_Resource

### Asps_Resource
An Asps_Resource is for managing a user's App Specific Passwords
(ASPs). This mock implements...

1. listAsps()

### Members_Resource
A Members_Resource is for managing members of a group.
This mock implements...

1. insert()
2. listMembers()

### Users_Resource
A Users_Resource has various methods for managing Google Apps users.
Three of these that are implemented by this mock are ...

1. delete()
2. get()
2. insert()
3. update()
4. listUsers()

### UsersAliases_Resource
A UsersAliases_Resource has various methods for managing Google Apps
users aliases.  The ones implemented by this mock are ...

1. delete()
2. insert()
3. listUsersAliases()

### Tokens_Resource
A Tokens_Resource is for managing a user's OAuth access tokens. This
mock implements...

1. listTokens()

### TwoStepVerification_Resource
A TwoStepVerification_Resource is for turning off 2SV. This
mock implements...

1. turnOff()

### VerificationCodes_Resource
A VerificationCodes_Resource is for managing a user's OAuth access tokens. This
mock implements...

1. generate()
2. invalidate()
3. listVerificationCodes()


## Gmail
Properties of the Gmail API object include...

1. $users_settings
2. $users_settings_delegates
3. $users_settings_forwardingAddresses

### UsersSettings
Methods on the UsersSettings resource that this mock implements include...

1. updateImap()
2. updatePop()

## UsersSettingsDelegates
Methods on the UsersSettingsDelegates resource that this mock implements
include...

1. create()
2. delete()
3. get()
4. listUsersSettingsDelegates()

## UsersSettingsForwardingAddresses
Methods on the UsersSettingsForwardingAddresses resource that this mock
implements include...

1. listUsersSettingsForwardingAddresses()

Unit Testing
------------
You should have docker and the docker compose plugin installed.
To run testing:
 - make it-now

Data Persistence
----------------
In order to keep data available for use by this mock, it makes use of a
**Sqlite** database file. The default path and name of this file are ...
**SilMock/DataStore/Sqlite/Google_Service_Data.db**.  To override this,
the constructors for the UsersResource and UsersAliasesResource class
accept an optional string parameter.

The database is accessed/managed by
**SilMock/DataStore/Sqlite/SqliteUtils.php**.  It has one table with
four columns ...

1. id  = INTEGER PRIMARY KEY,
2. type = TEXT,  e.g. "directory",
3. class = TEXT, e.g. "user" or "users_alias",
4. data = TEXT

The **data** field contains json with key-value pairs related to the
properties of the GSD objects.  The data is prepared by using the php
json_encode function.

Test Fixtures
-------------
There is a class to assist with dealing with data for unit tests ...
**SilMock\Google\Service\GoogleFixtures.php**.  Its constructor accepts
an optional parameter for the path and name of the Sqlite database file.
It has two methods ...

1. addFixtures($fixtures), expecting an array of 3-element arrays
    (type, class, data).
2. removeAllFixtures()

Unit Tests for the Mock Itself
------------------------------
The SilMock/tests folder includes phpunit tests for the three main
portions of this mock (Directory, GoogleFixtures, SqliteUtils).
These should help provide examples of how to use the mock.

Examples
--------

### Switching between the Mock and the Real GSD
    public static function useRealGoogle() {
        return  ( ! isset (\Yii::app()->params['use_real_google']) ||
                  \Yii::app()->params['use_real_google']);
    }

    public static function getGoogleServiceDirectory($client) {
        if (self::useRealGoogle()) {
            return new Google\Service\Directory($client);
        }
        $db_path = null;
        if (isset(\Yii::app()->params['googleMockDbPath'])) {
            $db_path = \Yii::app()->params['googleMockDbPath'];
        }
        return new SilMock\Google\Service\Directory($client, $db_path);
    }

### Managing a User
    $dir = self::getGoogleServiceDirectory($client);
    $google_user = new Google\Service\Directory\User();
    $google_user = $dir->users->insert($google_user);
    $google_user = $dir->users->get($usersEmail);

    $google_user->suspended = true;
    $google_user->suspensionReason = 'ADMIN';
    $account = $dir->users->update($users_email, $google_user);

### Managing a User's Aliases
    $dir = self::getGoogleServiceDirectory($client);
    $google_alias = new Google\Service\Directory\Alias();
    $google_alias->setAlias($alias);
    $alias = $dir->users_aliases->insert($users_email, $google_alias);

    $aliases = $dir->users_aliases->listUsersAliases($users_email);
    $alias = $dir->users_aliases->delete($users_email, $alias);

### DEVELOPER'S NOTE: Releases should include updating the version in composer.json to match