<?php
namespace SilMock\Google\Service\Directory;


class FakeGoogleAlias implements \ArrayAccess
{
    private $_values = array();

    public $alias;
    public $etag;
    public $id;
    public $kind;
    public $primaryEmail;


    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function setPrimaryEmail($primaryEmail)
    {
        $this->primaryEmail = $primaryEmail;
    }

    public function getPrimaryEmail()
    {
        return $this->primaryEmail;
    }

    public function setKind($kind)
    {
        $this->kind = $kind;
    }

    /**
     * Get a data by property name
     *
     * @param string The key data to retrieve
     */
    public function &__get ($key) {
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