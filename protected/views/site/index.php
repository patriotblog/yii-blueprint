<?php
/* @var $this SiteController */

$this->pageTitle=Yii::app()->name;





$tags=array('Satu','Dua','Tiga');
echo CHtml::textField('test','',array('id'=>'test'));
$this->widget('ext.select2.ESelect2',array(
    'selector'=>'#test',
    'options'=>array(
        'tags'=>$tags,
    ),
));
?>


