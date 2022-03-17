<?php

namespace SilMock\tests\Google\Service\Gmail\Resource;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use SilMock\Google\Service\Gmail\Resource\UsersSettingsForwardingAddresses;
use SilMock\Google\Service\GoogleFixtures;

class UsersSettingsForwardingAddressesTest extends TestCase
{
    public $dataFile = DATAFILE4;
    
    protected function setUp(): void
    {
        $this->emptyFixturesDataFile();
    }
    
    private function emptyFixturesDataFile()
    {
        $fixturesClass = new GoogleFixtures($this->dataFile);
        $fixturesClass->removeAllFixtures();
    }
    
    protected function tearDown(): void
    {
        $this->emptyFixturesDataFile();
    }
    
    public function testListUsersSettingsForwardingAddresses()
    {
        $accountEmail = 'john_smith@example.org';
        $forwardingAddresses = new UsersSettingsForwardingAddresses();
        $result = $forwardingAddresses->listUsersSettingsForwardingAddresses($accountEmail);
        // Because this is totally a skeleton.
        Assert::assertEmpty($result);
    }
    
    public function testDelete()
    {
        $accountEmail = 'john_smith@example.org';
        $forwardingAddresses = new UsersSettingsForwardingAddresses();
        $forwardingAddresses->delete($accountEmail, $accountEmail);
        Assert::assertTrue(true, 'Because a skeleton should not explode.');
    }
}
