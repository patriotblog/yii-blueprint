<?php
Yii::import('zii.widgets.grid.CGridView');

/**
 * @author Nikola Kostadinov
 * @license MIT License
 * @version 0.3
 */
class EExcelView extends CGridView
{
    //Document properties
    public $creator = 'Nikola Kostadinov';
    public $title = null;
    public $subject = 'Subject';
    public $description = '';
    public $category = '';

    //the PHPExcel object
    public $objPHPExcel = null;
    public $libPath = 'ext.phpexcel.Classes.PHPExcel'; //the path to the PHP excel lib

    //config
    public $autoWidth = true;
    public $width = 30;
    public $exportType = 'Excel5';
    public $disablePaging = true;
    public $filename = null; //export FileName
    public $stream = true; //stream to browser
    public $grid_mode = 'grid'; //Whether to display grid ot export it to selected format. Possible values(grid, export)
    public $grid_mode_var = 'grid_mode'; //GET var for the grid mode

    //buttons config
    public $exportButtonsCSS = 'summary';
    public $exportButtons = array('Excel2007');
    public $exportText = 'Export to: ';

    //callbacks
    public $onRenderHeaderCell = null;
    public $onRenderDataCell = null;
    public $onRenderFooterCell = null;

    //mime types used for streaming
    public $mimeTypes = array(
        'Excel5' => array(
            'Content-type' => 'application/vnd.ms-excel',
            'extension' => 'xls',
            'caption' => 'Excel(*.xls)',
        ),
        'Excel2007' => array(
            'Content-type' => 'application/vnd.ms-excel',
            'extension' => 'xlsx',
            'caption' => 'Excel(*.xlsx)',
        ),
        'PDF' => array(
            'Content-type' => 'application/pdf',
            'extension' => 'pdf',
            'caption' => 'PDF(*.pdf)',
        ),
        'HTML' => array(
            'Content-type' => 'text/html',
            'extension' => 'html',
            'caption' => 'HTML(*.html)',
        ),
        'CSV' => array(
            'Content-type' => 'application/csv',
            'extension' => 'csv',
            'caption' => 'CSV(*.csv)',
        )
    );


    public $extraHeaders = [];
    public $styles = [];
    public $process_styles = [];
    public $sheets = ['report'];
    public $dataProviderList;
    public $removableGeaderPrefix = [];
    public $columnWidth = [];
    public $charts = [];
    public $dataList;

    public function init()
    {
        $this->fix_styles();
        if (isset($_GET[$this->grid_mode_var]))
            $this->grid_mode = $_GET[$this->grid_mode_var];
        if (isset($_GET['exportType']))
            $this->exportType = $_GET['exportType'];

        $lib = Yii::getPathOfAlias($this->libPath) . '.php';
        if ($this->grid_mode == 'export' and !file_exists($lib)) {
            $this->grid_mode = 'grid';
            Yii::log("PHP Excel lib not found($lib). Export disabled !", CLogger::LEVEL_WARNING, 'EExcelview');
        }

        if ($this->grid_mode == 'export') {
            $this->title = $this->title ? $this->title : Yii::app()->getController()->getPageTitle();
            $this->initColumns();
            //parent::init();
            //Autoload fix
            //spl_autoload_unregister(array('YiiBase','autoload'));
            Yii::import($this->libPath, true);
            $this->objPHPExcel = new PHPExcel();
            spl_autoload_register(array('YiiBase', 'autoload'));
            // Creating a workbook
            $this->objPHPExcel->getProperties()->setCreator($this->creator);
            $this->objPHPExcel->getProperties()->setTitle($this->title);
            $this->objPHPExcel->getProperties()->setSubject($this->subject);
            $this->objPHPExcel->getProperties()->setDescription($this->description);
            $this->objPHPExcel->getProperties()->setCategory($this->category);
        } else
            parent::init();
    }

    private function renderExtraHeaders($column_count, $export_extra_headers)
    {
        $a = 0;
        $index = 1;
        foreach ($export_extra_headers as $key => $header) {



            $start = '';
            if (isset($header['style'])) {
                $css_styles = $header['style'];
            } elseif (isset($header['bgcolor'])) {
                $css_styles = $this->fixBgColorField($header['bgcolor']);
            }
            for ($i = 0; $i < $header['colspan']; $i++) {
                $a = $a + 1;
                $columnName = $this->columnName($a) . $index;
                if ($i == 0) {
                    $start = $columnName;
                }
                if(is_numeric($key) && isset($header['title']))
                    $title = $header['title'];
                else
                    $title = $key;
                $this->objPHPExcel->getActiveSheet()->setCellValue($this->columnName($a) . $index, $title, true);
            }
            $end = $columnName;
            $this->objPHPExcel->getActiveSheet()->mergeCells($start . ':' . $end);
            $style = $this->generateStyle($css_styles);

            $this->objPHPExcel->getActiveSheet()->getStyle($start . ':' . $end)->applyFromArray($style);
        }
        if (!empty($this->extraHeaders)) {
            $index++;
        }

        //exit();
        return $index;
    }

    public function renderHeader($column_count, $model_columns, $export_extra_headers)
    {
        $index = $this->renderExtraHeaders($column_count, $export_extra_headers);
        $a = 0;


        foreach ($model_columns as $column) {


            if($column_count){
                if($a > $column_count){
                    break;
                }
            }

            $a = $a + 1;
            if ($column instanceof CButtonColumn)
                $head = $column->header;
            elseif ($column->header === null && $column->name !== null) {
                if ($column->grid->dataProvider instanceof CActiveDataProvider)
                    $head = $column->grid->dataProvider->model->getAttributeLabel($column->name);
                else
                    $head = $column->name;
            } else
                $head = trim($column->header) !== '' ? $column->header : $column->grid->blankDisplay;

            $final_header = $head;
            if(!empty($this->removableGeaderPrefix)){
                foreach ($this->removableGeaderPrefix as $rhp) {
                    $final_header = str_replace($rhp, '', $final_header);
                }
            }

            $columnCellID = $this->columnName($a) . $index;

            $cell = $this->objPHPExcel->getActiveSheet()->setCellValue($columnCellID, $final_header, true);





            if (isset($this->process_styles['headers'])) {
                foreach ($this->process_styles['headers'] as $key => $val) {
                    if ($column->header == $key) {
                        $style = $this->generateStyle($val);
                        $this->objPHPExcel->getActiveSheet()->getStyle($columnCellID)->applyFromArray($style);
                    }
                }
            }

            if(!empty($this->columnWidth)){
                if(isset($this->columnWidth[$a])){
                    /*
                    echo '<pre>';
                    var_dump($columnID, $this->columnWidth, $a);
                    echo '</pre>';
                    exit();
                    */
                    $this->objPHPExcel->getActiveSheet()->getColumnDimension($this->columnName($a))->setWidth($this->columnWidth[$a]);
                }
            }



            if (is_callable($this->onRenderHeaderCell))
                call_user_func_array($this->onRenderHeaderCell, array($cell, $head));
        }
        $index++;
        return $index;
    }

    public function renderBody($index = 0, $data)
    {

        $n = count($data);

        if ($n > 0) {
            for ($row = 0; $row < $n; ++$row)
                $this->renderRow($row, $index, $data);
        }


        if ($n > 0) {
            for ($row = 0; $row < $n; ++$row)
                $this->renderColumnStyle($row, $index, $data);
        }


        return $n;
    }

    public function renderRow($row, $index = 2, $data)
    {


        $a = 0;





        foreach ($this->columns as $n => $column) {






            if(isset($data[$row]->counter)){
                if($a > $data[$row]->counter) {
                    break;
                }
            }


            if ($column instanceof CLinkColumn) {
                if ($column->labelExpression !== null)
                    $value = $column->evaluateExpression($column->labelExpression, array('data' => $data[$row], 'row' => $row));
                else
                    $value = $column->label;
            } elseif ($column instanceof CButtonColumn)
                $value = ""; //Dont know what to do with buttons
            elseif ($column->value !== null)
                $value = $this->evaluateExpression($column->value, array('data' => $data[$row]));
            elseif ($column->name !== null) {
                //$value=$data[$row][$column->name];
                $value = CHtml::value($data[$row], $column->name);
                $value = $value === null ? "" : $column->grid->getFormatter()->format($value, 'raw');
            }

            $a++;

            if ($a == 1) {
                $start = $this->columnName($a) . ($row + $index);
            }
            if(is_numeric($value))
                $cell = $this->objPHPExcel->getActiveSheet()->setCellValueExplicit($this->columnName($a) . ($row + $index), $value, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            else
                $cell = $this->objPHPExcel->getActiveSheet()->setCellValueExplicit($this->columnName($a) . ($row + $index), $value, PHPExcel_Cell_DataType::TYPE_STRING);

            if (isset($this->process_styles['columns'])) {
                foreach ($this->process_styles['columns'] as $key => $val) {
                    if ($column->header == $key) {
                        $style = $this->generateStyle($val);
                        $this->objPHPExcel->getActiveSheet()->getStyle($this->columnName($a) . ($row + $index))->applyFromArray($style);
                    }
                }
            }

            if (is_callable($this->onRenderDataCell))
                call_user_func_array($this->onRenderDataCell, array($cell, $data[$row], $value));
        }

        $end = $this->columnName($a) . ($row + $index);

        if (isset($this->process_styles['rows'][$row])) {
            $style = $this->generateStyle($this->process_styles['rows'][$row]);
            $this->objPHPExcel->getActiveSheet()->getStyle($start . ':' . $end)->applyFromArray($style);

        }
    }

    public function renderColumnStyle($row, $index = 2, $data)
    {


        $a = 0;
        foreach ($this->columns as $n => $column) {


            if(isset($data[$row]->counter)){
                if($a >= ($data[$row]->counter-1))
                    break;
            }

            if ($column instanceof CLinkColumn) {
                if ($column->labelExpression !== null)
                    $value = $column->evaluateExpression($column->labelExpression, array('data' => $data[$row], 'row' => $row));
                else
                    $value = $column->label;
            } elseif ($column instanceof CButtonColumn)
                $value = ""; //Dont know what to do with buttons
            elseif ($column->value !== null)
                $value = $this->evaluateExpression($column->value, array('data' => $data[$row]));
            elseif ($column->name !== null) {
                //$value=$data[$row][$column->name];
                $value = CHtml::value($data[$row], $column->name);
                $value = $value === null ? "" : $column->grid->getFormatter()->format($value, 'raw');
            }

            $a++;
            if ($a == 1) {
                $start = $this->columnName($a) . ($row + $index);
            }


            if (isset($this->process_styles['columns'])) {
                foreach ($this->process_styles['columns'] as $key => $val) {
                    if ($column->header == $key) {
                        $style = $this->generateStyle($val);
                        $this->objPHPExcel->getActiveSheet()->getStyle($this->columnName($a) . ($row + $index))->applyFromArray($style);
                    }
                }
            }


        }
    }

    public function renderFooter($row, $charts)
    {
        $a = 0;
        foreach ($this->columns as $n => $column) {
            $a = $a + 1;
            if ($column->footer) {
                $footer = trim($column->footer) !== '' ? $column->footer : $column->grid->blankDisplay;

                $cell = $this->objPHPExcel->getActiveSheet()->setCellValue($this->columnName($a) . ($row + 2), $footer, true);
                if (is_callable($this->onRenderFooterCell))
                    call_user_func_array($this->onRenderFooterCell, array($cell, $footer));
            }
        }

        if(!empty($charts)){
            foreach ($charts as $chart) {
                $this->renderChart($chart);
            }
        }
    }

    public function renderChart($chart){

        Yii::log(json_encode($chart), 'info', 'excel');
        if($chart['chart_type']=='row')
            list($plotValues, $labels, $plotCategory) = $this->generateRowChart($chart);
        elseif($chart['chart_type']=='col')
            list($plotValues, $labels, $plotCategory) = $this->generateColChart($chart);



        $plotOrder = range(0, count($plotValues)-1);
        //$plotOrder = [0, 1, 2];
        $series = new PHPExcel_Chart_DataSeries(
            $chart['type'],       // plotType
            $chart['group'],  // plotGrouping
            $plotOrder,                                       // plotOrder
            $labels,                                        // plotLabel
            $plotCategory,                             // plotCategory
            $plotValues                              // plotValues
        );

        //$series1->setPlotDirection(PHPExcel_Chart_DataSeries::DIRECTION_COL);

        if($chart['type'] == 'barChart')
            $series->setPlotDirection(\PHPExcel_Chart_DataSeries::DIRECTION_COL);



        $plotarea = new PHPExcel_Chart_PlotArea(null, array($series));
        $legend = new \PHPExcel_Chart_Legend(\PHPExcel_Chart_Legend::POSITION_TOPRIGHT, NULL, false);

        $name = (isset($chart['name']))?$chart['name']:'Chart';
        $title = new \PHPExcel_Chart_Title($name);

        $xTitle_name = (isset($chart['xTitle']))?$chart['xTitle']:'Date';
        $xTitle = new PHPExcel_Chart_Title($xTitle_name);

        $yTitle_name = (isset($chart['yTitle']))?$chart['yTitle']:'Count';
        $yTitle = new PHPExcel_Chart_Title($yTitle_name);

        $chart_draw  = new \PHPExcel_Chart(
            'chart1', // name
            $title, // title
            $legend, // legend
            $plotarea, // plotArea
            true, // plotVisibleOnly
            0, // displayBlanksAs
            $xTitle, // xAxisLabel
            $yTitle            // yAxisLabel
        );

        $place_start = $this->columnName($chart['place']['start']['col']).$chart['place']['start']['row'];
        $place_end = $this->columnName($chart['place']['end']['col']).$chart['place']['end']['row'];

        $chart_draw->setTopLeftPosition($place_start);
        $chart_draw->setBottomRightPosition($place_end);

        $r = $this->objPHPExcel->getActiveSheet()->addChart($chart_draw);

    }

    private function generateRowChart($chart){
        $sheet_title = $this->objPHPExcel->getActiveSheet()->getTitle();

        $x_axis = $chart['x-axis'];

        $xAxisTickValues_name = $sheet_title.'!$'.$this->columnName($x_axis['col'][0]).'$'.$x_axis['row'].':$'.$this->columnName($x_axis['col'][1]).'$'.$x_axis['row'];
        $plotCategory = [
            new \PHPExcel_Chart_DataSeriesValues('String', $xAxisTickValues_name),
        ];


        $plotValues = array();
        $labels = array();
        foreach ($chart['y-axis'] as $key=>$y_axis) {

            $dataSeriesValues1_range = $sheet_title.'!$'.$this->columnName($y_axis['col'][0]).'$'.$y_axis['row'].':$'.$this->columnName($y_axis['col'][1]).'$'.$y_axis['row'];
            $plotValues[] = new \PHPExcel_Chart_DataSeriesValues('Number', $dataSeriesValues1_range);
            $y_range = $sheet_title.'!'.$y_axis['lable'];
            $labels[] = new PHPExcel_Chart_DataSeriesValues('String', $y_range, null, 1);


        }

        return [$plotValues, $labels, $plotCategory];

    }

    private function generateColChart($chart){
        $sheet_title = $this->objPHPExcel->getActiveSheet()->getTitle();

        $x_axis = $chart['x-axis'];

        $xAxisTickValues_name = $sheet_title.'!$'.$this->columnName($x_axis['col']).'$'.$x_axis['row'][0].':$'.$this->columnName($x_axis['col']).'$'.$x_axis['row'][1];
        $plotCategory = [
            new \PHPExcel_Chart_DataSeriesValues('String', $xAxisTickValues_name),
        ];


        $plotValues = array();
        $labels = array();
        foreach ($chart['y-axis'] as $key=>$y_axis) {

            $dataSeriesValues1_range = $sheet_title.'!$'.$this->columnName($y_axis['col']).'$'.$y_axis['row'][0].':$'.$this->columnName($y_axis['col']).'$'.$y_axis['row'][1];


            $plotValues[] = new \PHPExcel_Chart_DataSeriesValues('Number', $dataSeriesValues1_range);
            $y_range = $sheet_title.'!'.$y_axis['lable'];
            $labels[] = new PHPExcel_Chart_DataSeriesValues('String', $y_range, null, 1);


        }

        return [$plotValues, $labels, $plotCategory];

    }

    private function clean_text($string) {
        $string = str_replace(' ', '', $string); // Replaces all spaces with hyphens.
        $string = str_replace('-', '', $string); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }
    private function normalize_sheet_name($name){



        $ret = $this->clean_text($name);


        $search  = array(0,1,2,3,4,5,6,7,8,9);
        $replace = array('Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine');


        if(is_numeric($ret[0])){
            $prefix = str_replace($search, $replace, $ret[0]);
            $ret = substr($ret, 1);
            $ret = $prefix.$ret;

        }
        return substr($ret, 0, 30);
    }
    public function run()
    {
        if ($this->grid_mode == 'export') {
            $i = 0;
            foreach ($this->sheets as $key => $val) {



                $sheet_name = $this->normalize_sheet_name($val);
                if ($i > 0) {
                    $objWorkSheet = $this->objPHPExcel->createSheet($i);
                    $objWorkSheet->setTitle($sheet_name);
                    $this->objPHPExcel->setActiveSheetIndex($i);
                } else {
                    $this->objPHPExcel->getActiveSheet()->setTitle($sheet_name);
                }





                if (count($this->sheets) > 1) {
                    if ($this->disablePaging) //if needed disable paging to export all data
                        $this->dataProviderList[$i]->pagination = false;


                    $data = $this->dataProviderList[$i]->getData();

                    $style = isset($data[0]->styles)?($data[0]->styles):$this->styles;


                    $p = $this->fix_styles($style);


                    $column_count = isset($data[0]->counter)?($data[0]->counter):null;
                    //export_columns
                    $model_columns = isset($data[0]->export_columns)?($data[0]->export_columns):$this->columns;
                    $export_extra_headers = isset($data[0]->export_extra_headers)?($data[0]->export_extra_headers):$this->extraHeaders;
                    $charts = isset($this->dataList[$i]->charts)?($this->dataList[$i]->charts):$this->charts;



                    //export_extra_headers


                    $index = $this->renderHeader($column_count, $model_columns, $export_extra_headers);

                    $row = $this->renderBody($index, $data);
                    $this->renderFooter($row, $charts);
                } else {

                    if ($this->disablePaging) //if needed disable paging to export all data
                        $this->dataProvider->pagination = false;

                    $data = $this->dataProvider->getData();

                    $column_count = isset($data[0]->counter)?($data[0]->counter):null;
                    $model_columns = isset($data[0]->export_columns)?($data[0]->export_columns):$this->columns;
                    $export_extra_headers = isset($data[0]->export_extra_headers)?($data[0]->export_extra_headers):$this->extraHeaders;
                    $charts = isset($data->charts)?($data->charts):$this->charts;

                    $index = $this->renderHeader($column_count, $model_columns, $export_extra_headers);

                    $row = $this->renderBody($index, $data);
                    $this->renderFooter($row, $charts);
                }


                $i++;
            }


            //create writer for saving
            $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, $this->exportType);
            if(!empty($this->charts))
                $objWriter->setIncludeCharts(TRUE);



            if (!$this->stream)
                $objWriter->save($this->filename);
            else //output to browser
            {
                if (!$this->filename)
                    $this->filename = $this->title;
                $this->cleanOutput();
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-type: ' . $this->mimeTypes[$this->exportType]['Content-type']);
                header('Content-Disposition: attachment; filename="' . $this->filename . '.' . $this->mimeTypes[$this->exportType]['extension'] . '"');
                header('Cache-Control: max-age=0');
                $objWriter->save('php://output');
                Yii::app()->end();
            }
        } else
            parent::run();
    }

    /**
     * Returns the coresponding excel column.(Abdul Rehman from yii forum)
     *
     * @param int $index
     * @return string
     */
    public function columnName($index)
    {
        --$index;
        if ($index >= 0 && $index < 26)
            return chr(ord('A') + $index);
        else if ($index > 25)
            return ($this->columnName($index / 26)) . ($this->columnName($index % 26 + 1));
        else
            throw new Exception("Invalid Column # " . ($index + 1));
    }

    public function renderExportButtons()
    {
        foreach ($this->exportButtons as $key => $button) {
            $item = is_array($button) ? CMap::mergeArray($this->mimeTypes[$key], $button) : $this->mimeTypes[$button];
            $type = is_array($button) ? $key : $button;
            $url = parse_url(Yii::app()->request->requestUri);
            //$content[] = CHtml::link($item['caption'], '?'.$url['query'].'exportType='.$type.'&'.$this->grid_mode_var.'=export');
            if (key_exists('query', $url))
                $content[] = CHtml::link($item['caption'], '?' . $url['query'] . '&exportType=' . $type . '&' . $this->grid_mode_var . '=export');
            else
                $content[] = CHtml::link($item['caption'], '?exportType=' . $type . '&' . $this->grid_mode_var . '=export');
        }
        if ($content)
            echo CHtml::tag('div', array('class' => $this->exportButtonsCSS), $this->exportText . implode(', ', $content));

    }

    /**
     * Performs cleaning on mutliple levels.
     *
     * From le_top @ yiiframework.com
     *
     */
    private static function cleanOutput()
    {
        for ($level = ob_get_level(); $level > 0; --$level) {
            @ob_end_clean();
        }
    }


    private function fixBgColorField($bgcolor)
    {
        $style = [];
        $tmp = explode(';', $bgcolor);
        foreach ($tmp as $str) {
            $rule = explode(':', $str);
            foreach ($rule as $k => $r) {
                unset($rule[$k]);
                $rule[trim($k)] = trim($r);
            }
            if (count($rule) > 1) {
                $style[$rule[0]] = $this->fix_color_value($rule[1]);
            } else {
                if (empty($style['background-color']))
                    $style['background-color'] = $this->fix_color_value($rule[0]);
            }


        }
        foreach ($style as $key => $val) {
            if (empty($val)) {
                unset($style[$key]);
            }
        }
        return $style;
    }

    private function fix_color_value($str)
    {
        $str = str_replace('1px solid ', '', $str);
        if (strpos($str, 'rgb') > -1) {
            $str = str_replace('rgb(', '', $str);
            $str = str_replace(')', '', $str);
            $str = str_replace(' ', '', $str);
            $str = $this->rgb2hex(explode(',', $str));
        } else {
            $str = str_replace('#', '', $str);
        }
        return $str;
    }

    private function rgb2hex($rgb)
    {
        $hex = "";
        $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

        return $hex; // returns the hex value including the number sign (#)
    }

    private function hex2rgb($hex)
    {
        $hex = str_replace("#", "", $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        $rgb = array($r, $g, $b);
        //return implode(",", $rgb); // returns the rgb values separated by commas
        return $rgb; // returns an array with the rgb values
    }

    private function generateStyle($css_styles, $print=false)
    {

        foreach ($css_styles as $css_name => $css_value) {
            if ($css_name == 'background-color') {
                $style['fill'] = array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => $css_value)
                );
            }
            if ($css_name == 'color') {
                $style['font'] = array(
                    //'bold'  => true,
                    'color' => array('rgb' => $css_value),
                    //'size'  => 15,
                    //'name'  => 'Verdana'
                );
            }
            if ($css_name == 'border-left') {
                $style['borders'] = array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => array('rgb' => $css_value)
                    )
                );
            }

            $align = $align = PHPExcel_Style_Alignment::HORIZONTAL_CENTER;

            if($css_name == 'text-align'){


                if($css_value == 'right'){
                    $align = PHPExcel_Style_Alignment::HORIZONTAL_RIGHT;
                }
                if($css_value == 'left'){
                    $align = PHPExcel_Style_Alignment::HORIZONTAL_LEFT;
                }



            }

            $style['alignment'] = array(
                'horizontal' => $align,
            );

        }
        if($print){
            echo '<pre>';
            var_dump($style);
            echo '</pre>';
        }

        return $style;
    }

    private function fix_styles($input_style = false)
    {

        $styles = ($input_style==false)?$this->styles:$input_style;
        $this->process_styles = [];
        foreach ($styles as $key => $style) {
            foreach ($style as $name => $value) {
                $this->process_styles[$key][$name] = $this->fixBgColorField($value);
            }
        }
        return [$this->process_styles, $input_style, $styles];
    }

}