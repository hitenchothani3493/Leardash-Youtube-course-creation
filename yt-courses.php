<?php
/*
Plugin Name: LearnDash YouTube Frontend Courses
Plugin URI: 
Description: Add youtube videos as lessons from frontend and create courses 
Version: 1.0
Author: 
Author URI: 
*/


global $ldYtCourses;

/*
 * Include Settings
*/
require_once(__DIR__.'/functions/settings.php');
new ldYtCoursesSettings();

/*
 * Include functional and display assets of plugin
*/
require_once(__DIR__.'/functions/frontend.php');
new ldYtCourses();


?>