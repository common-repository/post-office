<?php
/*
 * This file will be used to create a new Language file inside the /lang/ folder.
 */

//Start by including all the needed functions for the script to continue
preg_match('|^(.*?/)(wp-content)/|i',str_replace('\\','/',__FILE__),$_m);
require_once $_m[1].'wp-load.php';

//get the currently logged in user's cookies
if(is_ssl() && empty($_COOKIE[SECURE_AUTH_COOKIE]) && !empty($_REQUEST['auth_cookie']))
    $_COOKIE[SECURE_AUTH_COOKIE] = $_REQUEST['auth_cookie'];
elseif(empty($_COOKIE[AUTH_COOKIE]) && !empty($_REQUEST['auth_cookie']))
    $_COOKIE[AUTH_COOKIE] = $_REQUEST['auth_cookie'];
if(empty($_COOKIE[LOGGED_IN_COOKIE]) && !empty($_REQUEST['logged_in_cookie']))
    $_COOKIE[LOGGED_IN_COOKIE] = $_REQUEST['logged_in_cookie'];
unset($current_user);

//include the admin section for some other functions
require_once(ABSPATH.'wp-admin/admin.php');

//make sure that the current user may publish posts (if not, terminate script)
if(!current_user_can('manage_options')){
    header("Location:".dirname($_SERVER['HTTP_REFERER'])."/admin.php?page=postoffice_languages",true,302);
    exit(0);
}

//Get the current session id of the user, and start the session
if(isset($_POST["PHPSESSID"])){
    session_id($_POST["PHPSESSID"]);
}elseif(isset($_GET["PHPSESSID"])){
    session_id($_GET["PHPSESSID"]);
}
session_start();

if(!isset($_POST['postoffice_filename'])){
    header("Location:".dirname($_SERVER['HTTP_REFERER'])."/admin.php?page=postoffice_languages",true,302);
    exit(0);
}
$filename = $_POST['postoffice_filename'].".php";
$fileContents = <<<'EOD'
<?php
/*
 * Language: English
 * LangIntCode: en-uk
 * Author: Jacotheron
 * Version: 1.0.0
 * Description: This file is the default language file for Post Office.
 */

/*
 * This is the default language file for Post Office. The language is Simple English.
 * Do not Edit 'langs/default.php' file file directly. The langs/default.php will be
 * replaced in future updates and changes might be lost. To create a custom language,
 * visit the Language page of Post Office and click the create new Language button.
 * Then Edit the new file from the Plugin Editor.
 *
 * When changing values, only change the right hand side of the '=>' arrow. The left
 * hand side is used to identify which one should be used.
 */

$postOfficeLang = array(
    /* --- Create Post Page --- */
    'new_post_page_title'       => 'Post Office Upload',

    'new_post_title'            => 'New Post Title',
    'new_post_title_desc'       => 'The name of the new Post/Page if the process was successful.<br />Required.',

    'new_post_slug'             => 'Post Slug',
    'new_post_slug_desc'        => 'A URL friendly version of the Post/Page Title. This will be automatically generated if left blank.',

    'new_post_state'            => 'Publish State',
    'new_post_state_desc'       => 'Post/Page\'s published state. This is the state of the Post/Page after the content is processed.',
    'new_post_state_draft'      => 'Draft',
    'new_post_state_publish'    => 'Publish',
    'new_post_state_future'     => 'Future',
    'new_post_state_pending'    => 'Pending',

    'new_post_type'             => 'Page or Post',
    'new_post_type_desc'        => 'Is the current document a Page or a Post.<br />Default to Post.',
    'new_post_type_post'        => 'Post',
    'new_post_type_page'        => 'Page',

    'new_post_id'               => 'Post/Page ID',
    'new_post_id_desc'          => 'The ID of a Post/Page to override. Useful if you want to extract a specific Word File again to replace the contents of the previous
                                    Post/Page.<br />
                                    0 will create a new post.',

    'new_post_date'             => 'Post/Page Date',
    'new_post_date_desc'        => 'The date of the Post/Page in the form specified. If specified, this will be used for the date of the Post/Page.<br />If Publish State above
                                    is set to `Future`, this is required.',

    'new_post_tags'             => 'Post/Page Tags',
    'new_post_tags_desc'        => 'Separate tags with commas. These tags will be automatically added to your post (and inside WordPress).',

    'new_post_com-ping'         => 'Comment &amp; Ping Status',
    'new_post_com-ping_desc'    => 'Should Comments and Pingbacks be allowed on this specific Post/Page.',
    'new_post_com-ping_ping'    => 'Allow trackbacks and pingbacks on this page.',
    'new_post_com-ping_com'     => 'Allow Comments',

    'new_post_ori-img'          => 'Keep Original Images',
    'new_post_ori-img_desc'     => 'Check this if you want thumbnails to be created (at the Max Image Width settings width) and to keep the original size image and link to it.
                                    Useful if you want a large image in the post (with a view Larger option).<br /><strong>WARNING: This may take up a lot of space on
                                    your Server.</strong>',
    'new_post_ori-img_text'     => 'Keep Original Images and Link.',

    'new_post_split'            => 'Split to Different Posts/Pages<br />Keep Colored Text',
    'new_post_split_desc'       => 'Splits the Word Document before Main Headings and Excel Spreadsheets between the sheets. Posts will follow each other in
                                    close succession, Pages will be child Pages of the first part.<br />When text\'s color is not default, should it be kept.
                                    <br /><strong>Warning:</strong> Some text may become invisible or hard to read depending on the background color.',
    'new_post_split_text'       => 'Split between Main Headings or Sheets.',
    'new_post_color_text'       => 'Keep document\'s text colors',
    
    'new_post_round'            => 'Rounds integers to the nth decimal',
    'new_post_round_desc'       => "Rounds a number (in an Excel spreadsheet) to the decimal (whole numbers only) defined here. To disable all rounding keep this on 'none'.",

    'new_post_cats'             => 'New Post Categories',
    'new_post_cats_desc'        => 'Select the Category or Categories that should contain your Post. Currently only 2 Categories are supported.<br />
                                    The first one is required. Default to First Category of your Blog',

    'new_post_file'             => 'Office File',
    'new_post_file_desc'        => 'Select the Office file on your computer that should be processed.<br /><strong>Your Maximum File Size (according to the server) is:',

    'new_post_submit'           => 'Create Post Now!',
    /* --- Results Page --- */
    'results_page_title'        => 'Post Office Results',
    'results_last_text'         => 'Last Upload Result:',
    'results_create_more'       => 'Create Another One',
    /* --- Logs Page --- */
    'logs_page_title'           => 'Post Office Logs',
    'logs_message'              => 'When experiencing problems, you can contact me through my website and copy this  into the email.
                                    This will allow me to identify your problem very quickly and then also solve it a lot faster.',
    'logs_col_nr'               => 'Nr:',
    'logs_col_date'             => 'Date:',
    'logs_col_result'           => 'Result:',
    'logs_col_file'             => 'File Name:',
    'logs_col_size'             => 'File Size:',
    'logs_col_php'              => 'PHP Version:',

    'logs_clear'                => 'Clear Logs',
    'logs_clear_desc'           => '* This can not be undone. Be very sure you want to clear the logs.<br />
                                    * Only Administrators can clear the logs.',
    /* --- Files Page --- */
    'files_page_title'          => 'Post Office Files',
    'files_no_files'            => 'No media files found. Have you uploaded a document with images inside?',
    'files_desc'                => 'Folders are created based on the name of the Office file.',
    'files_loc'                 => 'Files are located at:',
    /* --- Settings Page --- */
    'settings_page_title'       => 'Post Office Settings',

    'settings_failed'           => 'Post Office: Your settings could not be updated.',
    'settings_succeed'          => 'Post Office: Your settings have been saved successfully.',

    'settings_post_state'       => 'New Post Publish State',
    'settings_post_state_desc'  => 'Post\'s published state. This is the state of the post after the content is proccessed.<br />
                                    This Setting is over writable form the Upload form.',

    'settings_img_width'        => 'Max Image Width',
    'settings_img_width_desc'   => 'Maximum image width in pixels. This will set a limit for the images\' width. No image\'s width will exceed this
                                    limit, smaller images will not be resized.<br />Default 0 (no resize).',

    'settings_lang'             => 'Language',
    'settings_lang_desc'        => 'The Language that should be used in this plugin.<br />
                                    Default: Easy English.',

    'settings_skin'             => 'Post Office Tabbar Skin',
    'settings_skin_desc'        => 'Tabbar Skin Options allow you to change the looks of the tabs. <br />
                                    Please Note: Changes will only be visible on new Excel spreadsheets (processed after the change).',

    'settings_debug'            => 'Post Office Debug',
    'settings_debug_desc'       => 'Enable/Disable debug for Post Office.<br />
                                    Default: Off.',
    'settings_debug_on'         => 'On',
    'settings_debug_off'        => 'Off',

    'settings_save'             => 'Save Changes',
    /* --- Languages Page --- */
    'lang_page_title'           => 'Post Office Languages',
    'lang_nr'                   => 'Nr:',
    'lang_lang'                 => 'Language:',
    'lang_code'                 => 'Language Code:',
    'lang_desc'                 => 'Description:',
    'lang_author'               => 'Author:',
    'lang_version'              => 'Version:',
    'lang_file'                 => 'File Name:',
    'lang_new_file'             => 'Language File Name:',
    'lang_new_desc'             => '* Name the files is according to their International code for example:
                                    American English\'s code is <strong>en-US</strong> (First the language code, then the country code).<br/>
                                    * After a file is created, translate the file using the Plugin Edit page (select the `Post Office` as
                                    the plugin and select the filename form the list on the right hand side).',
    /* --- Help Page --- */
    /* The Help page can not be translated as very important information is contained within it which might get lost during translation. */
    /* --- End Changes Here --- */
    'end' => 'end'
);
EOD;

$langDir = dirname(__FILE__)."/langs/";

$open = fopen($langDir.$filename, 'x');
$write = fwrite($open, $fileContents);
$close = fclose($open);

header("Location:".dirname($_SERVER['HTTP_REFERER'])."/admin.php?page=postoffice_languages",true,302);
exit(0);
#EOF ----