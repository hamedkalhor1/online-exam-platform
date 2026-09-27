<?php
/*
Plugin Name: Online Exam Word Importer
Description: Import exam questions from Word files
Version: 1.0
Author: Saadat Azmoon
*/

if (!defined('ABSPATH')) {
    exit;
}


define(
    'OEWI_PATH',
    plugin_dir_path(__FILE__)
);



require_once OEWI_PATH . 'admin-page.php';

require_once OEWI_PATH . 'word-parser.php';