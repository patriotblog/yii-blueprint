<?php

class PWebUser extends CWebUser
{

    //public $isAppUser = true;

    public $_user;

    public function setRole($val)
    {
        $this->setState('role', $val);
    }

    public function getRole()
    {
        $state = $this->getState('role');
        if (!$state) {
            $this->renewState();
            $state = $this->getState('role');
        }
        return $state;

    }


}

?>
