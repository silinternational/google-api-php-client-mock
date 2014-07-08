<?php
namespace SilMock\Google\Service\Directory;


class User {


    public $changePasswordAtNextLogin; // bool
    public $hashFunction; // string
    public $id; // int???
    public $password; // string
    public $primaryEmail; // string email
    public $suspended; // bool
    public $suspensionReason; // string

    public function initialize($properties)
    {
        foreach ($properties as $key=>$value) {
            $this->$key = $value;
        }
    }

} 