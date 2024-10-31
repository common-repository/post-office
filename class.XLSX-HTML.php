<?php

/* * CLASS XLSXtoHTML will convert a .xlsx file to (x)html.
 *
 * This class uses the dUnzip2 class from phpclasses.org and requires extension ZLib
 * This class can only handle 1 single file per instance.
 * This class might overrun the execution time limit.
 *
 * Variables that can be set:
 * @see $tempDir
 *
 * Variables Returned by Class:
 * @see $output
 * @see $error
 */

class XLSXtoHTML {

    /**
     * @var String This is where the ziped contents will be extracted to to process
     * @since 1.0
     */
    var $tempDir = "";
    /**
     *
     * @var String This is the html data that is returned from this class
     * @since 1.0
     */
    var $output = array();
    /**
     * @val String The error number generated and the meaning of the error
     * @since 1.0
     */
    var $error = NULL;
    /**
     * @val Array this array will hold the workbook data
     * @since 1.1
     */
    var $workbook = array();
    /**
     * @val Array this array will hold the workbook data
     * @since 1.1
     */
    var $workbookrels = array();
    /**
     * @var Array This array will contain all the shared strings of the Excel file (all strings)
     * @since 1.1
     */
    var $sharedStrings = array();
    /**
     * @var Array This contains the whole workbooks sheets as elements inside the array.
     * @since 1.1
     */
    var $sheetData = array();
    /**
     * @val Bool Split the workbook into multiple posts/pages
     * @since 1.3
     */
    var $split = false;
    /**
     * @val Bool Split the workbook into multiple posts/pages
     * @since 1.3
     */
    var $round_int = "none";
    /**
     * @val Bool round the integers in the excel file
     * @since 1.3
     */
    var $tabbarPath = "";
    /**
     * @val Bool Split the workbook into multiple posts/pages
     * @since 1.3
     */
    var $tabbarSkin = "dhx_skyblue";

    function __construct() {
        return true;
    }

    /**
     * This function call the Constructor Method
     * @return Bool True when ready
     * @since 1.0
     */
    function XLSXtoHTML() {
        return __construct();
    }

    /**
     * This function will initialize the process as well as handle the process automatically.
     * This requires that the vars be set to start
     * @return Bool True when successfully completed
     * @since 1.0
     * @modified 1.2.3
     */
    function Init() {
        global $PostOffice;
        //first extract the workbook file
        //get the sheet names for the tabs etc.
        if ($this->extractWorkbook() == false) {
            $this->DeleteTemps();
            $PostOffice->__destruct();
            $this->error = "16. The file data could not be found or read.";
            return false;
        }
        //then extract the workbook rels
        //find the names of the sheet files
        if ($this->extractWorkbookRels() == false) {
            $this->DeleteTemps();
            $PostOffice->__destruct();
            $this->error = "17. The relationships between the workbook sheets could not be found.";
            return false;
        }
        //extract the shared strings
        if ($this->extractSharedStrings() == false) {
            $this->DeleteTemps();
            $PostOffice->__destruct();
            $this->error = "18. The text of the sheets could not be found and used.";
            return false;
        }
        //looping through each sheet...
        if ($this->BuildWorkbook() == false) {
            $this->DeleteTemps();
            $PostOffice->__destruct();
            $this->error = "19. The workbook appears to be empty.";
            return false;
        }
        //extract each sheet and process its contents
        //building the tables:
        if ($this->putTogether() == false) {
            $this->DeleteTemps();
            $PostOffice->__destruct();
            $this->error = "20. The sheets of the workbook appears to be empty.";
            return false;
        }
        //remove and clear memory and working dirs
        if ($this->DeleteTemps() == false) {
            $PostOffice->__destruct();
            $this->error = "21. The temporary values created during the process could not be cleared.";
            return false;
        }
        return true;
        //done
    }

    /**
     * This function handles the extraction of the XML Workbook array
     * @return Bool True on success
     * @since 1.1
     * @modified 1.2.3
     */
    function extractWorkbook() {
        $xmlFile = $this->tempDir . "/xl/workbook.xml";
        $xml = file_get_contents($xmlFile);
        if ($xml == false) {
            return false;
        }
        $xml = mb_convert_encoding($xml, 'UTF-8', mb_detect_encoding($xml));
        $parser = xml_parser_create('UTF-8');
        $data = array();
        xml_parse_into_struct($parser, $xml, $data);
        foreach ($data as $value) {
            if ($value['tag'] == "SHEET") {
                $this->workbook[] = $value;
            }
        }
        return true;
    }

    /**
     * This function handles the extraction of the XML Workbook Rels array
     * @return Bool True on success
     * @since 1.1
     * @modified 1.2.3
     */
    function extractWorkbookRels() {
        $xmlFile = $this->tempDir . "/xl/_rels/workbook.xml.rels";
        $xml = file_get_contents($xmlFile);
        if ($xml == false) {
            return false;
        }
        $xml = mb_convert_encoding($xml, 'UTF-8', mb_detect_encoding($xml));
        $parser = xml_parser_create('UTF-8');
        $data = array();
        xml_parse_into_struct($parser, $xml, $data);
        foreach ($data as $key => $value) {
            if ($value['tag'] == "RELATIONSHIP") {
                //it is an relationship tag, get the ID attr as well as the TARGET and (if set, the targetmode)set into var.
                $this->workbookrels[$value['attributes']['ID']] = array('target' => $value['attributes']['TARGET']);
            }
        }
        return true;
    }

    /**
     * This function handles the extraction of the XML Workbook array
     * @return Bool True on success
     * @since 1.1
     * @modified 1.2.3
     */
    function extractSharedStrings() {
        $xmlFile = $this->tempDir . "/xl/sharedStrings.xml";
        $xml = file_get_contents($xmlFile);
        if ($xml == false) {
            return false;
        }
        $xml = mb_convert_encoding($xml, 'UTF-8', mb_detect_encoding($xml));
        $parser = xml_parser_create('UTF-8');
        $data = array();
        xml_parse_into_struct($parser, $xml, $data);
        $i = 0; //var tag number
        $j = 0; //var string number
        $r = false; //concat more strings
        foreach ($data as $key => $value) {
            if ($value['tag'] == "T") {
                //enter this information into the SharedStrings array (it will be in correct order and then have the correct ID)
                if ($r == false) {
                    //it is a normal string
                    if (!isset($value['value'])) {
                        $this->sharedStrings[$j] = "&nbsp;";
                    } else {
                        $this->sharedStrings[$j] = $value['value'];
                    }
                } elseif (array_key_exists($j, $this->sharedStrings)) {
                    //see if the array key has already been created, if so, append to it
                    if (!isset($value['value'])) {
                        $this->sharedStrings[$j] .= "&nbsp;";
                    } else {
                        $this->sharedStrings[$j] .= $value['value'];
                    }
                } else {
                    //it don't, create it
                    if (!isset($value['value'])) {
                        $this->sharedStrings[$j] = "&nbsp;";
                    } else {
                        $this->sharedStrings[$j] = $value['value'];
                    }
                }
            } elseif ($value['tag'] == "SI" && $value['type'] == "open" && $data[$i + 1]['tag'] == "R") {
                $r = true;
            } elseif ($value['tag'] == "SI" && $value['type'] == "close") {
                $r = false;
                $j++;
            }
            $i++;
        }
        return true;
    }

    /**
     * This function handles the extraction of the XML file data used to construct the HTML
     * @return Bool True on success
     * @since 1.0
     * @modified 1.2.3
     */
    function BuildWorkbook() {
        //start looping through them
        foreach ($this->workbook as $key => $value) {
            $name = $value['attributes']['NAME'];
            //the name will be used to identify the sheet's data and will be the name of the tab containing the data
            foreach ($this->workbookrels as $k => $v) {
                if ($k == $value['attributes']['R:ID']) {
                    //make sure the relationship id is correct and then parse sheet
                    $this->sheetData[$name] = $this->ParseSheet($v['target']);
                }
            }
        }
        if ($this->sheetData == array()) {
            return false;
        }
        return true;
    }

    /**
     * Function ParseSheet wil parse a single sheet inside the workbook and return a string containing the data
     * @param String $sheetFile The string found in the rels document to locate the correct sheet file
     * @return String Containg the table of the sheet or empty string if sheet is empty or false if sheet does not exist
     */
    function ParseSheet($sheetFile) {
        if (!is_file($this->tempDir . "/xl/" . $sheetFile) || strpos("worksheets", $sheetFile) != 0) {
            return false;
            //the document does not exist or was not a sheet file
        }
        $xmlFile = $this->tempDir . "/xl/" . $sheetFile;
        $xml = file_get_contents($xmlFile);
        if ($xml == false) {
            return false;
        }
        $xml = mb_convert_encoding($xml, 'UTF-8', mb_detect_encoding($xml));
        $parser = xml_parser_create('UTF-8');
        $data = array();
        xml_parse_into_struct($parser, $xml, $data);
        $sheetdata = '';
        $i = 0;
        $rows = array();
        foreach ($data as $key => $value) {
            switch ($value['tag']) {
                case "SHEETDATA":
                    //start the table or end the table
                    if ($value['type'] == 'open') {
                        $sheetdata .= "<table border='1'><tbody>";
                    } elseif ($value['type'] == 'close') {
                        $sheetdata .= "</tbody></table>";
                    } elseif ($value['type'] == 'complete') {
                        $sheetdata .= "";
                    } elseif ($value['type'] == 'c-data') {
                        $sheetdata .= "";
                    }
                    break;
                case "ROW":
                    //start the row or end the row
                    if ($value['type'] == 'open') {
                        $sheetdata .= "<tr>";
                        $row = $value['attributes']['R'];
                        $rows[$row] = array();
                    } elseif ($value['type'] == 'close') {
                        $sheetdata .= "</tr>";
                    } elseif ($value['type'] == 'complete') {
                        $sheetdata .= "";
                    } elseif ($value['type'] == 'c-data') {
                        $sheetdata .= "";
                    }
                    break;
                case "C":
                    //start the cell or end the cell
                    if ($value['type'] == 'open') {
                        $col = str_replace($row, "", $value['attributes']['R']); //get the row nr and col nr, then strip the row nr from it
                        //now we have an alphabet character/string to use
                        //first determine what it is that we have
                        //if we have a `A`, it is the first column, just continue
                        if ($col == "A") {
                            //this is good
                        } else {
                            $alphabet = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
                            foreach ($alphabet as $k => $v) {
                                if ($col == $v) {
                                    $c = $k;
                                    break;
                                }
                            }
                            $pre = $alphabet[$c - 1];
                            //now test if it exist in the $rows array
                            if (in_array($pre, $rows[$row])) {
                                //it is good as it is created, just continue
                            } else {
                                //it is not created, create a blank now and then continue
                                $rows[$row][] = $pre;
                                $sheetdata .= "<td>&nbsp;</td>";
                            }
                        }
                        //if we have any other, determine if we have the one before it
                        if (isset($value['attributes']['T']) == "s") {
                            $gettext = true;
                        } else {
                            $gettext = false;
                        }
                        $sheetdata .= "<td>";
                        $rows[$row][] = $col;
                    } elseif ($value['type'] == 'close') {
                        $sheetdata .= "</td>";
                    } elseif ($value['type'] == 'complete') {
                        $col = str_replace($row, "", $value['attributes']['R']); //get the row nr and col nr, then strip the row nr from it
                        $sheetdata .= "<td>&nbsp;</td>";
                        $rows[$row][] = $col;
                    } elseif ($value['type'] == 'c-data') {
                        $sheetdata .= "";
                    }
                    break;
                case "V":
                    //start the content or end the content
                    if ($value['type'] == 'open') {
                        $sheetdata .= "";
                    } elseif ($value['type'] == 'close') {
                        $sheetdata .= "";
                    } elseif ($value['type'] == 'complete') {
                        if ($gettext) {
                            if (!array_key_exists('value', $value)) {
                                $sheetdata .= "&nbsp;";
                            } else {
                                $sheetdata .= $this->sharedStrings[$value['value']];
                            }
                            $gettext = false;
                        } else {
                            if($this->round_int == "none"){
                                $sheetdata .= $value['value'];
                            } else {
                                $sheetdata .= round((float) $value['value'], (int)$this->round_int);
                            }
                        }
                    } elseif ($value['type'] == 'c-data') {
                        if ($gettext) {
                            $sheetdata .= $this->sharedStrings[$value['value']];
                            $gettext = false;
                        } else {
                            $sheetdata .= $value['value'];
                        }
                    }
                    break;
                default:
                    break;
            }
            $i++;
        }
        return $sheetdata;
    }

    function putTogether() {
        if ($this->split) {
            $this->output = $this->sheetData;
            return true;
        }
        $return = "[postoffice_excel_open]";
        $retcopy = $return;
        //start the tab bar
        $ids = array();
        $i = 0;
        foreach ($this->sheetData as $name => $details) {
            if (!empty($details) || $details != false) {
                //it is not empy so it should be visible, now add a div for it
                $ids[] = array('id' => str_replace(" ", "-", strtolower($name)), 'name' => $name);
                //log the id that is used to pass to JS
                $return .= '
                    [postoffice_div id="' . str_replace(" ", "-", strtolower($name)) . '" name="' . $name . '"]
                        ' . $details . '
                    [/postoffice_div]';
                //concatenate the sheet data to the overall retrun
                $i++;
            }
        }
        //now test if $return changed. If it have not changed, something is wrong
        if ($return == $retcopy) {
            $this->output = false;
            return false;
        }
        $return .= "
                [postoffice_excel_close]";
        $this->output[] = $return;
        return true;
    }

    /**
     * This function concludes the class by removing all te temporary files and folders as well as unsetting all variables not required
     * @return Bool True on success
     * @since 1.0
     */
    function DeleteTemps() {
        //this function will delete all the temp files except the word document
        //(.xlsx) itself. If this was uploaded it will be removed when the
        //script terminates
        if (is_dir($this->tempDir)) {
            //the temp directory still exist
            $this->rrmdir($this->tempDir);
            unset($this->tempDir);
            unset($this->workbook);
            unset($this->workbookrels);
            unset($this->sharedStrings);
            unset($this->sheetData);
            return true;
        }
    }

    /**
     * This function will remove files and directories recursivly
     * @param String $dir The path to the folder to be removed
     */
    function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        $this->rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

}

#EOF-----------