<?php

/**
 * This Class is a singleton Component use to load most usefull string, date and graphic functions
 *
 * @param str strUtility String Utility Class
 * @param date pDate Persian Date Class
 * @param gd gdUtility Graphic related Class
 *
 * @author saeed
 * use like this
 * print Yii::app()->apputil->date->date("Y-m-d");
 */
class AppUtility extends CApplicationComponent
{

    /**
     * String utility for language usage and utilities related to string
     *
     * @var stUtility
     */
    public $appUtilities = array(
        'str' => 'strUtility',
        'date' => 'pDate',
        'gd' => 'gdUtility'
    );

    /**
     * @var AppConfig
     */
    private static $_instance;


    public function init()
    {
        self::setAppUtility($this);
        $this->setRootAliasIfUndefined();

        parent::init();
    }

    /**
     *
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        } elseif (isset($this->appUtilities[$property])) {
            $class = $this->appUtilities[$property];
            if (is_string($class)) {
                $propertyClass = require_once(Yii::getPathOfAlias('apputil.components') . "/$class.php");
                $class = $this->appUtilities[$property] = new $class();
            }

            return $class;
        }
    }

    /**
     *
     */
    protected function setRootAliasIfUndefined()
    {
        if (Yii::getPathOfAlias('apputil') === false) {
            Yii::setPathOfAlias('apputil', realpath(dirname(__FILE__)));
        }
    }

    /**
     */
    public static function setAppUtility($value)
    {
        if ($value instanceof AppUtility) {
            self::$_instance = $value;
        }
    }

    /**
     */
    public static function getAppUtility()
    {
        if (null === self::$_instance) {
            if (Yii::app()->hasComponent('apputil')) {
                self::$_instance = Yii::app()->getComponent('apputil');
            }
        }
        return self::$_instance;
    }
}