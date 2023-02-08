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
            $newAliases = new Google_Service_Directory_Aliases();
            $newAlias = new Google_Service_Directory_Alias();
            $newAlias->alias = 'user_alias1@sil.org';
            $newAlias->setKind("personal");
            $newAlias->primaryEmail = $newUser->primaryEmail;
            $newAliases->setAliases([$newAlias]);
            $newUser->aliases = $newAliases; // bool
        }

        $newDir = new Directory('anyclient', $dataFile);
        return $newDir->users->insert($newUser);
    }
}
