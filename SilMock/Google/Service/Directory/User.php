<?php
namespace SilMock\Google\Service\Directory;


class User implements \ArrayAccess
{

    protected $_values = array();

//    public $changePasswordAtNextLogin; // bool
//    public $hashFunction; // string
//    public $id; // int???
//    public $password; // string
//    public $primaryEmail; // string email
//    public $suspended; // bool
//    public $suspensionReason; // string
//    public $aliases = array();

    public function initialize($properties)
    {
        $this->aliases = array();
        foreach ($properties as $key=>$value) {
            $this->$key = $value;
        }
    }

    /**
     * Get a data by property name
     *
     * @param string The key data to retrieve
     */
    public function &__get ($key) {
        if ($key === 'aliases') {
            if (isset($this->aliases)) {
                $this->_values['aliases'] = $this->aliases;
            } else {
                $this->_values['aliases'] = array();
            }
        }
        return $this->_values[$key];
    }

    /**
     * Assigns a value to the specified property
     *
     * @param string The data key to assign the value to
     * @param mixed  The value to set
     */
    public function __set($key,$value) {
        $this->_values[$key] = $value;
        $this->$key = $value;
    }

    // These are for implementing the ArrayAccess
    public function offsetExists($offset) {
        return array_key_exists($offset, $this->_values);
    }

    public function offsetSet($offset, $value) {
        $this->_values[$offset] = $value;
    }

    public function offsetUnset($offset) {
        if ($this->offsetExists($offset)) {
            unset($this->_values[$offset]);
        }
    }

    public function offsetGet($offset) {
        return $this->offsetExists($offset) ? $this->_values[$offset]:NULL;
    }
} 