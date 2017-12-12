<?php

/**
 * @author MohammadAli, Saeed
 *
 */
class strUtility
{
    /**
     *
     * @param unknown $srting
     * @return mixed
     */
    public function EN2PN($srting)
    {
        $EN = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $PN = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        return str_replace($EN, $PN, $srting);
    }

    /**
     *
     * @param unknown $srting
     * @return mixed
     */
    public function PN2EN($srting)
    {
        $EN = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $PN = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        return str_replace($PN, $EN, $srting);
    }

    /**
     * @link http://www.idevcenter.com/wiki/fix_persian_string_function/
     * @param unknown $text
     */
    public function fixPersianString($text)
    {
        if (is_null($text))
            return null;

        $replacePairs = array(
            chr(0xD9) . chr(0xA0) => chr(0xDB) . chr(0xB0),
            chr(0xD9) . chr(0xA1) => chr(0xDB) . chr(0xB1),
            chr(0xD9) . chr(0xA2) => chr(0xDB) . chr(0xB2),
            chr(0xD9) . chr(0xA3) => chr(0xDB) . chr(0xB3),
            chr(0xD9) . chr(0xA4) => chr(0xDB) . chr(0xB4),
            chr(0xD9) . chr(0xA5) => chr(0xDB) . chr(0xB5),
            chr(0xD9) . chr(0xA6) => chr(0xDB) . chr(0xB6),
            chr(0xD9) . chr(0xA7) => chr(0xDB) . chr(0xB7),
            chr(0xD9) . chr(0xA8) => chr(0xDB) . chr(0xB8),
            chr(0xD9) . chr(0xA9) => chr(0xDB) . chr(0xB9),
            chr(0xD9) . chr(0x83) => chr(0xDA) . chr(0xA9),
            chr(0xD9) . chr(0x89) => chr(0xDB) . chr(0x8C),
            chr(0xD9) . chr(0x8A) => chr(0xDB) . chr(0x8C),
            chr(0xDB) . chr(0x80) => chr(0xD9) . chr(0x87) . chr(0xD9) . chr(0x94));

        return strtr($text, $replacePairs);
    }

    /**
     *
     * @param unknown $text
     * @return mixed
     */
    public function fixLongText($text)
    {
        //echo $text.'<br>';
        $text = preg_replace_callback(
            '/\S{24,}/i',
            create_function(
                '$matches',
                'return "\"".chunk_split($matches[0], 24, " ")."\"";'
            ),
            $text
        );
        //echo $text.'<br><br>';
        //exit('fixLongText');
        return $text;
    }

    /**
     *
     * @param unknown $text
     * @return mixed
     */
    public function fixTags($text)
    {
        //echo $text.'<br>';
        $text = preg_replace('/(#\S+)(\r+)/i', '$1 ', $text);
        //echo $text.'<br><br>';
        //exit('fixTags');
        return $text;
    }

    /**
     *
     * @param unknown $dt
     * @return string
     */
    public function when($dt)
    {
        $ndt = new DateTime();
        //$diff = date_diff($dt, $ndt);
        $diff = $dt->diff($ndt);
        $r = '';
        if ($diff->y > 0)
            $r = $diff->format('حدود %y سال');
        elseif ($diff->m > 0)
            $r = $diff->format('حدود %m ماه');
        elseif ($diff->d > 0)
            $r = $diff->format('حدود %d روز');
        elseif ($diff->h > 0)
            $r = $diff->format('حدود %h ساعت');
        elseif ($diff->i > 0)
            $r = $diff->format(' %i دقیقه');
        elseif ($diff->s > 0)
            $r = $diff->format(' %s ثانیه');
        else
            $r = 'زمان نامعلوم !';
        return $r;
    }

    /**
     *
     * @param unknown $String
     * @param unknown $Length
     * @return unknown|string
     */
    public function CutString($String, $Length)
    {
        //mb_internal_encoding('UTF-8');
        if (mb_strlen($String) <= $Length) {
            return $String;
        } else {
            $String = mb_substr($String, 0, $Length);
            $String = explode(" ", $String);
            $String[count($String) - 1] = "";
            $String = implode(" ", $String);
            return $String . "...";
        }
    }

    /**
     *
     * @param unknown $Value
     * @return boolean
     */
    public function IsNumber($Value)
    {
        //$x = (int) $x;
        //$x = intval($x);
        //settype($x, "int");
        $Pattern = '/^[0-9]{1,8}$/';
        //return preg_match($Pattern, $Value);
        if (preg_match($Pattern, $Value))
            return true;
        else
            return false;
    }

}