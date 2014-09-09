<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		Angel Baev
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */
// ------------------------------------------------------------------------

/**
 * Form Validation Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Validation
 * @author		Angel Baev
 * @link		
 */

class CI_Excel_export {

    const CTYPE_INT = 'int';
    const CTYPE_FLOAT = 'float';
    const CTYPE_MONEY = 'money';
    const CTYPE_DOUBLE = 'double';

    const CTYPE_STRING = 'string';

    const CTYPE_BOOLEAN = 'boolean';

    const CTYPE_DATE = 'date';
    const CTYPE_DATETIME = 'datetime';
    

    private $content = '';
    protected $CI;
    protected $columns = array();
    protected $col_list = array();
    protected $records = array();
    public $error_msg = array();
    public $allowed_types = array();
    public $file_name = '';
    public $rec_num = 0;
    public $compress = TRUE;
    public $doc_excelxml_rdr = 'EXCEL';

    /**
     * Constructor
     *
     * @access	public
     */
    public function __construct($props = array()) {
        if (count($props) > 0) {
            $this->initialize($props);
        }

        log_message('debug', "Excel Export Class Initialized");
    }

    /**
     * Initialize preferences
     *
     * @param	array
     * @return	void
     */
    public function initialize($config = array()) {
        $defaults = array(
            'columns' => array(),
            'error_msg' => array(),
            'allowed_types' => array(),
            'file_name' => '',
            'rec_num' => 0,
            'compress' => TRUE,
            'doc_excelxml_rdr' => 'EXCEL'
        );


        foreach ($defaults as $key => $val) {
            if (isset($config[$key])) {
                $method = 'set_' . $key;
                if (method_exists($this, $method)) {
                    $this->$method($config[$key]);
                } else {
                    $this->$key = $config[$key];
                }
            } else {
                $this->$key = $val;
            }
        }
    }

    /**
     * Define excel column
     *
     * @param	$name
     * @param	$dbName
     * @param	$descr
     * @param	$name
     * @param	$type
     * @param	$default
     * @param	$params

     * @return	void
     */
    public function defineColumn($name, $dbName, $descr, $type, $default = NULL, $params = array()) {
        $this->columns[$dbName] = array(
            'dbName' => $dbName,
            'name' => $name,
            'descr' => $descr,
            'type' => $type,
            'default' => $default,
            'colWidth' => (isset($params['colWidth']) ? $params['colWidth'] : '80')
        );
        //
    }

    /**
     * prepare Excel XML
     *
     * @access	protected
     * @return	void
     */
    protected function _prepareExcelXML() {
        $this->content .=
                "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
                . "<?mso-application progid=\"Excel.Sheet\"?>\n"
                . "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n"
                . "xmlns:o=\"urn:schemas-microsoft-com:office:office\"\n"
                . "xmlns:x=\"urn:schemas-microsoft-com:office:excel\"\n"
                . "xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\"\n"
                . "xmlns:html=\"http://www.w3.org/TR/REC-html40\">\n"
                . "	<DocumentProperties xmlns=\"urn:schemas-microsoft-com:office:office\">\n"
                . "		<Author>Report Builder</Author>\n"
                . "		<LastAuthor>Report Builder</LastAuthor>\n"
                . "		<Created>" . date("Y-m-d") . "T" . date("H:i:s") . "Z</Created>\n"
                . "		<Company>AB-Labs.</Company>\n"
                . "		<Version>1.0</Version>\n"
                . "	</DocumentProperties>\n"
                . "	<ExcelWorkbook xmlns=\"urn:schemas-microsoft-com:office:excel\">\n"
                . "		<ProtectStructure>False</ProtectStructure>\n"
                . "		<ProtectWindows>False</ProtectWindows>\n"
                . "	</ExcelWorkbook>\n"
                . "	<Styles>\n"
                . "		<Style ss:ID=\"Default\" ss:Name=\"Normal\">\n"
                . "			<Alignment ss:Vertical=\"Top\"/>\n"
                . "			<Borders/>\n"
                . "			<Font x:CharSet=\"204\"/>\n"
                . "			<Interior/>\n"
                . "			<NumberFormat/>\n"
                . "			<Protection/>\n"
                . "		</Style>\n"
                . "		<Style ss:ID=\"rbsDate\">\n"
                . "			<NumberFormat ss:Format=\"Short Date\"/>\n"
                . "		</Style>\n"
                . "		<Style ss:ID=\"rbsSLT\">\n"
                . "			<Alignment ss:Horizontal=\"Left\" ss:Vertical=\"Top\"/>\n"
                . "		</Style>\n"
                . "		<Style ss:ID=\"rbsSLM\">\n"
                . "			<Alignment ss:Horizontal=\"Left\" ss:Vertical=\"Center\"/>\n"
                . "		</Style>\n"
                . "		<Style ss:ID=\"rbsSLB\">\n"
                . "			<Alignment ss:Horizontal=\"Left\" ss:Vertical=\"Bottom\"/>\n"
                . "		</Style>\n"
                . "		<Style ss:ID=\"rbsSCT\">\n"
                . "			<Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Top\"/>\n"
                . "		</Style>\n"
                . "		<Style ss:ID=\"rbsSCM\">\n"
                . "			<Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Center\"/>\n"
                . "		</Style>\n"
                . "		<Style ss:ID=\"rbsSCB\">\n"
                . "			<Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Bottom\"/>\n"
                . "		</Style>\n"
                . "		<Style ss:ID=\"rbsSRT\">\n"
                . "			<Alignment ss:Horizontal=\"Right\" ss:Vertical=\"Top\"/>\n"
                . "		</Style>\n"
                . "		<Style ss:ID=\"rbsSRM\">\n"
                . "			<Alignment ss:Horizontal=\"Right\" ss:Vertical=\"Center\"/>\n"
                . "		</Style>\n"
                . "		<Style ss:ID=\"rbsSRB\">\n"
                . "			<Alignment ss:Horizontal=\"Right\" ss:Vertical=\"Bottom\"/>\n"
                . "		</Style>\n"
                . "		<Style ss:ID=\"rbsBold\">\n"
                . "			<Font x:CharSet=\"204\" x:Family=\"Swiss\" ss:Bold=\"1\"/>\n"
                . "			<Interior/>\n"
                . "		</Style>\n"
                . "		<Style ss:ID=\"rbsBoldWW\" ss:Parent=\"rbsBold\">\n"
                . "			<Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Center\" ss:WrapText=\"1\"/>\n"
                . "		</Style>\n"
                . "		<Style ss:ID=\"rbsNumber\">\n"
                . "			<NumberFormat ss:Format=\"General Number\"/>\n"
                . "			<Alignment ss:Horizontal=\"Right\" ss:Vertical=\"Top\"/>\n"
                . "		</Style>\n"
                . "	</Styles>\n"
                . "	<Worksheet ss:Name=\"Export\">\n"
                . "		<Table>\n";
        
        foreach($this->col_list as $dbName) 
          $this->content .=
          "		<Column ss:AutoFitWidth=\"1\" />\n"; // ss:Width="61.5"
          // Generate Header Row
          $this->content .=
          "<Row ss:Index=\"".(++$this->rec_num)."\" ss:StyleID=\"rbsBoldWW\">\n";
        
        foreach($this->col_list as $col_index => $dbName) 
          $this->content .=
          "\t<Cell ss:Index=\"".($col_index + 1)."\">\n"
          ."\t\t<Data ss:Type=\"String\">".$this->escape_xml($this->columns[$dbName]["name"])."</Data>\n"
          ."\t</Cell>\n";
        $this->content .=
                "</Row>\n";
    }

    /**
     * done Excel XML
     *
     * @access	protected
     * @return	void
     */
    protected function _doneExcelXML() {
        $this->content .=
                "		</Table>\n" .
                "	</Worksheet>\n" .
                "</Workbook>\n";
    }

    protected function export() {
        $this->rec_num = 0;
        $this->col_list = array();
        foreach ($this->columns as $dbName => $item) {
            $this->col_list[] = $dbName;
        }
        $this->_prepareExcelXML();
        $this->getExportRecords();
        $this->_doneExcelXML();
    }

    protected function exportRecord($rec) {
         $this->rec_num++;
       
        $row = "<Row ss:Index=\"".$this->rec_num."\">\n";
        foreach($this->col_list as $col_index => $dbName) {
            $Style = ""; $Ticked = "";
            $val = isset($rec[$dbName])?$rec[$dbName]:"";
            switch($this->columns[$dbName]['type']) {
              case self::CTYPE_INT :  
              case self::CTYPE_FLOAT :  
              case self::CTYPE_BOOLEAN :  
              case self::CTYPE_MONEY :  
                $Style = "rbsNumber";
                if("" === $val) $val = NULL;
                $DataType = "Number";
                break;
              case self::CTYPE_DATE :  
              case self::CTYPE_DATETIME :  
                $Style = "rbsDate";
                if ($val) {
                    if ($this->columns[$dbName]['type'] == self::CTYPE_DATETIME){
                        $val = date('Y-m-d H:i:s', strtotime($val));
                    } else {
                        $val = date('Y-m-d', strtotime($val));
                    }
                } else {
                    $val = NULL;
                }
                $DataType = "DateTime";
                break;
              default :
                $Style = "rbsSLT";
                $DataType = "String";
                if((mb_substr($val,0,1) == "-") || (mb_substr($val,0,1) == "+")) 
                        $Ticked = " x:Ticked=\"1\"";
                $val = $this->escape_xml($val);
                break;
            }
            
            $row .=
            "\t<Cell ss:Index=\"".($col_index + 1)."\""
                    .($Style?" ss:StyleID=\"$Style\"":"")
                    .">\n"
            .(is_null($val)?"":"\t\t<Data ss:Type=\"".$DataType."\"".$Ticked.">".$val."</Data>\n")
            ."\t</Cell>\n";
        }

        $row .= "</Row>\n";
        $this->content .= $row;
        
    }
    
    protected function escape_xml($val) {
            $val = str_replace(
                "\t", "&#9;", str_replace(
                        "\n", "&#10;", str_replace(
                                "\r", "&#13;", str_replace(
                                        "\r\n", "&#10;", htmlspecialchars($val)
                                )
                        )
                )
        );
        return $val;
        
    }
    protected function _prepareHTTP() {
        if (!$this->compress) {
            // Turn off compression to prevent fake encoding
            if (ini_get("zlib.output_compression"))
                @ini_set("zlib.output_compression", "0");
            else if (ob_get_level()) {
                while (@ob_get_level() && @ob_end_clean());
                header("Content-Encoding:");
            }
        }

        header("Cache-control:no-store");
        header("Pragma:no-store");
//	header("Cache-control:no-cache");
//	header("Pragma:no-cache");
        header("Content-disposition:attachment; filename=\"".$this->getFileName()."\";");
        header("Content-type: application/vnd.ms-excel");
        header("Content-transfer-encoding:binary");
        
    }

    public function getFileName() {
       //$this->file_name 
       if ($this->doc_excelxml_rdr == 'EXCEL') {
        $ext = '.xls';  
       } else if ($this->doc_excelxml_rdr == 'OO') {
        $ext = '.xlsx';  
       } else {
        $ext = '.xls';  
       }
       $this->file_name = preg_replace('"\.(xlsx|xls)$"', $ext, $this->file_name);
       return $this->file_name; 
    }
    
    public function setExportRecords($rows = array()) {
      if(!is_array($rows)) $rows = array();  
      $this->records = $rows;  
    }

    public function getExportRecords() {
      foreach($this->records as $id => $rec) {
          $this->exportRecord($rec);
      }    
    }

    public function output() {
        $this->export();
        $this->_prepareHTTP();
        echo $this->content;
    }

    /**
     * Set an error message
     *
     * @param	string
     * @return	void
     */
    public function set_error($msg) {
        $CI = & get_instance();
        $CI->lang->load('Excel_export');

        if (is_array($msg)) {
            foreach ($msg as $val) {
                $msg = ($CI->lang->line($val) == FALSE) ? $val : $CI->lang->line($val);
                $this->error_msg[] = $msg;
                log_message('error', $msg);
            }
        } else {
            $msg = ($CI->lang->line($msg) == FALSE) ? $msg : $CI->lang->line($msg);
            $this->error_msg[] = $msg;
            log_message('error', $msg);
        }
    }

    /**
     * Display the error message
     *
     * @param	string
     * @param	string
     * @return	string
     */
    public function display_errors($open = '<p>', $close = '</p>') {
        $str = '';
        foreach ($this->error_msg as $val) {
            $str .= $open . $val . $close;
        }

        return $str;
    }

}
