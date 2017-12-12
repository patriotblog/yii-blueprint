<?php
/**
 * Group date picker is created to generate date picker input with prepend
 *
 * @author Saeed Gholizadeh <s.gholizadeh@gsmgroup.ir>
 */

Yii::import('booster.widgets.TbDatePicker');

class TbGroupDatePicker extends TbDatePicker
{

    /**
     * @var string has it prepend.
     */
    public $prepend;

    /**
     * over write run function to echo prepend also
     */
    public function run()
    {

        list($name, $id) = $this->resolveNameID();

        $pregroup = "";
        $postgroup = "";

        if ($this->prepend) {
            $pregroup = '<div class="input-group"><span class="input-group-addon">' . $this->prepend . '</span>';
            $postgroup = '</div>';
        }

        if (!empty($options['prepend']) || !empty($options['append'])) {
            $this->renderAddOnBegin($options['prepend'], $options['append'], $options['prependOptions']);
        }

        if ($this->hasModel()) {
            if ($this->form) {
                echo $this->form->textField($this->model, $this->attribute, $this->htmlOptions);
            } else {
                echo $pregroup . CHtml::activeTextField($this->model, $this->attribute, $this->htmlOptions) . $postgroup;
            }

        } else {
            echo $pregroup . CHtml::textField($name, $this->value, $this->htmlOptions) . $postgroup;
        }

        $this->registerClientScript();
        $this->registerLanguageScript();
        $options = !empty($this->options) ? CJavaScript::encode($this->options) : '';

        ob_start();
        echo "jQuery('#{$id}').datepicker({$options})";
        foreach ($this->events as $event => $handler) {
            echo ".on('{$event}', " . CJavaScript::encode($handler) . ")";
        }

        Yii::app()->getClientScript()->registerScript(__CLASS__ . '#' . $this->getId(), ob_get_clean() . ';');

    }
}