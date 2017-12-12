<?php

class uniqueMultiColumnValidator extends CValidator
{
    public $oneMessage = false;

    private $allowEmpty = false;

    public function setAllowEmpty($value)
    {
        $this->allowEmpty = $value;
    }

    public function getAllowEmpty()
    {
        return $this->allowEmpty;
    }

    private $caseSensitive = false;

    public function setCaseSensitive($value)
    {
        $this->caseSensitive = $value;
    }

    public function getCaseSensitive()
    {
        return $this->caseSensitive;
    }


    protected function validateAttribute($object, $attribute)
    {
        $attributes = null;
        $criteria = array('condition' => '');
        if (false !== strpos($attribute, "+")) {
            $attributes = explode("+", $attribute);
        } else {
            $attributes = array($attribute);
        }

        foreach ($attributes as $attribute) {
            $value = $object->$attribute;
            if ($this->allowEmpty && ($value === null || $value === ''))
                return;
            $column = $object->getTableSchema()->getColumn($attribute);
            if ($column === null)
                throw new CException(Yii::t('yii', '{class} does not have attribute "{attribute}".',
                    array('{class}' => get_class($object), '{attribute}' => $attribute)));
            $columnName = $column->rawName;
            if ('' != $criteria['condition']) {
                $criteria['condition'] .= " AND ";
            }
            $criteria['condition'] .= $this->caseSensitive ? "$columnName=:$attribute" : "LOWER($columnName)=LOWER(:$attribute)";
            $criteria['params'][':' . $attribute] = $value;
        }

        if ($column->isPrimaryKey)
            $exists = $object->exists($criteria);
        else {
            // need to exclude the current record based on PK
            $criteria['limit'] = 2;
            $objects = $object->findAll($criteria);
            $n = count($objects);
            if ($n === 1) {
                if ('' == $object->getPrimaryKey()) {
                    $exists = true;
                } else {
                    $exists = $objects[0]->getPrimaryKey() !== $object->getPrimaryKey();
                }
            } else
                $exists = $n > 1;
        }
        if ($exists) {
            $message = '';
            $labels = $object->attributeLabels();


            if (!$this->oneMessage) {

                if ($this->message == null) {
                    $message = '';
                    $labels = $object->attributeLabels();
                    foreach ($attributes as $attribute)
                        $message .= $labels[$attribute] . "+";
                    //remove the last +
                    $message = substr($message, 0, -1);

                    $this->addError($object, $attribute, "The Combination of ($message) should be unique in the current context.");

                } elseif (is_array($this->message)) {
                    foreach ($attributes as $attribute) {
                        if (iesset($this->message[$attribute]))
                            $this->addError($object, $attribute, $message[$attribute]);
                    }
                }

            } else { //single message
                if ($this->message == null) {
                    $message = '';
                    $labels = $object->attributeLabels();
                    foreach ($attributes as $attribute)
                        $message .= $labels[$attribute] . "+";
                    //remove the last +
                    $message = substr($message, 0, -1);

                    $this->addError($object, $attribute, "The Combination of ($message) should be unique in the current context.");
                } elseif (is_array($this->message)) {
                    $this->addError($object, $attribute, implode(",", $this->message));
                } else {
                    $this->addError($object, $attribute, $this->message);
                }
            }
        }
    }
}

?>
