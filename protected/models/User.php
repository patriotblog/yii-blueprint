<?php

/**
 * Created by PhpStorm.
 * User: abdnia
 * Date: 10/5/16
 * Time: 11:44 AM
 */
class User extends UserEntity
{

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return UserEntity the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * Checks if the given password is correct.
     * @param string the password to be validated
     * @return boolean whether the password is valid
     */
    public function validatePassword($password)
    {
        return CPasswordHelper::verifyPassword($password, $this->password);
    }

    /**
     * Generates the password hash.
     * @param string password
     * @return string hash
     */
    public function hashPassword($password)
    {
        return CPasswordHelper::hashPassword($password);
    }
    public function beforeSave()
    {
        //exit(var_dump($this->regions));
        if (parent::beforeSave()) {

            if ($this->isNewRecord) {

                $this->password = $this->hashPassword($this->password);
                $this->create_time = time();
            }

            return true;
        }
        return false;
    }

}