<?php

//Start by including all the needed functions for the script to continue
preg_match('|^(.*?/)(wp-content)/|i', str_replace('\\', '/', __FILE__), $_m);
require_once $_m[1] . 'wp-load.php';

//get the currently logged in user's cookies
if (is_ssl() && empty($_COOKIE[SECURE_AUTH_COOKIE]) && !empty($_REQUEST['auth_cookie']))
    $_COOKIE[SECURE_AUTH_COOKIE] = $_REQUEST['auth_cookie'];
elseif (empty($_COOKIE[AUTH_COOKIE]) && !empty($_REQUEST['auth_cookie']))
    $_COOKIE[AUTH_COOKIE] = $_REQUEST['auth_cookie'];
if (empty($_COOKIE[LOGGED_IN_COOKIE]) && !empty($_REQUEST['logged_in_cookie']))
    $_COOKIE[LOGGED_IN_COOKIE] = $_REQUEST['logged_in_cookie'];
unset($current_user);

//include the admin section for some other functions
require_once(ABSPATH . 'wp-admin/admin.php');

/**
 * Function to write to the logs file
 * @param String $result The result to be logged
 * @param String $file The filename to be logged
 * @param String $size The filesize to be logged
 */
function write_log($result, $file = "no-file", $size = "no-size") {
    $open = fopen("postoffice-log.csv", "a");
    $string = "\n" . str_replace("T", " ", str_replace(date("P"), "", date("c"))) . ",$result,$file,$size Bytes,PHP " . phpversion();
    $write = fwrite($open, $string);
    $close = fclose($open);
}

//load the settings
$PostOfficeSettings = get_option('postoffice_settings');
$_debug = $PostOfficeSettings['debug'];
if($_debug == "true"){
    error_reporting(-1);
}
$postoffice_max_image = $PostOfficeSettings['img_max_width'];
$tabbarSkin = $PostOfficeSettings['skin'];
$re = dirname($_SERVER['HTTP_REFERER']) . "/admin.php?page=postoffice_result";
$return = "Location:" . $re;
$returnlink = "<a href='$re'>Return</a>";

//make sure that the current user may publish posts (if not, terminate script)
if (!current_user_can('publish_posts')) {
    $result = "1. You are not authorised to upload files.";
    update_option('postoffice_last_result', $result);
    write_log($result);
    if ($_debug == "true") {
        echo $result . "<br />" . $returnlink;
        error_reporting(E_ALL ^ E_NOTICE);
    } else {
        header($return, true, 302);
        echo $result . "<br />" . $returnlink;
    }
    exit(0);
}
//Get the current session id of the user, and start the session
if (isset($_POST["PHPSESSID"])) {
    session_id($_POST["PHPSESSID"]);
} elseif (isset($_GET["PHPSESSID"])) {
    session_id($_GET["PHPSESSID"]);
}
session_start();

//get the max size that POST data may be and see it it were exceeded, if it did, fail with error message
$POST_MAX_SIZE = ini_get('post_max_size');
$unit = strtoupper(substr($POST_MAX_SIZE, -1));
$multiplier = $unit == 'M' ? 1048576 : ($unit == 'K' ? 1024 : ($unit == 'G' ? 1073741824 : 1));

if ((int) $_SERVER['CONTENT_LENGTH'] > $multiplier * (int) $POST_MAX_SIZE && $POST_MAX_SIZE) {
    $result = "2. POST exceeded maximum allowed size. Post size is: " . $_SERVER['CONTENT_LENGTH'] . " - Maximum allowed is: " . $multiplier * (int) $POST_MAX_SIZE;
    update_option('postoffice_last_result', $result);
    write_log($result);
    if ($_debug == "true") {
        echo $result . "<br />" . $returnlink;
        error_reporting(E_ALL ^ E_NOTICE);
    } else {
        header($return, true, 302);
        echo $result . "<br />" . $returnlink;
    }
    exit(0);
}
//The allowed extensions for this script
$extension_whitelist = array('docx', 'xlsx', 'pptx');
//define an array containing the possible errors when uploading a file
$uploadErrors = array(
    0 => "There is no error, the file uploaded with success.",
    1 => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
    2 => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
    3 => "The uploaded file was only partially uploaded.",
    4 => "No file was uploaded.",
    6 => "Missing a temporary folder."
);
//Test the following: a file is uploaded; no upload error have occured; the uploaded file is found; the file have a name
if (!isset($_FILES['postoffice_file'])) {
    $result = "3. No upload found in \$_FILES for 'postoffice_file'";
    update_option('postoffice_last_result', $result);
    write_log($result);
    if ($_debug == "true") {
        echo $result . "<br />" . $returnlink;
        error_reporting(E_ALL ^ E_NOTICE);
    } else {
        header($return, true, 302);
        echo $result . "<br />" . $returnlink;
    }
    exit(0);
} elseif (isset($_FILES['postoffice_file']["error"]) && $_FILES['postoffice_file']["error"] != 0) {
    $returndata = $uploadErrors[$_FILES['postoffice_file']["error"]];
    $result = "4. " . $returndata;
    update_option('postoffice_last_result', $result);
    write_log($result);
    if ($_debug == "true") {
        echo $result . "<br />" . $returnlink;
        error_reporting(E_ALL ^ E_NOTICE);
    } else {
        header($return, true, 302);
        echo $result . "<br />" . $returnlink;
    }
    exit(0);
} elseif (!isset($_FILES['postoffice_file']["tmp_name"]) || !@is_uploaded_file($_FILES['postoffice_file']["tmp_name"])) {
    $result = "5. Upload failed is_uploaded_file test.";
    update_option('postoffice_last_result', $result);
    write_log($result);
    if ($_debug == "true") {
        echo $result . "<br />" . $returnlink;
        error_reporting(E_ALL ^ E_NOTICE);
    } else {
        header($return, true, 302);
        echo $result . "<br />" . $returnlink;
    }
    exit(0);
} elseif (!isset($_FILES['postoffice_file']['name'])) {
    $result = "6. File has no name.";
    update_option('postoffice_last_result', $result);
    write_log($result);
    if ($_debug == "true") {
        echo $result . "<br />" . $returnlink;
        error_reporting(E_ALL ^ E_NOTICE);
    } else {
        header($return, true, 302);
        echo $result . "<br />" . $returnlink;
    }
    exit(0);
}

//check if the file is in the correct size limitations
$file_size = @filesize($_FILES['postoffice_file']["tmp_name"]);
$file_name = $_FILES['postoffice_file']['name'];
if (!$file_size) {
    $result = "7. File exceeds the maximum allowed size.";
    update_option('postoffice_last_result', $result);
    write_log($result, $file_name);
    if ($_debug == "true") {
        echo $result . "<br />" . $returnlink;
        error_reporting(E_ALL ^ E_NOTICE);
    } else {
        header($return, true, 302);
        echo $result . "<br />" . $returnlink;
    }
    exit(0);
}

if ($file_size <= 0) {
    $result = "8. File size outside allowed lower bound.";
    update_option('postoffice_last_result', $result);
    write_log($result, $file_name);
    if ($_debug == "true") {
        echo $result . "<br />" . $returnlink;
        error_reporting(E_ALL ^ E_NOTICE);
    } else {
        header($return, true, 302);
        echo $result . "<br />" . $returnlink;
    }
    exit(0);
}
//check if the file have the correct extension
$filepath = $_FILES['postoffice_file']['name'];
$tempfile = $_FILES['postoffice_file']['tmp_name'];
$path_info = pathinfo($filepath);
$file_extension = $path_info["extension"];
$is_valid_extension = false;
foreach ($extension_whitelist as $extension) {
    if (strtolower($file_extension) == $extension) {
        if ($extension == 'docx') {
            $use = "Word";
        } elseif ($extension == "xlsx") {
            $use = "Excel";
        } elseif ($extension == "pptx") {
            $use = "PowerPoint";
        }
        $is_valid_extension = true;
        break;
    }
}
if (!$is_valid_extension) {
    $result = "9. Invalid file extension.";
    update_option('postoffice_last_result', $result);
    write_log($result, $file_name, $file_size);
    if ($_debug == "true") {
        echo $result . "<br />" . $returnlink;
        error_reporting(E_ALL ^ E_NOTICE);
    } else {
        header($return, true, 302);
        echo $result . "<br />" . $returnlink;
    }
    exit(0);
}
//$use = "Excel";
//
//THIS IS WHERE THE PARSE CLASS WILL BE CALLED FROM
//

/**
 * This function start the timer of this script
 *
 * @global Float $timestart The time the function started, used to calculate script proccessing time
 * @return Bool True when the timer have started
 * @since 1.0
 */
function postoffice_timer_start() {
    global $timestart;
    $mtime = explode(' ', microtime());
    $mtime = $mtime[1] + $mtime[0];
    $timestart = $mtime;
    return true;
}

/**
 * This function calculates the difference between when the timer_start was called and the current time
 *
 * @global Float $timestart The time the timer have started
 * @global Float $timeend The time the timer is stopped
 * @param Int $display Should the result be displayed or returned
 * @param Int $precision The amount of decimals to return (after the comma)
 * @return Float Containing the Time since the timer start was called
 * @since 1.0
 */
function postoffice_timer_stop($display = 0, $precision = 3) { //if called like timer_stop(1), will echo $timetotal
    global $timestart, $timeend;
    $mtime = microtime();
    $mtime = explode(' ', $mtime);
    $mtime = $mtime[1] + $mtime[0];
    $timeend = $mtime;
    $timetotal = $timeend - $timestart;
    $r = number_format($timetotal, $precision);
    if ($display)
        echo $r;
    return $r;
}

//Get The Global Class
postoffice_timer_start();
require("./class.PostOffice.php");
global $PostOffice;
//Initiate The PostOffice Class for Extraction
$PostOffice = new PostOffice($tempfile,$_debug);
if(!$PostOffice){
    $result = "11. The files contents could not be extracted.";
    update_option('postoffice_last_result', $result);
    write_log($result, $file_name, $file_size);
    if ($_debug == "true") {
        echo $result . "<br />" . $returnlink;
        error_reporting(E_ALL ^ E_NOTICE);
    } else {
        header($return, true, 302);
        echo $result . "<br />" . $returnlink;
    }
    exit(0);
}
//initiate the correct class, define some variables and start the proccess
if ($use == "Word") {
    //Initiate The DOCX to HTML Class
    require("class.DOCX-HTML.php");
    $extract = new DOCXtoHTML();
    $extract->docxPath = $filepath;
    $extract->tempDir = $PostOffice->tempDir;
    $extract->content_folder = strtolower(str_replace("." . $path_info['extension'], "", str_replace(" ", "-", $path_info['basename'])));
    $extract->image_max_width = $postoffice_max_image;
    $extract->imagePathPrefix = plugins_url();
    if (isset($_POST['postoffice_original_images'])) {
        $extract->keepOriginalImage = ($_POST['postoffice_original_images'] == "true") ? true : false;
    } else {
        $extract->keepOriginalImage = false;
    }
    if (isset($_POST['postoffice_split'])) {
        $extract->split = ($_POST['postoffice_split'] == "true") ? true : false;
    } else {
        $extract->split = false;
    }
    if (isset($_POST['postoffice_colors'])) {
        $extract->allowColor = ($_POST['postoffice_colors'] == "true") ? true : false;
    } else {
        $extract->allowColor = false;
    }
    $extract->Init();

    //handle the output of the class and define variables needed for the WP post
    $post_data = $extract->output;
    $page_id = 0;
    foreach ($post_data as $key => $value) {
        if ($key == 0) {
            $post_title = $_POST['postoffice_post_title'];
            $post_name = $_POST['postoffice_post_name'] ? $_POST['postoffice_post_name'] : "";
            $post_date = $_POST['postoffice_post_date'];
            if ($post_date == "YYYY-mm-dd HH:ii:ss") {
                $post_date = "";
            }
            $post_id = $_POST['postoffice_post_id'];
            $post_tags = $_POST['postoffice_post_tags'];
            if (isset($_POST['postoffice_post_comments'])) {
                $post_comments = $_POST['postoffice_post_comments'] == "true" ? "open" : "closed";
            } else {
                $post_comments = "closed";
            }
            if (isset($_POST['postoffice_post_pings'])) {
                $post_pings = $_POST['postoffice_post_pings'] == "true" ? "open" : "closed";
            } else {
                $post_pings = "closed";
            }
            $post_status = $_POST['postoffice_post_status'];
            $post_type = $_POST['postoffice_post_type'];
            if ($post_type == "page") {
                $post_parent = 0;
            } else {
                $post_parent = 0;
            }
            $post_cats = $_POST['postoffice_post_cats'];
        } else {
            $post_title = $_POST['postoffice_post_title'] . " - Part " . ($key + 1);
            $post_name = $_POST['postoffice_post_name'] ? $_POST['postoffice_post_name'] . "-part-" . ($key + 1) : "";
            $post_id = 0;
            $post_tags = $_POST['postoffice_post_tags'];
            $post_comments = $_POST['postoffice_post_comments'] == "true" ? "open" : "closed";
            $post_pings = $_POST['postoffice_post_pings'] == "true" ? "open" : "closed";
            $post_status = $_POST['postoffice_post_status'];
            $post_type = $_POST['postoffice_post_type'];
            if ($post_type == "page") {
                $post_parent = $page_id; //here it should be the previous pages id one
            } else {
                $post_parent = 0;
            }
            $post_cats = $_POST['postoffice_post_cats'];
        }
        $my_post = array(
            'ID' => $post_id,
            'post_title' => $post_title,
            'post_name' => $post_name,
            'post_date' => $post_date,
            'tags_input' => $post_tags,
            'comment_status' => $post_comments,
            'ping_status' => $post_pings,
            'post_content' => $value,
            'post_status' => $post_status,
            'post_parent' => $post_parent,
            'post_type' => $post_type,
            'post_category' => $post_cats
        );
        $post = wp_insert_post($my_post, $_debug == "true" ? true : false);
        if ($post > 0 && $post_type == "page" && $page_id == 0) {
            //this should only happen if the post was succesful, it is a page and there was no page id change
            $page_id = $post;
        }
    }
} elseif ($use == "Excel") {
    //Initiate the XSLX to HTML Class
    require("class.XLSX-HTML.php");
    $extract = new XLSXtoHTML();
    $extract->tempDir = $PostOffice->tempDir;
    if (isset($_POST['postoffice_split'])) {
        $extract->split = ($_POST['postoffice_split'] == "true") ? true : false;
    } else {
        $extract->split = false;
    }
    if(isset($_POST['postoffice_round'])){
        $extract->round_int = $_POST['postoffice_round'];
    }
    $extract->tabbarPath = plugins_url() . "/post-office/";
    $extract->tabbarSkin = $tabbarSkin;
    $extract->Init();

    //handle the output of the class and define variables needed for the WP post
    $post_data = $extract->output;
    $post_title = $_POST['postoffice_post_title'];
    $post_name = $_POST['postoffice_post_name'] ? $_POST['postoffice_post_name'] : "";
    $post_date = $_POST['postoffice_post_date'];
    if ($post_date == "YYYY-mm-dd HH:ii:ss") {
        $post_date = "";
    }
    $post_id = $_POST['postoffice_post_id'];
    $post_tags = $_POST['postoffice_post_tags'];
    if (isset($_POST['postoffice_post_comments'])) {
        $post_comments = $_POST['postoffice_post_comments'] == "true" ? "open" : "closed";
    } else {
        $post_comments = "closed";
    }
    if (isset($_POST['postoffice_post_pings'])) {
        $post_pings = $_POST['postoffice_post_pings'] == "true" ? "open" : "closed";
    } else {
        $post_pings = "closed";
    }
    $post_status = $_POST['postoffice_post_status'];
    $post_type = $_POST['postoffice_post_type'];
    $post_cats = $_POST['postoffice_post_cats'];
    $page_id = 0;
    if(!$post_data){
        //if there are no content to add to the database, error
        $result = "14. There does not seem to be data in the file to create a document from.";
        update_option('postoffice_last_result', $result);
        write_log($result, $file_name, $file_size);
        if ($_debug == "true") {
            echo $result . "<br />" . $returnlink;
        } else {
            header($return, true, 302);
        echo $result . "<br />" . $returnlink;
        }
        exit(0);
    }
    // Create post object
    foreach ($post_data as $key => $value) {
        if ($post_type == "page") {
            $post_parent = $page_id; //here it should be the previous pages id one
        } else {
            $post_parent = 0;
        }
        $my_post = array(
            'ID' => $post_id,
            'post_title' => $post_title . ( $key == 0 ? "" : " - " . $key ),
            'post_name' => $post_name . str_replace(" ", "-", strtolower(( $key == 0 ? "" : "-" . $key))),
            'post_date' => $post_date,
            'tags_input' => $post_tags,
            'comment_status' => $post_comments,
            'ping_status' => $post_pings,
            'post_parent' => $post_parent,
            'post_content' => $value,
            'post_status' => $post_status,
            'post_type' => $post_type,
            'post_category' => $post_cats
        );
        // Insert the post into the database
        $post = wp_insert_post($my_post, $_debug == "true" ? true : false);
        if ($post > 0 && $post_type == "page" && $page_id == 0) {
            //this should only happen if the post was succesful, it is a page and there was no page id change
            $page_id = $post;
        }
    }
} elseif ($use == "PowerPoint") {
    //Initiate the PPTX to HTML Class
    $post == 0;
} else {
    //no use found, error
}
$time = postoffice_timer_stop(0);

if ($extract->error != NULL) {
    $result = $extract->error;
    update_option('postoffice_last_result', $result);
    write_log($result, $file_name, $file_size);
    if ($_debug == "true") {
        echo $result . "<br />" . $returnlink;
        error_reporting(E_ALL ^ E_NOTICE);
    } else {
        header($return, true, 302);
        echo $result . "<br />" . $returnlink;
    }
    exit(0);
} elseif (is_object($post) || $post == 0) {
    $result = "10.The post could not be inserted. An unknown error occurred.";
    if (is_object($post)) {
        $result .= " " . $post->get_error_message();
    }
    update_option('postoffice_last_result', $result);
    write_log($result, $file_name, $file_size);
    if ($_debug == "true") {
        echo $result . "<br />" . $returnlink;
        error_reporting(E_ALL ^ E_NOTICE);
    } else {
        header($return, true, 302);
        echo $result . "<br />" . $returnlink;
    }
    exit(0);
} else {
    $returndata = $time;
    $result = "Post successfuly inserted. Operation took " . $returndata . " seconds.";
    update_option('postoffice_last_result', $result);
    write_log($result, $file_name, $file_size);
    if ($_debug == "true") {
        echo $result . "<br />" . $returnlink;
        error_reporting(E_ALL ^ E_NOTICE);
    } else {
        header($return, true, 302);
        echo $result . "<br />" . $returnlink;
    }
    exit(0);
}
//
//END THE CALL TO PARSE
//