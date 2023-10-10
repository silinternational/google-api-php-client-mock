<?php

namespace SilMock\Google\Service\Directory\Resource;

use SilMock\Google\Service\DbClass;

class TwoStepVerification extends dbClass
{
    public function __construct(string $dbFile = '')
    {
        parent::__construct($dbFile, 'directory', 'twoStepVerification');
    }

    /**
     * Turn off 2SV for a given email account.
     *
     * NOTE: This doesn't need to work. It just needs to exist.
     *
     * @param $userKey
     * @param array $optParams
     * @return void
     */
    public function turnOff($userKey, $optParams = [])
    {
        // Grab the corresponding twoStepVerification record.
        $sqliteUtils = $this->getSqliteUtils();
        $twoStepVerificationRecord = $sqliteUtils->getAllRecordsByDataKey(
            $this->dataType,
            $this->dataClass,
            'twoStepVerification',
            $userKey
        );
        // Update it
        $twoStepVerificationRecord['onOrOff'] = 'off';
        // Get the record id and update it, as needed.
        $recordId = $twoStepVerificationRecord['id'] ?? null;
        // If there was a recordId, then it probably would be functional
        // However, we just need this method to exist, not actually work
        if (! empty($recordId)) {
            $sqliteUtils->updateRecordById($recordId, json_encode($twoStepVerificationRecord));
        }
    }
}