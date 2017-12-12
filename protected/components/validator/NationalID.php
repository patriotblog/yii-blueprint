<?php

class NationalID extends CValidator
{
    /**
     * Holds National Code
     *
     * @var Integer
     */
    protected static $nationalCode;

    /**
     * Incorrect List
     *
     * @var Array
     */
    protected static $notNationalCode = array("1111111111", "2222222222", "3333333333", "4444444444", "5555555555",
        "6666666666", "7777777777", "8888888888", "9999999999", "0000000000");

    /**
     * Validates the attribute of the object.
     * If there is any error, the error message is added to the object.
     * @param CModel $object the object being validated
     * @param string $attribute the attribute being validated
     */
    protected function validateAttribute($object, $attribute)
    {
        self::$nationalCode = trim($object->$attribute);
        if (self::validCode()) {
            $melliCode = self::$nationalCode;
            $subMid = self::subMidNumbers($melliCode, 10, 1);
            $getNum = 0;

            for ($i = 1; $i < 10; $i++)
                $getNum += (self::subMidNumbers($melliCode, $i, 1) * (11 - $i));

            $modulus = ($getNum % 11);

            if (!(($modulus < 2 && $subMid == $modulus) || ($modulus >= 2 && $subMid == (11 - $modulus))))
                $this->addError($object, $attribute, 'National ID is Invalid!');
        }
    }

    /**
     *
     * @return boolean
     */
    protected function validCode()
    {
        $melliCode = self::$nationalCode;
        if ((is_numeric($melliCode)) && (strlen($melliCode) == 10) && (strspn($melliCode, $melliCode[0]) != strlen($melliCode))) return true;
        return false;
    }

    /**
     * Get Portion of String Specified
     *
     * @param unknown $number
     * @param unknown $start
     * @param unknown $length
     * @return string
     */
    protected function subMidNumbers($number, $start, $length)
    {
        $number = substr($number, ($start - 1), $length);
        return $number;
    }
}