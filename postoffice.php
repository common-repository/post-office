<?php
/*
  Plugin Name: Post Office
  Plugin URI: http://www.starsites.co.za/
  Description: This plugin will upload a docx file to extract all the contents (text/images) and then post the contents.
  Version: 1.0.12
  Author: Jaco Theron
  Author URI: http://www.starsites.co.za/
 */
/*
  Post Office WordPress Plugin
  Copyright (C) 2010  Jacotheron(Starsites) - info@starsites.co.za

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
// create custom plugin settings menu
add_action('admin_menu', 'postoffice_create_menu');
add_action('init', 'postoffice_init');

function postoffice_create_menu() {
    //create new top-level menu
    add_action('admin_init', 'postoffice_register_settings'); //call register settings function
    //add a few variables to make stuff easier
    $Name = "Post Office";
    $Slug = "postoffice";
    $Post = 'publish_posts';
    $Set = 'manage_options';
    //add al the menu items
    add_menu_page($Name, $Name, $Post, $Slug, $Slug . '_upload_page', plugins_url('/images/icon.png', __FILE__));
    add_submenu_page($Slug, $Name . ' Results', 'Last Result', $Post, $Slug . '_result', 'postoffice_results_page');
    add_submenu_page($Slug, $Name . ' Logs', 'Result Logs', $Set, $Slug . '_logs', 'postoffice_logs_page');
    add_submenu_page($Slug, $Name . ' Files', 'Files', $Set, $Slug . '_files', 'postoffice_files_page');
    add_submenu_page($Slug, $Name . ' Languages', 'Languages', $Set, $Slug . '_languages', 'postoffice_language_page');
    add_submenu_page($Slug, $Name . ' Settings', 'Settings', $Set, $Slug . '_settings', 'postoffice_settings_page');
    add_submenu_page($Slug, $Name . ' Help', 'Help', $Post, $Slug . '_help', 'postoffice_help_page');
}

function postoffice_register_settings() {
    //register our settings
    register_setting('postoffice_settings_group', 'postoffice_settings', 'postoffice_settings_validate');
    register_setting('postoffice_results_group', 'postoffice_last_result');
    //get the language ready
    postoffice_get_lang();
}

function postoffice_get_lang() {
    $postoffice_settings = get_option('postoffice_settings');
    if (isset($postoffice_settings['lang'])) {
        $cur_lang = $postoffice_settings['lang'];
        //language is set in settings
    } else {
        $cur_lang = "default.php";
        //revert to the default file
    }
    if (is_file(dirname(__FILE__) . "/langs/" . $cur_lang)) {
        //test if language file exist
        include ( dirname(__FILE__) . "/langs/" . $cur_lang );
        $GLOBALS['postOfficeLang'] = $postOfficeLang;
    } elseif (is_file(dirname(__FILE__) . "/langs/default.php")) {
        //test if the default language file exist
        include ( dirname(__FILE__) . "/langs/default.php" );
        $GLOBALS['postOfficeLang'] = $postOfficeLang;
    } else {
        //the default file have been removed, use the built-in language
        $GLOBALS['postOfficeLang'] = false;
    }
}

function postoffice_init() {
    add_shortcode('postoffice_excel_open', 'postoffice_excel_open');
    add_shortcode('postoffice_excel_close', 'postoffice_excel_close');
    add_shortcode('postoffice_div', 'postoffice_div');
    global $div_id, $ids;
    $div_id = 0;
    $ids = array();
}

function postoffice_excel_open($atts, $content=null, $code="") {
    $tabbarPath = plugins_url() . "/post-office/";
    $return = "
        <link rel='STYLESHEET' type='text/css' href='{$tabbarPath}tabbar-codebase/dhtmlxtabbar.css'>
        <script type='text/javascript' src='{$tabbarPath}tabbar-codebase/dhtmlxcommon.js'></script>
        <script type='text/javascript' src='{$tabbarPath}tabbar-codebase/dhtmlxtabbar.js'></script>
        <div id='postoffice_excel_tabs' >";
    if($content!=null){
        $return .= $content;
    }
    return $return;
}
function postoffice_excel_close($atts, $content=null, $code="") {
    $tabbarPath = plugins_url() . "/post-office/";
    $postoffice_settings = get_option('postoffice_settings');
    $tabbarSkin = "default";
    if (@$postoffice_settings['skin']) {
        $tabbarSkin = $postoffice_settings['skin'];
    }
    $return = "
            <script type='text/javascript'>
            tabbar = new dhtmlXTabBar('postoffice_excel_tabs', 'top');
            tabbar.setSkin('{$tabbarSkin}');
            tabbar.setImagePath('{$tabbarPath}tabbar-codebase/imgs/');
            tabbar.enableAutoSize(false, true);";
    global $ids;
    foreach ($ids as $id => $value) {
        $return .= "
                tabbar.addTab('s$id', '" . $value['name'] . "', '" . $value['size'] . "');
                tabbar.setContent('s$id', '" . $value['id'] . "');";
    }
    $return .="
            tabbar.setTabActive('s0');
            </script>
        </div>";
    if($content!=null){
        $return .= $content;
    }
    return $return;
}
function postoffice_div($atts, $content=null, $code="") {
    global $div_id;
    extract(shortcode_atts(array(
                "id" => 'excel' . $div_id,
                "name" => 'Tab-' . $div_id,
                "size" => '100'
                    ), $atts));
    global $ids;
    $ids[] = array('id' => $id, 'name' => $name, 'size' => $size);
    $div_id++;
    return "<div id='$id'>" . $content . "</div>";
}

function postoffice_settings_validate($set_arr) {
    //validate our settings
    foreach ($set_arr as $set => $val) {
        if ($set == "state") {
            //if setting a default post state, it should be one of the following
            if ($val == "draft" || $val == "publish" || $val == "future" || $val == "pending") {
                //it is good
            } else {
                //if not, use drat
                $set_arr['state'] = "draft";
            }
        } elseif ($set == "img_max_width") {
            //if settings the max img width, it should be an integer
            if (is_int((int) $val)) {
                //it is good
            } else {
                //if not, use 0
                $set_arr['img_max_width'] = 0;
            }
        } elseif ($set == "lang") {
            if (empty($val)) {
                //if setting language, make sure its not empty
                $set_arr['lang'] = "default.php";
            } else {
                if (is_file(dirname(__FILE__) . "/langs/" . $set_arr['lang'])) {
                    //it is good, as the file exists
                } else {
                    //the language set does not exist anymore, revert to default
                    $set_arr['lang'] = "default.php";
                }
            }
        } elseif ($set == "skin") {
            if (empty($val)) {
                //if setting language, make sure its not empty
                $set_arr['skin'] = "default";
            } else {
                if (is_dir(dirname(__FILE__) . "/tabbar-codebase/imgs/" . $set_arr['skin'])) {
                    //it is good, as the folder exists
                } else {
                    //the language set does not exist anymore, revert to default
                    $set_arr['lang'] = "default";
                }
            }
        } elseif ($set == "debug") {
            //if setting debugging it should either be true or false, default to false if not true
            if (empty($val)) {
                $set_arr['debug'] = "false";
            } elseif ($set_arr['debug'] != "false" && $set_arr['debug'] != "true") {
                $set_arr['debug'] = "false";
            } else {
                //it is good
            }
        } else {
            unset($set_arr[$set]);
            //if it is not any of the previous stuff, remove it. It might be bad.
        }
    }
    return $set_arr;
    //enter the information into the database
}

function postoffice_upload_page() {
    global $postOfficeLang;
    //first get the language ready, then show the upload page
    ?>
    <div class="wrap">
        <h2 style="float:left;"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_page_title'] : "Post Office Upload"; ?></h2>
        <p style="float:right;"><a href="#" onclick="toggle_optional();">Show/Hide Optional Fields</a></p>
                        <?php postoffice_ready(); ?>
        <form method="post" action="<?php echo plugins_url() . "/post-office/upload.php"; ?>" enctype='multipart/form-data'>
    <?php
    settings_fields('postoffice_settings_group');
    $postoffice_settings = get_option('postoffice_settings');
    ?>
            <table class="form-table">
                <tr valign="top" id="postoffice_title_cont">
                    <th scope="row" style="">
                            <?php echo ($postOfficeLang) ? $postOfficeLang['new_post_title'] : "New Post Title"; ?>
                        <span style="color:red;">*</span>
                    </th>
                    <td style="">
                        <input  id="post_title" type="text" size="30" name="postoffice_post_title" value=""
                                style="font-size:1.7em; line-height: 100%; outline: medium none; padding: 3px 4px; width:300px;" />
                    </td>
                    <td style="">
                        <span class="description">
                            <?php
                            echo ($postOfficeLang) ?
                                    $postOfficeLang['new_post_title_desc'] :
                                    "The name of the new Post/Page if the proccess was succesful.<br />Required.";
                            ?>
                        </span>
                    </td>
                </tr>
                <tr valign="top" id="postoffice_slug_cont" style="display:none;">
                    <th scope="row"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_slug'] : "Post Slug"; ?></th>
                    <td><input type="text" size="30" name="postoffice_post_name" value="" style="padding: 3px 4px; width:300px;" /></td>
                    <td><span class="description">
                                <?php echo ($postOfficeLang) ? $postOfficeLang['new_post_slug_desc'] :
                                        "A URL friendly version of the Post/Page Title. This will be automatically generated if left blank."; ?>
                        </span></td>
                </tr>
                <tr valign="top" id="postoffice_state_cont" style="display:none;">
                    <th scope="row"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_state'] : "Publish State"; ?></th>
                                <?php $post_publish = $postoffice_settings['state']; ?>
                    <td><select name="postoffice_post_status" style="width:100px;" >
                            <option value="draft" <?php echo ($post_publish == "draft") ? 'selected="selected"' : "" ?>>
    <?php echo ($postOfficeLang) ? $postOfficeLang['new_post_state_draft'] : "Draft"; ?>
                            </option>
                            <option value="publish" <?php echo ($post_publish == "publish") ? 'selected="selected"' : "" ?>>
    <?php echo ($postOfficeLang) ? $postOfficeLang['new_post_state_publish'] : "Publish"; ?>
                            </option>
                            <option value="pending" <?php echo ($post_publish == "pending") ? 'selected="selected"' : "" ?>>
    <?php echo ($postOfficeLang) ? $postOfficeLang['new_post_state_pending'] : "Pending"; ?>
                            </option>
                            <option value="future" <?php echo ($post_publish == "future") ? 'selected="selected"' : "" ?>>
    <?php echo ($postOfficeLang) ? $postOfficeLang['new_post_state_future'] : "Future"; ?>
                            </option>
                        </select></td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_state_desc'] :
            "Post/Page's published state. This is the state of the Post/Page after the content is processed."; ?>
                        </span></td>
                </tr>
                <tr valign="top" id="postoffice_type_cont">
                    <th scope="row"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_type'] : "Page or Post"; ?>
                        <span style="color:red;">*</span></th>
                    <td><select name="postoffice_post_type" style="width:100px;" >
                            <option value="post"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_type_post'] : "Post"; ?></option>
                            <option value="page"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_type_page'] : "Page"; ?></option>
                        </select></td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_type_desc'] :
                                "Is the current document a Page or a Post.<br />Default to Post."; ?></span></td>
                </tr>
                <tr valign="top" id="postoffice_id_cont" style="display:none;">
                    <th scope="row"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_id'] : "Post/Page ID"; ?></th>
                    <td><input type="text" size="30" name="postoffice_post_id" value="0" style="padding: 3px 4px; width:300px;" /></td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_id_desc'] :
                                "The ID of a Post/Page to override. Usefull if you want to extract a specific Word File again to replace the
                        contents of the previous Post/Page.<br />0 will create a new post."; ?></span></td>
                </tr>
                <tr valign="top" id="postoffice_date_cont" style="display:none;">
                    <th scope="row"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_date'] : "Post/Page Date"; ?></th>
                    <td><input type="text" size="30" name="postoffice_post_date" value="YYYY-mm-dd HH:ii:ss" style="padding: 3px 4px; width:300px;" /></td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_date_desc'] :
                                "The date of the Po/Pagest in the form specified. If specified, this will be used for the date of the Post/Page.
                        <br />If Publish State above is set to `Future`, this is required."; ?></span></td>
                </tr>
                <tr valign="top" id="postoffice_tags_cont" style="display:none;">
                    <th scope="row"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_tags'] : "Post/Page Tags"; ?></th>
                    <td><input type="text" size="30" name="postoffice_post_tags" value="" style="padding: 3px 4px; width:300px;" /></td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_tags_desc'] :
                                   "Separate tags with commas. These tags will be automatically added to your post (and inside WordPress)."; ?></span></td>
                </tr>
                <tr valign="top" id="postoffice_com-ping_cont" style="display:none;">
                    <th scope="row"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_com-ping'] : "Comment &amp; Ping Status"; ?></th>
                    <td><input type="checkbox" size="30" name="postoffice_post_comments" value="true" style="" <?php echo (get_option('default_comment_status') == "open" ? "checked='checked'" : ""); ?> />
                        <?php echo ($postOfficeLang) ? $postOfficeLang['new_post_com-ping_com'] : "Allow Comments"; ?><br />
                        <input type="checkbox" size="30" name="postoffice_post_pings" value="true" style="" <?php echo (get_option('default_ping_status') == "open" ? "checked='checked'" : ""); ?> />
                            <?php echo ($postOfficeLang) ? $postOfficeLang['new_post_com-ping_ping'] : "Allow trackbacks and pingbacks on this page."; ?>
                    </td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_com-ping_desc'] :
                                    "Should Comments and Pingbacks be allowed on this specific Post/Page."; ?></span></td>
                </tr>
                <tr valign="top" id="postoffice_ori_cont" style="display:none;">
                    <th scope="row"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_ori-img'] : "Keep Original Images"; ?></th>
                    <td><input type="checkbox" name="postoffice_original_images" value="true" style="" />
                        <?php echo ($postOfficeLang) ? $postOfficeLang['new_post_ori-img_text'] : "Keep Original Images and Link."; ?></td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_ori-img_desc'] :
                                "Check this if you want thumbnails to be created (at the Max Image Width settings width) and to keep the original
                    size image and link to it. Useful if you want a large image in the post (with a view Larger option).<br />
                    <strong>WARNING: This may take up a lot of space on your Server.</strong>"; ?>
                        </span></td>
                </tr>
                <tr valign="top" id="postoffice_split_cont" style="display:none;">
                    <th scope="row"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_split'] : "Split to Different Posts/Pages<br />Keep Colored Text"; ?></th>
                    <td><input type="checkbox" name="postoffice_split" value="true" style="" />
    <?php echo ($postOfficeLang) ? $postOfficeLang['new_post_split_text'] : "Split between Main Headings or Sheets."; ?><br />
                        <input type="checkbox" name="postoffice_colors" value="true" style="" />
    <?php echo ($postOfficeLang) ? $postOfficeLang['new_post_color_text'] : "Keep Colored Text"; ?>
                    </td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_split_desc'] :
            "Splits the Word Document before Main Headings and Excel Spreadsheets between the sheets. Posts will follow eachother in
                    close succession, Pages will be child Pages of the first part.<br />When text's color is not default, should it be kept.
                    <br /><strong>Warning:</strong> Some text may become invisible or hard to read depending on the background color."; ?>
                        </span></td>
                </tr>
                <tr valign="top" id="postoffice_round_cont" style="display:none;">
                    <th scope="row"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_round'] : "Rounds integers to the nth decimal"; ?></th>
                    <td><input type="text" name="postoffice_round" value="none" style="" /></td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_round_desc'] :
            "Rounds a number (in an Excel spreadsheet) to the decimal (whole numbers only) defined here. To disable all rounding keep this on 'none'."; ?>
                        </span></td>
                </tr>
                <tr valign="top" id="postoffice_cats_cont">
                    <th scope="row"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_cats'] : "New Post Categories"; ?>
                        <span style="color:red;">*</span></th>
                    <td><?php
                    wp_dropdown_categories(array(
                        'hide_empty' => 0,
                        'name' => 'postoffice_post_cats[]',
                        'id' => 'postoffice_post_cat1',
                        'selected' => $category->parent,
                        'hierarchical' => true));
    ?>
    <?php
    wp_dropdown_categories(array(
        'hide_empty' => 0,
        'name' => 'postoffice_post_cats[]',
        'id' => 'postoffice_post_cat2',
        'selected' => $category->parent,
        'hierarchical' => true,
        'show_option_none' => '-- None --'));
    ?></td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_cats_desc'] :
                                "Select the Category or Categories that should contain your Post. Currently only 2 Categories are supported.<br />
                    The first one is required. Default to First Category of your Blog"; ?></span></td>
                </tr>
                <tr valign="top" id="postoffice_file_cont">
                    <th scope="row"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_file'] : "Office File"; ?>
                        <span style="color:red;">*</span></th>
                    <td><input type="file" name="postoffice_file" size="30" value="" style="line-height: 100%; outline: medium none; padding: 3px 4px; width:300px;" /></td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['new_post_file_desc'] :
                                "Select the .docx file on your computer that should be processed.<br /><strong>Your Maximum File Size (according to the server)
                        is:"; ?>  <?php
                        $UPLOAD_MAX_SIZE = ini_get('upload_max_filesize');
                        $unit = strtoupper(substr($UPLOAD_MAX_SIZE, -1));
                        if (!is_int($unit)) {
                            echo $UPLOAD_MAX_SIZE;
                        } else {
                            $gigabytes = $UPLOAD_MAX_SIZE / 1073741824;
                            $megabytes = $UPLOAD_MAX_SIZE / 1048576;
                            $kilobytes = $UPLOAD_MAX_SIZE / 1024;
                            $bytes = $UPLOAD_MAX_SIZE;
                            if ($gigabytes > 1) {
                                echo $gigabytes . "G";
                            } elseif ($megabytes > 1) {
                                echo $megabytes . "M";
                            } elseif ($kilobytes > 1) {
                                echo $kilobytes . "K";
                            } else {
                                echo $bytes;
                            }
                        }
                        ?></strong></span></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" class="button-primary" value="<?php echo ($postOfficeLang) ?
                                $postOfficeLang['new_post_submit'] : "Create Post Now!"; ?>" /></p>
        </form>
        <script type="text/javascript">
            function toggle_optional(){
                //get all the elements to show/hide
                var slug  = document.getElementById( 'postoffice_slug_cont' );
                var id    = document.getElementById( 'postoffice_id_cont' );
                var state = document.getElementById( 'postoffice_state_cont' );
                var date  = document.getElementById( 'postoffice_date_cont' );
                var tags  = document.getElementById( 'postoffice_tags_cont' );
                var com   = document.getElementById( 'postoffice_com-ping_cont' );
                var ori   = document.getElementById( 'postoffice_ori_cont' );
                var split = document.getElementById( 'postoffice_split_cont' );
                var round = document.getElementById( 'postoffice_round_cont' );

                if( slug.style.display != 'none' ){
                    slug.style.display = 'none';
                    id.style.display = 'none';
                    state.style.display = 'none';
                    date.style.display = 'none';
                    tags.style.display = 'none';
                    com.style.display = 'none';
                    ori.style.display = 'none';
                    split.style.display = 'none';
                    round.style.display = 'none';
                } else {
                    slug.style.display = '';
                    id.style.display = '';
                    state.style.display = '';
                    date.style.display = '';
                    tags.style.display = '';
                    com.style.display = '';
                    ori.style.display = '';
                    split.style.display = '';
                    round.style.display = '';
                }
                return false;
            }
            function toggle_element( element ){
                element.style.display = ( element.style.display != 'none' ? 'none' : '' );
                return false;
            }
        </script>
    </div>
    <?php
}

function postoffice_results_page() {
    global $postOfficeLang;
    //first get the language ready, then show the results page
    $data = get_option('postoffice_last_result');
    ?>
    <div class="wrap">
        <h2><?php echo ($postOfficeLang) ? $postOfficeLang['results_page_title'] : "Post Offce Results"; ?></h2>
        <table class="widefat fixed">
            <tr valign="top">
                <th scope="row" style="width:150px;">
    <?php echo ($postOfficeLang) ? $postOfficeLang['results_last_text'] : "Last Upload Result:"; ?></th>
                <td><?php echo $data; ?></td>
            </tr>
        </table>
        <p>
            <span style="padding-top:10px;" >
                <a href="./admin.php?page=postoffice" class="button-primary" >
    <?php echo ($postOfficeLang) ? $postOfficeLang['results_create_more'] : "Create Another One!"; ?>
                </a>
            </span>
        </p>
    </div>
    <?php
}

function postoffice_logs_page() {
    global $postOfficeLang;
    //first get the language ready, get the logs file and read it's contents then show the logs page
    $dir = dirname(__FILE__);
    $length = strlen($dir);
    if ($dir[$length - 1] != "/") {
        $open = fopen(dirname(__FILE__) . "/postoffice-log.csv", "r");
    } else {
        $open = fopen(dirname(__FILE__) . "./postoffice-log.csv", "r");
    }
    $contentarr = array();
    while (!feof($open)) {
        $contentarr = array_merge($contentarr, array(fgetcsv($open)));
    }
    $close = fclose($open);
    ?>
    <div class="wrap">
        <h2><?php echo ($postOfficeLang) ? $postOfficeLang['logs_page_title'] : "Post Office Logs"; ?></h2>
        <div id="message" class="updated"><p><strong>
    <?php echo ($postOfficeLang) ? $postOfficeLang['logs_message'] :
            "When experiencing problems, you can contact me through my website and copy this  into the email.
            This will allow me to identify your problem very quickly and then also solve it a lot faster."; ?></strong></p></div>
        <table class="widefat fixed" style="margin-bottom:4px;">
            <thead valign="top"><tr>
                    <th scope="col" style="width:40px;"><?php echo ($postOfficeLang) ? $postOfficeLang['logs_col_nr'] : "Nr:"; ?></th>
                    <th scope="col" style="width:150px;"><?php echo ($postOfficeLang) ? $postOfficeLang['logs_col_date'] : "Date:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['logs_col_result'] : "Result:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['logs_col_file'] : "File Name:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['logs_col_size'] : "File Size:"; ?></th>
                    <th scope="col" style="max-width:100px;"><?php echo ($postOfficeLang) ? $postOfficeLang['logs_col_php'] : "PHP Version:"; ?></th>
                </tr>
            </thead>
            <tfoot valign="top"><tr>
                    <th scope="col" style="width:40px;"><?php echo ($postOfficeLang) ? $postOfficeLang['logs_col_nr'] : "Nr:"; ?></th>
                    <th scope="col" style="width:150px;"><?php echo ($postOfficeLang) ? $postOfficeLang['logs_col_date'] : "Date:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['logs_col_result'] : "Result:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['logs_col_file'] : "File Name:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['logs_col_size'] : "File Size:"; ?></th>
                    <th scope="col" style="max-width:100px;"><?php echo ($postOfficeLang) ? $postOfficeLang['logs_col_php'] : "PHP Version:"; ?></th>
                </tr></tfoot>
            <?php
            if (array_key_exists(1, $contentarr)) {
                foreach ($contentarr as $key => $value) {
                    if (is_array($value)) {
                        if ($key != 0) {
                            echo "<tbody><tr>";
                            echo "<td>" . $key . "</td>";
                            echo "<td>" . $value[0] . "</td>";
                            echo "<td>" . $value[1] . "</td>";
                            echo "<td>" . $value[2] . "</td>";
                            echo "<td>" . $value[3] . "</td>";
                            echo "<td>" . $value[4] . "</td>";
                            echo "</tr></tbody>";
                        }
                    }
                }
            } else {
                echo "<tbody><tr>";
                echo "<td>&nbsp;</td>";
                echo "<td>&nbsp;</td>";
                echo "<td>&nbsp;</td>";
                echo "<td>&nbsp;</td>";
                echo "<td>&nbsp;</td>";
                echo "<td>&nbsp;</td>";
                echo "</tr></tbody>";
            }
            ?>
        </table>
    <?php
    if (current_user_can('manage_options')) {
        ?>
            <span style="padding-top:10px;" >
                <a href="<?php echo plugins_url() . "/post-office/clearLogs.php"; ?>" class="button-primary" >
        <?php echo ($postOfficeLang) ? $postOfficeLang['logs_clear'] : "Clear Logs"; ?>
                </a><br /><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['logs_clear_desc'] :
                "* This can not be undone. Be very sure you want to clear the logs.<br />
                 * Only Administrators can clear the logs."; ?></span>
            </span>
        <?php
    }
    ?>
    </div>
    <?php
}

function postoffice_files_page() {
    global $postOfficeLang;
    //first get the language ready, get some more functions to show the files then show the files page
    include( "filehandler.php" );
    if (!is_dir(dirname(__FILE__) . "/../../uploads/media/")) {
        $list = ($postOfficeLang) ? $postOfficeLang['files_no_files'] :
                "No media files found. Have you uploaded a document with images inside?";
    } else {
        $list = dirList(dirname(__FILE__) . "/../../uploads/media/", "filetree");
    }
    ?>
    <div class="wrap">
        <h2><?php echo ($postOfficeLang) ? $postOfficeLang['files_page_title'] : "Post Office Files"; ?></h2>
        <script src="http://code.jquery.com/jquery-latest.js" type="text/javascript" ></script>
        <link rel="stylesheet" href="http://jquery.bassistance.de/treeview/demo/screen.css" type="text/css" />
        <link rel="stylesheet" href="http://jquery.bassistance.de/treeview/jquery.treeview.css" type="text/css" />
        <script type="text/javascript" src="http://jquery.bassistance.de/treeview/jquery.treeview.js" ></script>
        <script type="text/javascript" >
            $(document).ready(function(){
                $("#filetree").treeview();
            });
        </script>
        <div class="updated"><p><strong><?php echo ($postOfficeLang) ? $postOfficeLang['files_desc'] :
            "Folders are created based on the name of the Office file."; ?></strong></p></div>
        <p><?php echo ($postOfficeLang) ? $postOfficeLang['files_loc'] :
                        "Files are located at:"; ?> <code><?php echo dirname(dirname(dirname(__FILE__))) . "/uploads/media/"; ?></code></p>
                    <?php echo $list; ?>
    </div>
                    <?php
                }

                function postoffice_settings_page() {
                    global $postOfficeLang;
                    //first get the language ready, get the settings then show the settings page
                    $postoffice_settings = get_option('postoffice_settings');
                    ?>
    <div class="wrap">
        <h2><?php echo ($postOfficeLang) ? $postOfficeLang['settings_page_title'] : "Post Office Settings"; ?></h2>
                                <?php if (isset($_GET['updated']) == "true") { ?>
            <div class="updated"><p><strong>
        <?php echo ($postOfficeLang) ? $postOfficeLang['settings_succeed'] :
                "Post Office: Your settings have been saved successfully."; ?></strong></p></div>
                                <?php } elseif (isset($_GET['updated']) && $_GET['updated'] != "true") { ?>
            <div class="updated"><p><strong>
                                    <?php echo ($postOfficeLang) ? $postOfficeLang['settings_failed'] :
                                            "Post Office: Your settings could not be updated."; ?></strong></p></div>
    <?php } ?>
        <form method="post" action="options.php">
                                <?php settings_fields('postoffice_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="postoffice_publish"><?php echo ($postOfficeLang) ? $postOfficeLang['settings_post_state'] :
                                    "New Post Publish State"; ?></label></th>
                            <?php $post_publish = $postoffice_settings['state']; ?>
                    <td><select id="postoffice_publish" name="postoffice_settings[state]" style="width:150px;" >
                            <option value="draft" <?php echo ($post_publish == "draft") ? 'selected="selected"' : "" ?>>
                            <?php echo ($postOfficeLang) ? $postOfficeLang['new_post_state_draft'] : "Draft"; ?>
                            </option>
                            <option value="publish" <?php echo ($post_publish == "publish") ? 'selected="selected"' : "" ?>>
    <?php echo ($postOfficeLang) ? $postOfficeLang['new_post_state_publish'] : "Publish"; ?>
                            </option>
                            <option value="pending" <?php echo ($post_publish == "pending") ? 'selected="selected"' : "" ?>>
    <?php echo ($postOfficeLang) ? $postOfficeLang['new_post_state_pending'] : "Pending"; ?>
                            </option>
                            <option value="future" <?php echo ($post_publish == "future") ? 'selected="selected"' : "" ?>>
    <?php echo ($postOfficeLang) ? $postOfficeLang['new_post_state_future'] : "Future"; ?>
                            </option>
                        </select></td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['settings_post_state_desc'] :
            "Post's published state. This is the state of the post after the content is proccessed.<br />
                        This Setting is overwritable form the Upload form."; ?></span></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="postoffice_max_image_width">
                            <?php echo ($postOfficeLang) ? $postOfficeLang['settings_img_width'] : "Max Image Width"; ?></label></th>
                    <td><?php $max_width = $postoffice_settings['img_max_width']; ?>
                        <input
                            type="text"
                            id="postoffice_max_image_width"
                            name="postoffice_settings[img_max_width]"
                            size="10"
                            value="<?php if (empty($max_width)) {
                            echo "0";
                        } else {
                            echo $max_width;
                        } ?>"
                            style="width:110px;text-align: right;"
                            /> Pixels</td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['settings_img_width_desc'] :
                                "Maximum image width in pixels. This will set a limit for the images' width. No image's width will exceed this limit,
                         smaller images will not be resized.<br />
                        Default 0 (no resize)."; ?></span></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="postoffice_lang">
                            <?php echo ($postOfficeLang) ? $postOfficeLang['settings_lang'] : "Language"; ?></label></th>
                    <td><?php
                            if (isset($postoffice_settings['lang'])) {
                                $lang = $postoffice_settings['lang'];
                            } else {
                                $lang = "default.php";
                            }
                            ?>
                        <select id="postoffice_lang" name="postoffice_settings[lang]" style="width:150px;" >
                            <?php
                            $Langs = postoffice_Langauges();
                            foreach ($Langs as $file => $props) {
                                if ($file == $lang) {
                                    echo "<option value='$file' selected='selected' >" . $file . " - " . $props['Language'] . "</option>";
                                } else {
                                    echo "<option value='$file' >" . $file . " - " . $props['Language'] . "</option>";
                                }
                            }
                            ?>
                        </select></td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['settings_lang_desc'] :
                                "The Language that should be used in this plugin.<br />
                    Default: English."; ?></span></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="postoffice_skin"><?php echo ($postOfficeLang) ? $postOfficeLang['settings_skin'] :
                                "Post Office Tabbar Skin"; ?></label></th>
    <?php $skin = $postoffice_settings['skin']; ?>
                    <td><select id="postoffice_debug" name="postoffice_settings[skin]" style="width:150px;" >
                                <?php
                                foreach (getSkins() as $value) {
                                    echo "<option value='$value'" . (( $skin == $value) ? 'selected="selected"' : "" ) . ">";
                                    echo $value;
                                    echo "</option>";
                                }
                                ?>
                        </select></td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['settings_skin_desc'] :
                                        "Tabbar Skin Options allow you to change the looks of the tabs. <br />
                        Please Note: Changes will only be visible on new Excel spreadsheets (processed after the change)"; ?></span></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="postoffice_debug"><?php echo ($postOfficeLang) ? $postOfficeLang['settings_debug'] :
                                        "Post Office Debugging"; ?></label></th>
    <?php $state = $postoffice_settings['debug']; ?>
                    <td><select id="postoffice_debug" name="postoffice_settings[debug]" style="width:150px;" >
                            <option value="false" <?php echo ($state == "false") ? 'selected="selected"' : "" ?>>
    <?php echo ($postOfficeLang) ? $postOfficeLang['settings_debug_off'] : "Off"; ?>
                            </option>
                            <option value="true" <?php echo ($state == "true") ? 'selected="selected"' : "" ?>>
    <?php echo ($postOfficeLang) ? $postOfficeLang['settings_debug_on'] : "On"; ?>
                            </option>
                        </select></td>
                    <td><span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['settings_debug_desc'] :
            "Enable Post Office Debugging"; ?></span></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" class="button-primary" value="<?php echo ($postOfficeLang) ?
            $postOfficeLang['settings_save'] : "Save Changes"; ?>" /></p>
        </form>
    </div>
    <?php
}

function postoffice_language_page() {
    global $postOfficeLang;
    //first get the language ready, then show the languages page
    ?>
    <div class="wrap">
        <h2><?php echo ($postOfficeLang) ? $postOfficeLang['lang_page_title'] : "Post Office Languages"; ?></h2>
        <pre>
    <?php $langData = postoffice_Langauges(); ?>
        </pre>
        <table class="widefat fixed" style="margin-bottom:4px;">
            <thead>
                <tr>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_nr'] : "Nr:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_lang'] : "Language:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_code'] : "Language Code:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_desc'] : "Description:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_author'] : "Author:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_version'] : "Version:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_version'] : "Filename:"; ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_nr'] : "Nr:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_lang'] : "Language:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_code'] : "Language Code:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_desc'] : "Description:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_author'] : "Author:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_version'] : "Version:"; ?></th>
                    <th scope="col"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_version'] : "Filename:"; ?></th>
                </tr>
            </tfoot>
            <tbody>
                <?php
                $nr = 1;
                foreach ($langData as $langFile => $props) {
                    echo "<tr>
                        <td>" . $nr . "</td>
                        <td>" . $props['Language'] . "</td>
                        <td>" . $props['LangIntCode'] . "</td>
                        <td>" . $props['Description'] . "</td>
                        <td>" . $props['Author'] . "</td>
                        <td>" . $props['Version'] . "</td>
                        <td>$langFile</td>
                    </tr>";
                    $nr++;
                }
                ?>
            </tbody>
        </table>
        <form method="post" action="<?php echo plugins_url() . "/post-office/createLangFile.php"; ?>">
            <label for="postoffice_filename"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_new_file'] :
            "Language File Name:"; ?></label>
            <input type="text" id="postoffice_filename" name="postoffice_filename" value="" />
            <input type="submit" class="button-primary" value="Create Language File" /><br />
            <span class="description"><?php echo ($postOfficeLang) ? $postOfficeLang['lang_new_desc'] :
            "* Name the files is according to their International code for example:
                American English's code is <strong>en-US</strong> (First the language code, then the country code).<br/>
                * After a file is created, translate the file using the Plugin Edit page (select the `Post Office` as
                the plugin and select the filename form the list on the right hand side)."; ?></span>
        </form>
    </div>
    <?php
}

function getSkins() {
    $skinDir = dirname(__FILE__) . "/tabbar-codebase/imgs/";
    $skins = array_diff(scandir($skinDir), Array(".", "..", "blank.html"));
    return $skins;
}

function postoffice_Langauges() {
    $langDir = dirname(__FILE__) . "/langs/";
    $files = array_diff(scandir($langDir), Array(".", ".."));
    $langData = array();
    $fileHeaders = array(
        'Language' => 'Language',
        'LangIntCode' => 'LangIntCode',
        'Description' => 'Description',
        'Author' => 'Author',
        'Version' => 'Version'
    );
    foreach ($files as $file) {
        $langData[$file] = postoffice_get_file_data($langDir . $file, $fileHeaders);
    }
    return $langData;
}

function postoffice_get_file_data($file, $default_headers) {
    // We don't need to write to the file, so just open for reading.
    $fp = fopen($file, 'r');

    // Pull only the first 8kiB of the file in.
    $file_data = fread($fp, 8192);

    // PHP will close file handle, but we are good citizens.
    fclose($fp);

    $all_headers = $default_headers;

    foreach ($all_headers as $field => $regex) {
        preg_match('/' . preg_quote($regex, '/') . ':(.*)$/mi', $file_data, ${$field});
        if (!empty(${$field}))
            ${$field} = _cleanup_header_comment(${$field}[1]);
        else
            ${$field} = '';
    }

    $file_data = compact(array_keys($all_headers));

    return $file_data;
}

function postoffice_help_page() {
    //show the help page (without languages)
    ?>
    <div class="wrap">
        <h2>Post Office Help</h2>
        <table class="widefat fixed" style="margin-bottom:4px;">
            <thead><tr valign="top">
                    <th scope="col" style="width:20%;"><strong>Question:</strong></th>
                    <th scope="col"><strong>Answer:</strong></th>
                </tr></thead>
            <tfoot><tr valign="top">
                    <th scope="col"><strong>Question:</strong></th>
                    <th scope="col"><strong>Answer:</strong></th>
                </tr></tfoot>
            <tbody><tr valign="top">
                    <th scope="row">How do I use this plugin?</th>
                    <td>Firstly the administrator of the blog should set a few settings on the Settings page.<br />
                        If that is done, you can just go to the Post Office page, fill in the required information, select a (Word / Excel 2007) file
                        on your computer and click on "Create Post Now!"</td>
                </tr>
                <tr valign="top">
                    <th scope="row">What does this plugin do?</th>
                    <td>This plugin reads your office files, extracting all the content from the file and posting it as a WordPress post/page.</td>
                </tr>
                <tr valign="top">
                    <th scope="row">What is the difference between a Setting and an Option?</th>
                    <td>A Setting is an option set at the Settings page of the plugin. Options are optional options that are selected on a per file basis.</td>
                </tr>
                <tr valign="top">
                    <th scope="row">How do I use the Shortcodes of the plugin?</th>
                    <td>This plugin currently have 3 Shortcodes.<br />
                        The first is: <code>[postoffice_excel_open]</code>. It add the needed Javascript to start the Tabbar.<br />
                        Then it is: <code>[postoffice_div]</code>. It groups the content of the tab together. This full one: 
                            <code>[postoffice_div id="theid" name="The Name appear in the Tab" size="150"]</code>. `id` is the unique name for it on the page (cannot start with a number),
                            `name` the identifier will appear on the tabs and `size` the size of the tab (default is 100px). After the content this Shortcode have to be closed with
                            <code>[/postoffice_div]</code>.<br />
                        Lastly it is the <code>[postoffice_excel_close]</code> to end the Javascript.
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">What formatting can this plugin extract?</th>
                    <td>This plugin can extract all text from your document. Currently it can format Headings, Bold, Italics, Underlined, Strike-Through,
                        Super Script, Sub Script, Text Colors and normal. This plugin can also extract the images (in jpeg, png and gif formats) and
                        and resize them for your blog (according to the Image Maximum Width setting) and optional link to the full sized images.</td>
                </tr>
                <tr valign="top">
                    <th scope="row">What is the main purpose of this plugin?</th>
                    <td>This plugin is created to save time and thus money. Step 1: Enter the required information into the fields. Step 2: Select your
                        file. Step 3: Click on "Create Post Now!"
                        This plugin even displays the time it took to create the post from the file, giving you a way to measure the time being saved by
                        using this plugin.</td>
                </tr>
                <tr valign="top">
                    <th scope="row">What does the Error codes mean?</th>
                    <td>
                        <ol>
                            <li>"You are not authorized to upload files."<br />
                                <span class="description">You are not logged in or your account does not allow you to create a post.</span></li>
                            <li>"POST exceeded maximum allowed size. Post size is: some_size - Maximum allowed is: another_size"<br />
                                <span class="description">Your server is set to not handle more than the second amount of data in a single request.
                                    The first amount is the amount that is required for the file you are trying to upload.</span></li>
                            <li>"No upload found in $_FILES for 'postoffice_file'"<br />
                                <span class="description">The uploaded file could not be found.</span></li>
                            <li>Upload Related Errors
                                <ol>
                                    <li>"The uploaded file exceeds the upload_max_filesize directive in php.ini"<br />
                                        <span class="description">Your server is set to not allow the upload of files as large as the one you are trying to upload.</span></li>
                                    <li>"The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form."<br />
                                        <span class="description">The form is set to handle only smaller file sizes that what you are trying to upload.</span></li>
                                    <li>"The uploaded file was only partially uploaded."<br />
                                        <span class="description">Somehow the file did not finish uploading.</span></li>
                                    <li>"No file was uploaded."<br />
                                        <span class="description">The file was not uploaded.</span></li>
                                    <li>"Missing a temporary folder."<br />
                                        <span class="description">The folder where the uploaded file was supposed to be is not found.</span></li>
                                </ol>
                            </li>
                            <li>"Upload failed is_uploaded_file test."<br />
                                <span class="description">The test to make sure that the file was uploaded, failed.</span></li>
                            <li>"File has no name."<br />
                                <span class="description">Uploaded file have no name and thus it can't be a file.</span></li>
                            <li>"File exceeds the maximum allowed size."<br />
                                <span class="description">The file is larger than what the system can handle.</span></li>
                            <li>"File size outside allowed lower bound."<br />
                                <span class="description">The file size is negative and thus it can't be a file.</span></li>
                            <li>"Invalid file extension."<br />
                                <span class="description">This plugin requires an extension of .docx (Word 2007), .xlsx (Excel 2007).
                                    If it is not one of these, this plugin can't work.</span></li>
                            <li>"The post could not be inserted. An unknown error occurred."<br />
                                <span class="description">While adding the post into the database, an error occurred.<br />When a documents content are too big or your
                                    database have corrupted, this error will appear stating that the post can not be inserted.<br />* Make sure your file's size is not to big,
                                    and then try to repair the database.</span></li>
                            <li>"The files contents could not be extracted."<br />
                                <span class="description">The files contents could not be extracted.<br />
                                    Did you get a massage that you do not have the correct extension for PHP enabled?</span></li>
                            <li>"The file data could not be found or read."
                                <br /><span class="description">When trying to read the contents of the Word file, something went wrong.</span></li>
                            <li>"The Media could not be found."
                                <br /><span class="description">When trying to extract any media from the Word file, there does not seem to be any (although the document stated that it have media).</span></li>
                            <li>"The file data could not be found or read."
                                <br /><span class="description">The text of the Word file could not be found and read by the plugin.</span></li>
                            <li>"The temporary files created during the process could not be deleted.
                                The contents, however, might still have been extracted."
                                <br /><span class="description">There are files that are created while processing the Word file,<br />
                                    these could not be removed, but the contents might have been extracted.</span></li>
                            <li>"The file data could not be found or read."
                                <br /><span class="description">The workbook data of your Excel document could not be read by the plugin.</span></li>
                            <li>"The relationships between the workbook sheets could not be found."
                                <br /><span class="description">By reading the workbook file, the plugin can get the relationship between the sheets, but it failed.</span></li>
                            <li>"The text of the sheets could not be found and used."
                                <br /><span class="description">The text of the whole Excel document are stored in a special place, which in this case could not be found.</span></li>
                            <li>"The workbook appears to be empty."
                                <br /><span class="description">The plugin could not identify any sheets in the Excel.</span></li>
                            <li>"The sheets of the workbook appears to be empty."
                                <br /><span class="description">The plugin could not identify any content in the Excel spreadsheets.</span></li>
                            <li>"The temporary values created during the process could not be cleared."
                                <br /><span class="description">The temporary information of the plugin to process Excel files could not be cleared after everything is done.</span></li>
                        </ol>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Why are the Upload Page showing Warnings?</th>
                    <td>The Warnings displayed on the Upload Page are problems that the plugin find that will result in the plugin not working correctly. When you see these
                        warnings, contact your Hosting and ask them for assistance in enabling the required PHP extensions.<br />The `GD Image Library` is only required if your
                        documents contain images. The rest are all required.</td>
                </tr>
                <tr valign="top">
                    <th scope="row">Can I use WordPress Short Codes in the documents?</th>
                    <td>Yes, The shortcodes should be kept through out the whole process (although we have not tested it yet) and thus inserted into the post on the correct place.</td>
                </tr>
                <tr valign="top">
                    <th scope="row">What is the main purpose of this plugin's log?</th>
                    <td>If you are experiencing problems with this plugin (maybe after a WordPress upgrade), the issues will most probably be logged inside the Logs and
                        when contacting me for support, you can provide the log so that I can see what went wrong and release a fix in a very short time. Information collected
                        by the log is:
                        Date &amp; Time; The result of the upload; The file name; The file size; The PHP version. Information is collected at each upload. Logs can now be cleared.
                        Information will not be sold provided to any third party without your consent.<br />
                        <span class="description">This plugin does not distribute this information automatically to any server. This information is stored in a file.</span></td>
                </tr>
                <tr valign="top">
                    <th scope="row">How can we Contribute to this Project?</th>
                    <td>There is a few ways that can be used to contribute to this Free Open Source project:
                        <ul>
                            <li>Make a Donation to PayPal user `webmaster@starsites.co.za`</li>
                            <li>Provide Feedback about the plugin</li>
                            <li>Blog/Tweet about the plugin (reviews)</li>
                            <li>Suggest features to be included in next versions</li>
                            <li>Developers can help development | Anyone can become a Beta Tester</li>
                        </ul>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">How can I be contacted?</th>
                    <td>If you have suggestions for future releases or have a problem, you can contact me from my website:
                        <a href="http://www.starsites.co.za" target="_blank">http://www.starsites.co.za</a>.<br />
                        You can also request help from our Support Site: <a href="http://support.starsites.co.za" target="_blank">http://support.starsites.co.za</a>.</td>
                </tr></tbody>
        </table>
    </div>
    <?php
}

function postoffice_ready() {
    $gz = false;
    $gd = false;
    $xml = false;
    if (!function_exists("gzinflate")) {
        //the unzip will not be able to work / try for another method of unzipping
        $gz = true;
    }
    if (!function_exists("imageCreateTrueColor")) {
        //the directories created's mode can not be set. Rarely a problem
        $gd = true;
    }
    if (!function_exists("xml_parser_create")) {
        //the xml parser can not be created so it can't read the required files
        $xml = true;
    }
    if ($gz || $gd || $xml) {
        //the plugin will have in compatabilities
        echo '<div class="updated">';
        echo '<p><strong><em>Post Office Warnings:</em></strong></p>';
        echo ($gz ? "<p><strong>Warning:</strong> The Plugin will not be able to complete the request. Please notify your hosting provider to activate the
            <code>Zlib</code> PHP extension. This is required to extract the contents of the file.</p>" : "");
        echo ($gd ? "<p><strong>Warning:</strong> The Plugin might not be able to complete the request. Please notify your hosting provider to activate the
            <code>GD Image Library</code> PHP extension. This is required to resize the images.</p>" : "");
        echo ($gd ? "<p><strong>Warning:</strong> The Plugin will not be able to complete the request. Please notify your hosting provider to activate the
            <code>XML Parser</code> PHP extension. This is required to read the information.</p>" : "");
        echo '</div>';
    } else {
        return;
        //the plugin should work
    }
}