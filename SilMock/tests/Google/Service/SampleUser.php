<?php

namespace SilMock\tests\Google\Service;

use Google_Service_Directory_User;
use Google_Service_Directory_Aliases;
use Google_Service_Directory_Alias;
use SilMock\Google\Service\GoogleFixtures;
use SilMock\Google\Service\Directory;

trait SampleUser
{
    public function setupSampleUser(string $dataFile, bool $withAliases = false): ?Google_Service_Directory_User
    {
        $fixturesClass = new GoogleFixtures($dataFile);
        $fixturesClass->removeAllFixtures();
        $newUser = new Google_Service_Directory_User();
        $newUser->changePasswordAtNextLogin = false; // bool
        $newUser->hashFunction = "SHA-1"; // string
        $newUser->id = 999991; // int???
        $newUser->password = 'testP4ss'; // string
        $newUser->primaryEmail = 'user_test1@sil.org'; // string email
        $newUser->suspended = false; // bool
        $newUser->isEnrolledIn2Sv = true;
        $newUser->isEnforcedIn2Sv = false;
        //  $newUser->$suspensionReason = ''; // string

        if ($withAliases) {
            $newUser->aliases = [ 'user_alias1@sil.org', 'user_alias2@sil.org' ];
        } else {
            $newUser->aliases = [];
        }

        $newDir = new Directory('anyclient', $dataFile);
        return $newDir->users->insert($newUser);
    }
}
