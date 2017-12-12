<?php

class PActiveRecord extends CActiveRecord
{
    const STATUS_PENDING = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_DISABLED = 3;
    const STATUS_FINISHED = 4;


    public static function statuses()
    {
        return array(
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_DISABLED => 'Disabled',
            self::STATUS_FINISHED => 'Stoped',
        );

    }

    public function getStatusName()
    {
        $list = self::statuses();
        if (isset($list[$this->status])) {
            return $list[$this->status];
        } else {
            return 'Unknown';
        }
    }

    public function getPersianDate()
    {
        if (isset($this->create_time)) {
            return Yii::app()->apputil->date->date('Y/m/j', $this->create_time);
        }
    }

    public function getPersianTime()
    {
        if (isset($this->create_time)) {
            return Yii::app()->apputil->date->date('H:i', $this->create_time);
        }
    }

    public function changeStatusLink($status_name = 'status', $status, $redirect = 'admin')
    {
        $cc = new Controller(rand());
        return $cc->createUrl('/' . lcfirst(get_class($this)) . '/changeStatus', array(
            'id' => $this->id,
            'status' => $status,
            'status_name' => $status_name,

            'redirect' => Yii::app()->request->getBaseUrl(true) . "/" . $redirect
        ));
    }

    public function checkStatus($status, $status_name)
    {
        if (isset($this->$status_name)) {
            if ($this->$status_name == $status) {
                return true;
            }
        }
        return false;
    }

    public function createUrl($route, $params){
        $host = isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'panel.gsmgroup.ir/';
        return 'http://'.$host.Yii::app()->createUrl($route, $params);
    }
}