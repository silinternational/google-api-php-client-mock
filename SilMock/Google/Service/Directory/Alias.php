<?php

namespace Google\Service\Directory;


class Alias {

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
} 