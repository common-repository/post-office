<?php
//Start by including all the needed functions for the script to continue
preg_match( '|^(.*?/)(wp-content)/|i' , str_replace( '\\' , '/' , __FILE__ ) , $_m );
require_once $_m[1] . 'wp-load.php' ;

//get the currently logged in user's cookies
if( is_ssl() && empty( $_COOKIE[SECURE_AUTH_COOKIE] ) && !empty( $_REQUEST['auth_cookie'] ) )
    $_COOKIE[SECURE_AUTH_COOKIE] = $_REQUEST['auth_cookie'];
elseif( empty( $_COOKIE[AUTH_COOKIE] ) && !empty( $_REQUEST['auth_cookie'] ) )
    $_COOKIE[AUTH_COOKIE] = $_REQUEST['auth_cookie'];
if( empty( $_COOKIE[LOGGED_IN_COOKIE] ) && !empty( $_REQUEST['logged_in_cookie'] ) )
    $_COOKIE[LOGGED_IN_COOKIE] = $_REQUEST['logged_in_cookie'];
unset( $current_user );

//include the admin section for some other functions
require_once( ABSPATH . 'wp-admin/admin.php' );

$re = dirname( $_SERVER['HTTP_REFERER'] ) . "/admin.php?page=postoffice_logs";
$return = "Location:" . $re;
$returnlink = "<a href='$re'>Return</a>.";

//make sure that the current user may publish posts (if not, terminate script)
if( !current_user_can( 'manage_options' ) ){
    die( "1. You are not authorised to clear the logs.<br />$returnlink" );
}

//Get the current session id of the user, and start the session
if(isset($_POST["PHPSESSID"])){
    session_id($_POST["PHPSESSID"]);
}elseif(isset($_GET["PHPSESSID"])){
    session_id($_GET["PHPSESSID"]);
}
session_start();

$clearedVal = "date,result,filename,filesize,phpversion";
$cleared = file_put_contents( dirname( __FILE__ ) . '/postoffice-log.csv' , $clearedVal , LOCK_EX );
if($cleared === false){
    die("2. An Error had occured. The logs was not cleared.<br />$returnlink");
} else {
    header($return,true,302);
}
exit;