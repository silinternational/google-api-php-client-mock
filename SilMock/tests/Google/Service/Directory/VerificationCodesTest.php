<?php

namespace SilMock\tests\Google\Service\Directory;

use PHPUnit\Framework\TestCase;
use Google_Service_Directory_VerificationCodes;
use SilMock\Google\Service\Directory;
use SilMock\tests\Google\Service\SampleUser;

class VerificationCodesTest extends TestCase
{
    use SampleUser;
    public $dataFile = DATAFILE2;

    public function testVerificationCodesGenerateAndList()
    {
        // set up a 2SV user.
        $newUser = $this->setupSampleUser($this->dataFile);
        self::assertIsObject($newUser, 'Unable to initialize sample user for generate test');

        // generate verification codes
        $directory = new Directory('anyclient', $this->dataFile);
        $returnValue = $directory->verificationCodes->generate($newUser->getPrimaryEmail());
        self::assertEmpty($returnValue);

        // list them
        $returnValue = $directory->verificationCodes->listVerificationCodes($newUser->getPrimaryEmail());
        self::assertInstanceOf(
            Google_Service_Directory_VerificationCodes::class,
            $returnValue,
            'Expecting Google_Service_Directory_VerificationCodes class'
        );
    }

} 
