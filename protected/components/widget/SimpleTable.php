<?php
/**
 * Class to generate simple tables
 *
 * @author Saeed Gholizadeh <s.gholizadeh@gsmgroup.ir>
 */

Yii::import('zii.widgets.CPortlet');

class SimpleTable extends CPortlet
{
    /**
     * @var array data.
     * @var array headers.
     * @var array header_offset (added manually for categorizing and there is no data on data array for their offset)
     * @var bool is 3d array or 2d.
     * @var string table css class.
     * @var array sum array.
     */
    public $data;
    public $dataList;
    public $headers = array();
    public $headersColor = array();
    public $is3d = false;
    public $header_offset = array();
    public $tableclass = "";
    public $additinalHeaderRows = array();
    public $barColumns = [];
    public $showRowNumber = true;
    public $dataColumnStyle = [];
    public $sum = array(
        'data' => null,
        'row' => null,
        'column' => null,
    );
    public $extraheaders = null;
    public $topheaders = null;
    public $removeable_header_prefix = [];

    public $export_model;
    public $exportColumnWidth;
    public $export_charts = [];
    public $export_stream = true;
    public $report_model;
    public $report_modelList;
    public $report_columns;
    public $report_method = 'POST';
    public $report_uri = '';
    public $report_filed_name = 'report';
    public $report_file_name = 'test.xlsx';
    public $report_sheets = ['report'];
    public $filters;

    private $data_headers = array();

    private function renderExcel($dataProvider, $columns)
    {

        Yii::import('ext.phpexcel.Classes.PHPExcel.*', true);
        $p = dirname(__DIR__) . '/../extensions/phpexcel/Classes/PHPExcel.php';
        require($p);

        if (count($this->report_sheets) > 1) {
            $dataProviderList = $this->report_modelList;
        } else {
            $dataProviderList = false;
        }

        $this->widget('EExcelView', array(
            'dataProvider' => $dataProvider,
            'dataProviderList' => $dataProviderList,
            'dataList'=>$this->dataList,
            'sheets' => $this->report_sheets,
            'title' => 'Title',
            'autoWidth' => false,
            'filename' => $this->report_file_name,
            'stream' => $this->export_stream,
            'grid_mode' => 'export',
            'columns' => $columns,
            'exportType' => 'Excel2007',
            'extraHeaders' => $this->topheaders,
            'removableGeaderPrefix'=>$this->removeable_header_prefix,
            'columnWidth'=>$this->exportColumnWidth,
            'charts'=>$this->export_charts,
            'styles' => array(
                'rows' => $this->fixRowStyle(),
                'columns' => $this->dataColumnStyle,
                'headers' => $this->headersColor
            )
        ));
    }

    private function fixRowStyle($data=false)
    {

        if($data == false){
            $row_data = $this->data;
        }else{
            $row_data = $data;
        }

        $i = 0;
        $ret = [];
        foreach ($row_data as $key => $row) {
            if (isset($this->additinalHeaderRows[$key])) {
                $ret[$i] = $this->additinalHeaderRows[$key];
            } else {

            }
            $i++;
        }
        return $ret;
    }

    private function setDataToDownload()
    {
        $this->report_columns = [];

        $report = [];

        $i = 1;


        foreach ($this->data as $key => $d) {

            $model = new TemplateClass();

            foreach ($this->headers as $header) {
                $name = $header;
                if (!isset($d[$header])) {
                    $value = $i;
                    //continue;
                } else {

                    $value = $d[$header];
                }

                $attribute = $model->createProperty($name, $value);
                $this->report_columns[$attribute] = array(
                    'name' => $attribute,
                    'value' => '$data->getProperty("'. $attribute.'")',
                    'header' => $name,
                );




            }
            $i++;
            $report[] = $model;
        }

        $this->report_model = new CArrayDataProvider($report);
        $this->renderExcel($this->report_model, $this->report_columns);

    }

    private function setDataListToDownload()
    {
        //$this->report_columns = [];
        $reportList = [];
        foreach ($this->dataList as $data) {
            $report = [];

            $i = 1;


            $sheet_header = isset($data->tableHeader)?$data->tableHeader:$this->headers;
            $report_columns = [];

            foreach ($data->tableData as $key => $d) {

                $model = new TemplateClass();
                $model->export_extra_headers = $data->tableExtraTopHeaders;
                $model->export_columns = [];
                $model->styles = array(
                    'rows' => $this->fixRowStyle($data),
                    'columns' => $data->tableDataColumnStyle,
                    'headers' => $data->tableHeadersColor
                );



                foreach ($sheet_header as $header) {
                    $name = $header;
                    if (!isset($d[$header])) {
                        $value = $i;
                        //continue;
                    } else {

                        $value = $d[$header];
                    }


                    $attribute = $model->createProperty($name, $value);

                    //CGridColumn
                    $report_columns = new stdClass;
                    $report_columns->name = $attribute;
                    $report_columns->value = '$data->getProperty("'. $attribute.'")';
                    $report_columns->header = $name;

                    $model->export_columns[$attribute] = $report_columns;





                }
                $i++;
                $report[] = $model;

            }


            $reportList[] = new CArrayDataProvider($report);
        }



        $this->report_modelList = $reportList;

    }

    protected function renderContent()
    {

        if (isset($_GET['download']) && $_GET['download'] = 'true') {
            if (!empty($this->export_model)) {
                if (count($this->report_sheets) > 1)
                    $this->setDataListToDownload();

                $this->setDataToDownload();
            } elseif (!empty($this->report_model) && !empty($this->report_columns)) {
                $this->renderExcel($this->report_model, $this->report_columns);
            }
        } else {
            $result = $this->renderDownloadBtn();
            $result .= $this->renderHead();

            foreach ($this->headers as $header) {
                if (!in_array($header, $this->header_offset))
                    $this->data_headers[] = $header;
            }
            $result .= $this->renderBody();

            echo $result;
        }
    }

    private function codeRepo($result)
    {
        $p = dirname(__DIR__) . '/../vendors/HtmlPhpExcel/lib/HtmlPhpExcel/HtmlPhpExcel.php';
        require_once($p);

        $doc = new DOMDocument();
        @$doc->loadHTML($result);

        $tags = $doc->getElementsByTagName('table');


        $newdoc = new DOMDocument();
        $cloned = $tags[0]->cloneNode(TRUE);
        $newdoc->appendChild($newdoc->importNode($cloned, TRUE));
        $r = $newdoc->saveHTML();


        $htmlPhpExcel = new \Ticketpark\HtmlPhpExcel\HtmlPhpExcel($r);
        $htmlPhpExcel->process()->output(date('d/m/Y - h:s') . '.xls');
        exit();
    }

    private function renderDownloadBtn()
    {
        if (
            (!empty($this->report_model) && !empty($this->report_columns)) OR
            (!empty($this->export_model))
        ) {
            $uri = empty($this->report_uri) ? Yii::app()->request->requestUri . 'download=true' : $this->report_uri . 'download=true';

            ?>
            <form action="<?php echo $uri; ?>" method="<?php echo $this->report_method; ?>">

                <?php
                foreach ($this->filters as $label => $filter) {
                    if (empty($this->report_filed_name)) {
                        $name = $label;
                    } else {
                        $name = $this->report_filed_name . '[' . $label . ']';
                    }
                    echo '<input type="hidden" name="' . $name . '" value=\'' . $filter . '\'>';
                }
                if (strtolower($this->report_method) == 'get') {
                    echo '<input type="hidden" name="download" value="true">';
                }
                ?>


                <input type="submit" value="Download" class="btn btn-primary btn-sm">
                <div style="clear: both">&nbsp;</div>
            </form>
        <?php }
    }

    /**
     * render the header part of table
     * it will handle extra headers also which include other headers.
     *
     * @return string
     */
    private function renderHead()
    {


        $class = !empty($this->tableclass) ? "class='{$this->tableclass}'" : "";
        $head = "<table $class><thead><tr>";

        if (!empty($this->topheaders)) {
            foreach ($this->topheaders as $key => $th) {
                if (trim($key)) {
                    $css = "'border:0px; text-align:center; background-color:" . $th['bgcolor'] . "'";
                } else {
                    $css = "'border:0px; text-align:center; background-color:" . $th['bgcolor'] . "'";
                }

                $width = isset($th['width'])? 'width="'.$th['width'].'"':'';

                if(is_numeric($key) && isset($th['title']))
                    $head .= "<th colspan=" . $th['colspan'] . " style=" . $css . " ".$width.">" . $th['title'] . "</th>";
                else
                    $head .= "<th colspan=" . $th['colspan'] . " style=" . $css . " ".$width.">" . $key . "</th>";
            }
            $head .= "</tr><tr>";
        }

        if (!empty($this->extraheaders)) {
            /**
             * merge column can resort columns
             * so there should be three temp array
             * one for first row
             * one for second row
             * one for new sort of headers wich contains column from first and second row
             */
            $first_row = array();
            $second_row = array();
            $new_header = array();
            foreach ($this->headers as $header) {

                if (in_array($header, $new_header))
                    continue;

                $used = false;

                foreach ($this->extraheaders as $h_key => $ex_head) {
                    //var_dump($ex_head);

                    if (in_array($header, $ex_head)) {

                        $first_row [] = array(
                            'value' => $h_key,
                            'colspan' => count($ex_head),
                            'rowspan' => 1,
                            'style' => 'border-bottom: 1px solid #fff !important;',
                        );
                        foreach ($ex_head as $expanded_header) {

                            $second_row [] = $expanded_header;
                            $new_header[] = $expanded_header;
                        }

                        $used = true;
                        continue 2;
                    }
                }
                //exit();

                if (!$used) {
                    $first_row [] = array(
                        'value' => $header,
                        'colspan' => 1,
                        'rowspan' => 2,
                        'style' => '',
                    );
                    $new_header[] = $header;
                }

            }

            foreach ($first_row as $row) {
                //$color = isset($this->headersColor[$row['value']])?$this->headersColor[$row['value']]:'';
                //$row['style'] .= 'background-color='.$color;
                $head .= "<th rowspan='{$row['rowspan']}' colspan='{$row['colspan']}' style='{$row['style']}'>{$row['value']}</th>";
            }
            $head .= $this->total_headers(2);
            $head .= "</tr><tr>";
            foreach ($second_row as $row) {
                $head .= "<th>$row</th>";
            }

            $this->headers = $new_header;

        } else {
            foreach ($this->headers as $header) {
                $color = isset($this->headersColor[$header]) ? $this->headersColor[$header] : '';
                $header_title = $header;
                if(!empty($this->removeable_header_prefix)){
                    foreach ($this->removeable_header_prefix as $rhp) {
                        $header_title = str_replace($rhp, '', $header_title);
                    }
                }
                $head .= "<th style='border:0px; background-color:$color'>$header_title</th>";
            }

            $head .= $this->total_headers(1);
        }

        $head .= "</tr></thead>";

        return $head;
    }

    private function retrieveHead()
    {


        //$class = !empty($this->tableclass) ? "class='{$this->tableclass}'" : "";
        //$head = "<table $class><thead><tr>";
        $head = [];

        if (!empty($this->extraheaders)) {
            /**
             * merge column can resort columns
             * so there should be three temp array
             * one for first row
             * one for second row
             * one for new sort of headers wich contains column from first and second row
             */
            $first_row = array();
            $second_row = array();
            $new_header = array();
            foreach ($this->headers as $header) {

                if (in_array($header, $new_header))
                    continue;

                $used = false;

                foreach ($this->extraheaders as $h_key => $ex_head) {
                    if (in_array($header, $ex_head)) {

                        $first_row [] = array(
                            'value' => $h_key,
                            'colspan' => count($ex_head),
                            'rowspan' => 1,
                            'style' => 'border-bottom: 1px solid #fff !important;',
                        );
                        foreach ($ex_head as $expanded_header) {
                            $second_row [] = $expanded_header;
                            $new_header[] = $expanded_header;
                        }

                        $used = true;
                        continue 2;
                    }
                }

                if (!$used) {
                    $first_row [] = array(
                        'value' => $header,
                        'colspan' => 1,
                        'rowspan' => 2,
                        'style' => '',
                    );
                    $new_header[] = $header;
                }

            }

            foreach ($first_row as $row) {
                $head[] = $row['value'];
            }
            $head[] = $this->retrieve_total_headers(2);

            foreach ($second_row as $row) {
                $head[] = $row;
            }

            $this->headers = $new_header;

        } else {
            foreach ($this->headers as $header)
                $head[] = $header;

            $head[] = $this->retrieve_total_headers(1);
        }

        //$head .= "</tr></thead>";

        return $head;
    }

    private function total_headers($total_rowspan)
    {
        $total_title = "";
        if (!empty($this->sum['data']) && isset($this->sum['row'])) {

            if (!$this->is3d)
                $total_title .= "<th rowspan='$total_rowspan' >{$this->headers[0]} total</th>";
            else
                $total_title .= "<th rowspan='$total_rowspan' >{$this->headers[1]} total</th><th rowspan='$total_rowspan'>{$this->headers[0]} total</th>";
        }
        return $total_title;
    }

    private function retrieve_total_headers($total_rowspan)
    {
        $total_title = [];
        if (!empty($this->sum['data']) && isset($this->sum['row'])) {

            if (!$this->is3d)
                $total_title[] = $this->headers[0];
            else
                $total_title[] = $this->headers[0] . " total";
        }
        return $total_title;
    }

    private function renderBody()
    {
        $body = "<tbody>";
        if (!$this->is3d) {
            $body .= implode("", $this->thirdlayer($this->data));
        } else {
            foreach ($this->data as $row => $data) {
                //var_dump($row);
                //continue;
                $row_span = count($data) > 1 ? "rowspan='" . count($data) . "'" : "";


                $body .= "<tr><td $row_span>$row</td>";
                $entery_rows = $this->thirdlayer($data, $row . "_");

                /**
                 * these 2 lines of code make all rows for a general row
                 * a general row can have more than one rows so because of HTML TABLE STRUCTURE first row shuld be complete
                 * so here we generate a complete row by getting the first element of all rows and remove <tr> open and close tags
                 * then we add rest of rows.
                 */
                $body .= substr($entery_rows[0], 4, -5);
                unset($entery_rows[0]);

                if (!empty($this->sum['data']) && isset($this->sum['row']))
                    $body .= "<td $row_span>" . $this->sum['data'][$this->sum['row']][$row] . "</td>";

                $body .= "</tr>";
                $body .= implode("", $entery_rows);
            }
            //exit();
        }

        if (!empty($this->sum['data']) && isset($this->sum['column'])) {
            $body .= "</tbody><tfoot><tr>";
            foreach ($this->headers as $header) {
                $column_total = isset($this->sum['data'][$this->sum['column']][$header]) ? $this->sum['data'][$this->sum['column']][$header] : "";
                $body .= "<td>$column_total</td>";
                /*
                if(!in_array($header, $this->header_offset)){ //performance !!! doesn't need
                    $body .= "<td>".$this->sum['data'][$this->sum['column']][$header]."</td>";
                }else
                    $body .= "<td></td>";
                */
            }

            $sum_colspan = $this->is3d ? "colspan='2'" : "";

            if (isset($this->sum['row']))
                $body .= "<td $sum_colspan>" . array_sum($this->sum['data'][$this->sum['column']]) . "</td>";

            $body .= "</tr></tfoot>";
        } else {
            $body .= "</tbody>";
        }

        $body .= "</table>";

        return $body;
    }

    private function retrieveBody()
    {
        $body = [];
        if (!$this->is3d) {
            $body[0][] = $this->retrievethirdlayer($this->data);
        } else {
            foreach ($this->data as $row => $data) {
                $row_data = [];
                //$row_span = count($data) > 1 ? "rowspan='".count($data)."'" : "";

                //$body .= "<tr><td $row_span>$row</td>";
                //$entery_rows = $this->retrievethirdlayer($data, $row."_");

                $row_data[] = $row;

                /**
                 * these 2 lines of code make all rows for a general row
                 * a general row can have more than one rows so because of HTML TABLE STRUCTURE first row shuld be complete
                 * so here we generate a complete row by getting the first element of all rows and remove <tr> open and close tags
                 * then we add rest of rows.
                 */
                //$body .= substr($entery_rows[0],4,-5);
                //unset($entery_rows[0]);

                if (!empty($this->sum['data']) && isset($this->sum['row'])) {
                    // $body .= "<td $row_span>" . $this->sum['data'][$this->sum['row']][$row] . "</td>";
                    $row_data[] = $this->sum['data'][$this->sum['row']][$row];
                }

                //$body .= "</tr>";
                //$body .= implode("", $entery_rows);
                $body[0][] = $row_data;
            }
        }

        if (!empty($this->sum['data']) && isset($this->sum['column'])) {
            //$body .= "</tbody><tfoot><tr>";
            foreach ($this->headers as $header) {
                $column_total = isset($this->sum['data'][$this->sum['column']][$header]) ? $this->sum['data'][$this->sum['column']][$header] : "";
                //$body .= "<td>$column_total</td>";
                $body['total'][] = $column_total;
                /*
                if(!in_array($header, $this->header_offset)){ //performance !!! doesn't need
                    $body .= "<td>".$this->sum['data'][$this->sum['column']][$header]."</td>";
                }else
                    $body .= "<td></td>";
                */
            }

            $sum_colspan = $this->is3d ? "colspan='2'" : "";

            if (isset($this->sum['row'])) {
                //$body .= "<td $sum_colspan>" . array_sum($this->sum['data'][$this->sum['column']]) . "</td>";
                $body['total'][] = array_sum($this->sum['data'][$this->sum['column']]);
            }

            //$body .= "</tr></tfoot>";
        } else {
            //$body .="</tbody>";
        }

        //$body .= "</table>";

        return $body;
    }


    /**
     * to generate each rows
     * if the data is in 3d Array the sum for first column would be ex: [location] and for the second column would be [location_mall]
     * so the addsum is the prefix for the second column
     *
     * if cases we have 2d Array this the addsum prefix will be null and sum array just have the first row ex: [location]
     *
     * @param array $thirdlayer
     * @param string $add_sum
     *
     * @return array rows
     */
    private function thirdlayer($thirdlayer, $add_sum = "")
    {
        //var_dump($thirdlayer);
        //exit();
        $rows = array();
        foreach ($thirdlayer as $row => $data) {
            if (isset($this->additinalHeaderRows[$row])) {
                $css = $this->additinalHeaderRows[$row];
            } else {
                $css = '';
            }
            //echo '<pre>';
            //var_dump($row);
            //var_dump($css);
            //var_dump($this->additinalHeaderRows);
            //echo '</pre>';
            //exit();
            //continue;
            if ($this->showRowNumber)
                $result = "<tr style='$css'><td style='$css'>$row</td>";
            else
                $result = "<tr style='$css'>";
            //ksort($data); //performance !!! doesn't need if use headers instead (which is more reliable)
            //foreach ($data as $key => $column)
            foreach ($this->data_headers as $header) {
                $column_css = '';
                if (isset($this->dataColumnStyle[$header])) {
                    $column_css = $this->dataColumnStyle[$header];
                }
                if (isset($this->barColumns[$header])) {
                    $bar_data = $this->barColumns[$header];
                    $perc = ($bar_data["total"]>0)?round(($data[$header] / $bar_data["total"]) * 100):0;
                    if ($css == '') {
                        $style = $bar_data["style"];
                        $txt = "<div style='width: $perc%; height: 30px; $style'>$data[$header]</div>";
                        $td_css = 'padding:0px; line-height:300%';
                    } else {
                        $txt = $data[$header];
                        $td_css = '';
                    }

                } else {
                    $txt = $data[$header];
                    $td_css = '';
                }
                $result .= "<td data-test = '' style='$css $column_css $td_css'>$txt</td>";
            }

            if (!empty($this->sum['data']) && isset($this->sum['row']))
                $result .= "<td style='$css'>" . $this->sum['data'][$this->sum['row']][$add_sum . $row] . "</td>";
            $result .= "</tr>";

            $rows[] = $result;
        }
        //exit();
        return $rows;
    }

    private function retrievethirdlayer($thirdlayer, $add_sum = "")
    {
        $rows = array();
        foreach ($thirdlayer as $row => $data) {
            $row_data = [];
            $row_data['data'][] = $row;
            //$result = "<tr><td>$row</td>";
            //ksort($data); //performance !!! doesn't need if use headers instead (which is more reliable)
            //foreach ($data as $key => $column)
            foreach ($this->data_headers as $header) {
                //$result .= "<td>$data[$header]</td>";
                $row_data['data'][] = $data[$header];
            }

            if (!empty($this->sum['data']) && isset($this->sum['row'])) {
                //$result .= "<td>" . $this->sum['data'][$this->sum['row']][$add_sum . $row] . "</td>";
                $row_data['sum'] = $this->sum['data'][$this->sum['row']][$add_sum . $row];
            }
            //$result .= "</tr>";

            $rows[] = $row_data;
        }
        return $rows;
    }
}

class TemplateClass
{

    public $counter = 0;

    public function createProperty($name, $value)
    {
        $attribute = 'report_' . md5($name);
        //$attribute = 'report_' . base64_encode($name);
        $this->{$attribute} = $value;

        $this->counter++;

        return $attribute;
    }
    public function getProperty($attribute){
        if(isset($this->$attribute)){
            //'$data->' . $attribute;
            //return true;
            return $this->$attribute;
        }else{
            return false;
        }
    }
}