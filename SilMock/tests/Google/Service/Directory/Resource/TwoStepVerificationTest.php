<?php

namespace Service\Directory\Resource;

use Exception;
use PHPUnit\Framework\TestCase;
use SilMock\Google\Service\Directory\Resource\TwoStepVerification;

class TwoStepVerificationTest extends TestCase
{
    public $dataFile = DATAFILE2;

    public function testTwoStepVerificationTurnOff()
    {
        $twoStepVerfication = new TwoStepVerification($this->dataFile);
        $this->assertIsObject($twoStepVerfication, 'Unable to instantiate twoStepVerification Mock object');

        try {
            $twoStepVerfication->turnOff('dummy@example.org');
        } catch (Exception $exception) {
            $this->assertFalse(
                true,
                sprintf(
                    'Was expecting the turnOff method to function, but got: %s',
                    $exception->getMessage()
                )
            );
        }
    }
}
