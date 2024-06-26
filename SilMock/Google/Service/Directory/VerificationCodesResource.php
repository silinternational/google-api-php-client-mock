<?php

namespace SilMock\Google\Service\Directory;

use Google_Service_Directory_VerificationCode;
use Google_Service_Directory_VerificationCodes;
use SilMock\DataStore\Sqlite\SqliteUtils;
use SilMock\Google\Service\DbClass;
use SilMock\Google\Service\Directory;

class VerificationCodesResource extends DbClass
{
    public function __construct(?string $dbFile = null)
    {
        parent::__construct($dbFile, 'directory', 'verification_codes');
    }

    /**
     * invalidate all the verification codes for a particular user.
     *
     * @param string|int $userKey The email or immutable Id of the user
     * @return true|null depending on if an alias was deleted
     * @throws \Exception -- when account doesn't exist
     */
    public function invalidate($userKey)
    {
        // If the $userKey is not an email address, it must be an id
        $key = 'primaryEmail';
        if (!filter_var($userKey, FILTER_VALIDATE_EMAIL)) {
            $key = 'id';
        }

        // ensure that user exists in db
        $dir = new Directory('anything', $this->dbFile);
        $matchingUser = $dir->users->get($userKey);
        if ($matchingUser === null) {
            throw new \Exception("Account doesn't exist: " . $userKey);
        }
        $email = $matchingUser->getPrimaryEmail();

        // Confirm verification codes exist.
        $sqliteUtils = new SqliteUtils($this->dbFile);
        $verificationCodes = $sqliteUtils->getAllRecordsByDataKey(
            $this->dataType,
            $this->dataClass,
            $key,
            $userKey
        );
        if (empty($verificationCodes)) {
            return null;
        }

        // Delete the verification codes for that email account.
        return $sqliteUtils->deleteDataByEmail($this->dataType, $this->dataClass, $email);
    }

    /**
     * Gets a Google_Service_Directory_VerificationCodes instance with its
     *     items set to all the listed verification codes for that user.
     *
     * @param string|int $userKey - The Email or immutable Id of the user
     * @return Google_Service_Directory_VerificationCodes
     */
    public function listVerificationCodes($userKey)
    {
        // If the $userKey is not an email address, it must be an id
        $key = 'primaryEmail';
        if (!filter_var($userKey, FILTER_VALIDATE_EMAIL)) {
            $key = 'id';
        }

        $sqliteUtils = new SqliteUtils($this->dbFile);
        $data = $sqliteUtils->getAllRecordsByDataKey(
            $this->dataType,
            $this->dataClass,
            $key,
            $userKey
        );
        $verificationCodes = new Google_Service_Directory_VerificationCodes();
        if (! empty($data)) {
            $verificationCodeData = $data[0]['data'];
            $decodedVerificationCodeData = json_decode($verificationCodeData, true);
            unset($decodedVerificationCodeData['primaryEmail']);

            ObjectUtils::initialize($verificationCodes, $decodedVerificationCodeData['data']);
        }
        return $verificationCodes;
    }

    /**
     * Generates a new set of verifications for the specified user.
     *
     * @param string|int $userKey - The Email or immutable Id of the user
     * @return array -- empty array which indicates success.
     */
    public function generate($userKey)
    {

        // generate 10 new verification codes.
        $newVerificationCodes = new Google_Service_Directory_VerificationCodes();
        for ($count = 0; $count < 10; ++$count) {
            $newVerificationCode = new Google_Service_Directory_VerificationCode();
            $newVerificationCode->verificationCode = mt_rand(10000000, 99999999);
            $after = $newVerificationCodes->getItems();
            $after[] = $newVerificationCode;
            $newVerificationCodes->setItems($after);
        }

        // invalidate the old ones.
        try {
            $this->invalidate($userKey);
        } catch (\Exception $e) {
            // ignore it
        }

        $keyedData = [
            'primaryEmail' => $userKey,
            'data' => $newVerificationCodes,
        ];

        // save the new ones.
        $entryData = json_encode($keyedData);
        if ($entryData !== false) {
            $sqliteUtils = new SqliteUtils($this->dbFile);
            $sqliteUtils->recordData(
                $this->dataType,
                $this->dataClass,
                $entryData
            );
        }

        return [];
    }
}
