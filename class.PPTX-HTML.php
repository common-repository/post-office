<?php
/**CLASS DOCXtoHTML Premium will convert a .docx file to (x)html.
 *
 * This class uses the dUnzip2 class from phpclasses.org and requires extension ZLib
 * This class can only handle 1 single file per instance.
 * This class might overrun the execution time limit.
 *
 * Variables that can be set:
 * @see *$docxPath
 * @see $paragraphs
 * @see *$image_max_width
 * @see $mediaDir
 * @see $imagePathPrefix
 *
 * Variables Returned by Class:
 * @see $status
 * @see $output
 * @see $error
 * @see $time
 */
class PPTXtoHTML {
    /**
     * @var String This is the path to the file that should be read
     * @since 1.0
     */
    var $pptxPath = "";
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
    var $output = "";
    /**
     * @var Int This is the maximum width of an image after the process
     * @since 1.0
     * @update 1.2
     */
    var $image_max_width = 0;
    /**
     * @var String The path to where the content is extracted
     * @since 1.0
     */
    var $content_folder = "";
    /**
     * @var String The current Status of the class
     * @since 1.0
     */
    var $status = "";
    /**
     * @var String The path to where the media files of the document should be extracted
     * @since 1.0
     */
    var $mediaDir = "";
    /**
     * @var String The value of this variable will be prefixed to the path of the image. This class will create a folder 2 levels up, inside an 'upload' folder and this value should go to there.
     * @since 1.0
     */
    var $imagePathPrefix = "";
    /**
     * @var Float The time the scipt took to complete the file parsing
     * @since 1.0
     */
    var $time = 0;
    /**
     * @var Array This contains the relationships of different elements inside the word document and is used to link to the correct image.
     * @since 1.1
     */
    var $rels = array();
    /**
     * @val String The error number generated and the meaning of the error
     * @since 1.0
     */
    var $error = NULL;
    /**
     * @val String This will contain the closing tag of a paragraph level opened tag that can't be specified explicitly
     * @since 1.1
     */
    var $tagclosep = "";
    /**
     * @val String This will contain the closing tag of a text opened tag that can't be specified explicitly
     * @since 1.1
     */
     var $tagcloset = "";
    /**
     * @val Bool SWhould a thumbnail be created as well as to keep the original image in the folder
     * @since 1.3
     */
     var $keepOriginalImage = false;
    /**
     * This function will set the status to Ready when the class is called. The Constructor Method.
     * @return Bool True when ready
     * @since 1.0
     */
    function __construct(){
        $this->status = "Ready";
        return true;
    }
    /**
     * This function call the Constructor Method
     * @return Bool True when ready
     * @since 1.0
     */
    function PPTXtoHTML(){
        return __construct();
    }
    /**
     * This function will initialize the process as well as handle the process automatically.
     * This requires that the vars be set to start
     * @return Bool True when successfully completed
     * @since 1.0
     * @modified 1.2.3
     */
    function Init(){
        return;
        global $PostOffice;
        if($this->extractRelXML()==false){
            $this->DeleteTemps();
            $PostOffice->__destruct();
            $this->error = "13. The file data could not be found or read.";
            return false;
        }
        if($this->extractMedia()==false){
            $this->DeleteTemps();
            $PostOffice->__destruct();
            $this->error = "14. The Media could not be found.";
            return false;
        }
        if($this->extractXML()==false){
            $this->DeleteTemps();
            $PostOffice->__destruct();
            $this->error = "15. The file data could not be found or read.";
            return false;
        }
        if($this->DeleteTemps()==false){
            $PostOffice->__destruct();
            $this->error = "16. The temporary files created during the process could not be deleted.
                The contents, however, might still have been extracted.";
            return false;
        }
        return true;
    }
    /**
     * This function handles the extraction of the XML building the Rels array
     * @return Bool True on success
     * @since 1.1
     * @modified 1.2.3
     */
    function extractRelXML(){
        $xmlFile = $this->tempDir."/word/_rels/document.xml.rels";
        $xml = file_get_contents($xmlFile);
        if($xml == false){
            return false;
        }
        $xml = mb_convert_encoding($xml, 'UTF-8', mb_detect_encoding($xml));
        $parser = xml_parser_create('UTF-8');
        $data = array();
        xml_parse_into_struct($parser, $xml, $data);
        foreach($data as $value){
            if($value['tag']=="RELATIONSHIP"){
                //it is an relationship tag, get the ID attr as well as the TARGET and (if set, the targetmode)set into var.
                if(isset($value['attributes']['TARGETMODE'])){
                    $this->rels[$value['attributes']['ID']] = array(0 => $value['attributes']['TARGET'], 3=> $value['attributes']['TARGETMODE']);
                } else {
                    $this->rels[$value['attributes']['ID']] = array(0 => $value['attributes']['TARGET']);
                }
            }
        }
        return true;
    }
    /**
     * This function handles the extraction of the Media
     * @return Bool True on success
     * @since 1.1
     * @modified 1.2.3
     */
    function extractMedia(){
        $wordFolder = $this->tempDir."/word/";
        if(!is_dir($wordFolder."media")){
            return true;
            //there are no images to extract
        }
        $this->getMediaFolder();
        $i = false;
        foreach($this->rels as $key => $value){
            if(strtolower(pathinfo($value[0],PATHINFO_EXTENSION))=="png" || strtolower(pathinfo($value[0],PATHINFO_EXTENSION))=="gif" || strtolower(pathinfo($value[0],PATHINFO_EXTENSION))=="jpg"
                || strtolower(pathinfo($value[0],PATHINFO_EXTENSION))=="jpeg"){
                //this really is an image that we are working with
                $fileType = strtolower(pathinfo($value[0],PATHINFO_EXTENSION));
                //set the file type so that the correct image creation function can be called
                if(is_file($wordFolder.$value[0])){
                    if($this->keepOriginalImage == true){
                        $image = $this->processImage($wordFolder.$value[0], $this->image_max_width);
                        $imageorr = $this->processImage($wordFolder.$value[0]);
                    } else {
                        $image = $this->processImage($wordFolder.$value[0], $this->image_max_width);
                        $imageorr = false;
                    }
                    if($image){
                        $i = true;//this have been resourceful, do not return false
                        //the image was successfully created, now write to file
                        $filename = pathinfo($value[0],PATHINFO_BASENAME);
                        if($fileType=="png"){
                            if(imagePng($image,$this->mediaDir."/".$filename,0,PNG_NO_FILTER)){
                                imagedestroy($image);
                                $this->rels[$key][1] = $this->mediaDir."/".$filename;
                            }
                        } elseif($fileType=="gif"){
                            if(imageGif($image,$this->mediaDir."/".$filename,0)){
                                imagedestroy($image);
                                $this->rels[$key][1] = $this->mediaDir."/".$filename;
                            }
                        } else {
                            if(imageJpeg($image,$this->mediaDir."/".$filename,100)){
                                imagedestroy($image);
                                $this->rels[$key][1] = $this->mediaDir."/".$filename;
                            }
                        }
                    }
                    if($imageorr){
                        $i = true;//this have been resourceful, do not return false
                        //the image was successfully created, now write to file
                        $pathinfo = pathinfo($value[0]);
                        $filename = $pathinfo['filename']."_big.".$pathinfo['extension'];
                        if($fileType=="png"){
                            if(imagePng($imageorr,$this->mediaDir."/".$filename,0,PNG_NO_FILTER)){
                                imagedestroy($imageorr);
                                $this->rels[$key][2] = $this->mediaDir."/".$filename;
                            }
                        } elseif($fileType=="gif"){
                            if(imageGif($imageorr,$this->mediaDir."/".$filename,0)){
                                imagedestroy($imageorr);
                                $this->rels[$key][2] = $this->mediaDir."/".$filename;
                            }
                        } else {
                            if(imageJpeg($imageorr,$this->mediaDir."/".$filename,100)){
                                imagedestroy($imageorr);
                                $this->rels[$key][2] = $this->mediaDir."/".$filename;
                            }
                        }
                    }
                }
            }
        }
        return $i;
    }
    /**
     * This function creates the folder that will contain the media after the move
     * @return Bool True on success
     * @since 1.0
     */
    function getMediaFolder(){
        if(empty($this->content_folder)){
            $mediaFolder = pathinfo($this->docxPath,PATHINFO_BASENAME);
            $ext = pathinfo($this->docxPath,PATHINFO_EXTENSION);
            $MediaFolder = strtolower(str_replace(".".$ext,"",str_replace(" ","-",$mediaFolder)));
            $this->mediaDir = "../../uploads/media/".$MediaFolder;
        } else {
            $this->mediaDir = "../../uploads/media/".$this->content_folder;
        }
        if($this->mkdir_p($this->mediaDir)){
            return true;
        } else {
            return false;
        }
    }
    /**
     * This function handles the image proccessing
     * @param String $url Path to the file to proccess
     * @param Int $thumb The maximum width of an proccessed image
     * @return String The binary of the image that was created
     * @since 1.0
     */
    function processImage($url, $thumb=0) {
        $tmp0 = imageCreateFromString(fread(fopen($url, "rb"), filesize( $url )));
        if ($tmp0) {
            if($thumb == 0) {
                $dim = Array ('w' => imageSx($tmp0), 'h' => imageSy($tmp0));
            } else {
                if(imagesx($tmp0)<=$thumb){
                    if (imageSy($tmp0) > imageSx($tmp0)){
                        $dim = Array ('w' => imageSx($tmp0), 'h' => imageSy($tmp0));
                    } else {
                        $dim = Array ('w' => imageSx($tmp0), 'h' => imageSy($tmp0));
                    }
                } else {
                    $dim = Array ('w' => $thumb, 'h' => round(imageSy($tmp0)*$thumb/imageSx($tmp0)));
                }
            }
            $tmp1 = imageCreateTrueColor ( $dim [ 'w' ], $dim [ 'h' ] );
            if ( imagecopyresized  ( $tmp1 , $tmp0, 0, 0, 0, 0, $dim [ 'w' ], $dim [ 'h' ], imageSx ( $tmp0 ), imageSy ( $tmp0 ) ) ) {
                imageDestroy ( $tmp0 );
                return $tmp1;
            } else {
                imageDestroy ( $tmp0 );
                imageDestroy ( $tmp1 );
                return $this -> null;
            }
        } else {
            return $this -> null;
        }
    }
    /**
     * This function handles the extraction of the XML file data used to construct the HTML
     * @return Bool True on success
     * @since 1.0
     * @modified 1.2.3
     */
    function extractXML(){
        $xmlFile = $this->tempDir."/word/document.xml";
        $xml = file_get_contents($xmlFile);
        if($xml == false){
            return false;
        }
        $xml = mb_convert_encoding($xml, 'UTF-8', mb_detect_encoding($xml));
        $parser = xml_parser_create('UTF-8');
        $data = array();
        xml_parse_into_struct($parser, $xml, $data);
        //echo "<pre>";
        //print_r($data);
        //echo "</pre>";
        $html4output = "";
        $i = 0;
        while(isset($data[$i])){
            $html4output .= $this->buildhtml($data[$i]);
            $i++;
        }
        $this->output = $html4output;
        $this->status = "Contents Extracted...";
        if(empty($html4output)){
            return false;
        }
        return true;
    }
    /**
     * This function do the actual building of the HTML data string
     * @param Array $data An array containing the data of the XML tag currently proccessed
     * @return string The corresponding HTML for the tag that was proccessed
     * @since 1.0 Modified: 1.2.3
     */
    function buildhtml($data){
        $return = "";
        if(!is_array($data)){
            return $return;
            //the value should be an array otherwise break;
        }
        if($data['type']=="open"){
            //if it is an open tag see if it should be parsed
            switch ($data['tag']) {
                case "W:P"://the paragrah begins
                    $return = "<p>";
                    break;
                case "W:TBL"://the table is initiated
                    $return = "<table border='1'>";
                    break;
                case "W:TR"://the table row is initiated
                    $return = "<tr>";
                    break;
                case "W:TC"://the table cell is initiated
                    $return = "<td>";
                    break;
                case "W:HYPERLINK"://the hyperlink is initiated
                    $rid = $data['attributes']['R:ID'];
                    $path = $this->rels[$rid][0];
                    $target = $this->rels[$rid][3];
                    //now determine which type of link it is
                    if(strtolower($target) == "external"){
                        //this is an external link to a website
                        $return = "<a href='".$path."'>";
                    } elseif(isset($data['attributes']['W:ANCHOR'])){
                        $return = "<a href='#".$data['attributes']['W:ANCHOR']."'>";
                    }
                    break;
                default:
                    break;
            }
        }elseif($data['type']=="complete"){
            //if it is an complete tag see if it should be parsed
            switch ($data['tag']) {
                case "W:T":
                    $return = $data['value'].$this->tagcloset;//return the text (add spaces after)
                    $this->tagcloset = "";
                    break;
                case "V:TEXTPATH":
                    $return = $data['attributes']['STRING'];//add word art text (this is also important)
                    break;
                case "A:BLIP"://the image data
                    $rid = $data['attributes']['R:EMBED'];
                    $imagepath = $this->rels[$rid][1];
                    $imagebigpath = $this->rels[$rid][2];
                    if($this->keepOriginalImage == true){
                        $return = "<a href='".$this->imagePathPrefix.$imagebigpath."' target='_blank' >
                            <img style='display:inline;' src='".$this->imagePathPrefix.$imagepath."' alt='' />
                            </a>";
                    } else {
                        $return = "<img style='display:inline;' src='".$this->imagePathPrefix.$imagepath."' alt='' />";
                    }
                    break;
                case "W:PSTYLE"://word styles used for headings etc.
                    if($data['attributes']['W:VAL'] == "Heading1"){
                        $return = "<h1>";
                        $this->tagclosep = "</h1>";
                    }elseif($data['attributes']['W:VAL'] == "Heading2"){
                        $return = "<h2>";
                        $this->tagclosep = "</h2>";
                    }elseif($data['attributes']['W:VAL'] == "Heading3"){
                        $return = "<h3>";
                        $this->tagclosep = "</h3>";
                    }
                    break;
                case "W:B"://word style for bold
                    if($this->tagcloset == "</strong>"){
                        break;
                    }
                    $return = "<strong>";//return the text (add spaces after)
                    $this->tagcloset = "</strong>";
                    break;
                case "W:I"://word style for italics
                    if($this->tagcloset == "</em>"){
                        break;
                    }
                    $return = "<em>";//return the text (add spaces after)
                    $this->tagcloset = "</em>";
                    break;
                case "W:U"://word style for underline
                    if($this->tagcloset == "</span>"){
                        break;
                    }
                    $return = "<span style='text-decoration:underline;'>";//return the text (add spaces after)
                    $this->tagcloset = "</span>";
                    break;
                case "W:STRIKE"://word style for strike-throughs
                    if($this->tagcloset == "</span>"){
                        break;
                    }
                    $return = "<span style='text-decoration:line-through;'>";//return the text (add spaces after)
                    $this->tagcloset = "</span>";
                    break;
                case "W:VERTALIGN"://word style for super- and subscripts
                    if($data['attributes']['W:VAL'] == "subscript"){
                        $return = "<sub>";
                        $this->tagcloset = "</sub>";
                    }elseif($data['attributes']['W:VAL'] == "superscript"){
                        $return = "<sup>";
                        $this->tagcloset = "</sup>";
                    }
                    break;
                case "W:BOOKMARKSTART"://word style for bookmarks/internal links
                    $return = "<a id='".$data['attributes']['W:NAME']."'></a>";
                    break;
                default:
                    break;
            }
        }elseif($data['type']=="close"){
            //if it is an close tag see if it should be parsed
            switch ($data['tag']) {
                case "W:P"://the paragraph ends
                    $return = $this->tagclosep."</p>";
                    $this->tagclosep = "";
                    break;
                case "W:TC"://the table cell ends
                    $return = "</td>";
                    break;
                case "W:TR"://the table row ends
                    $return = "</tr>";
                    break;
                case "W:TBL"://the table ends
                    $return = "</table>";
                    break;
                case "W:HYPERLINK"://the hyperlink ends
                    $return = "</a>";
                    break;
                default:
                    break;
            }
        }
        return $return;
    }
    /**
     * Recursive directory creation based on full path.
     * Will attempt to set permissions on folders.
     * @param string $target Full path to attempt to create.
     * @return bool Whether the path was created or not. True if path already exists.
     * @since 1.0
     */
    function mkdir_p( $target ) {
        // from php.net/mkdir user contributed notes
        $target = str_replace( '//', '/', $target );
        if ( file_exists( $target ) ){
            return @is_dir( $target );
        }
        // Attempting to create the directory may clutter up our display.
        if ( @mkdir( $target ) ) {
            $stat = @stat( dirname( $target ) );
            $dir_perms = $stat['mode'] & 0007777;  // Get the permission bits.
            @chmod( $target, $dir_perms );
            return true;
        } elseif ( is_dir( dirname( $target ) ) ) {
            return false;
        }
        // If the above failed, attempt to create the parent node, then try again.
        if ( ( $target != '/' ) && ( $this->mkdir_p( dirname( $target ) ) ) ){
            return $this->mkdir_p( $target );
        }
        return false;
    }
    /**
     * This function concludes the class by removing all te temporary files and folders as well as unsetting all variables not required
     * @return Bool True on success
     * @since 1.0
     */
    function DeleteTemps(){
        //this function will delete all the temp files except the word document
        //(.docx) itself. If this was uploaded it will be removed when the
        //script terminates
        if(is_dir($this->tempDir)){
            //the temp directory still exist
            $this->rrmdir($this->tempDir);
            unset($this->content_folder);
            unset($this->docxPath);
            unset($this->imagePathPrefix);
            unset($this->image_max_width);
            unset($this->tempDir);
            unset($this->rels);
            unset($this->tagclosep);
            unset($this->tagcloset);
            return true;
        }
        return false;
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
                    if (filetype($dir."/".$object) == "dir"){
                        $this->rrmdir($dir."/".$object);
                    } else {
                        unlink($dir."/".$object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
}
#EOF-----------