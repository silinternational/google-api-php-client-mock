<?php
namespace SilMock\Google\Service;

use SilMock\Google\Service\Directory\Asps;
use SilMock\Google\Service\Directory\Tokens;
use SilMock\Google\Service\Directory\UsersResource;
use SilMock\Google\Service\Directory\UsersAliasesResource;
use SilMock\Google\Service\Directory\VerificationCodesResource;

class Directory
{
    public $asps;
    public $tokens;
    public $users;
    public $users_aliases;
    public $verificationCodes;
    
    /**
     * Sets the users and users_aliases properties to be instances of
     *    the corresponding mock classes.
     *
     * @param $client mixed - Ignored (normally it would be a Google_Client)
     * @param $dbFile string (optional) - The path and file name of the database file
     */
    public function __construct($client, $dbFile=null)
    {
        $this->asps = new Asps($dbFile);
        $this->tokens = new Tokens($dbFile);
        $this->users = new UsersResource($dbFile);
        $this->users_aliases = new UsersAliasesResource($dbFile);
        $this->verificationCodes = new VerificationCodesResource($dbFile);
    }
}
