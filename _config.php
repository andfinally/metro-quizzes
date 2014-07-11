<?php

error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

session_start();
session_regenerate_id();

// WordPress site we're going to authenticate to
define( 'WORDPRESS_DOMAIN', 'http://gareth.metro.co.uk' );
define( 'APP_ROOT', 'metro-quizzes-2' );

// Base URL for this app
$protocol = strpos( strtolower( $_SERVER[ 'SERVER_PROTOCOL' ] ), 'https' ) === FALSE ? 'http' : 'https';
$host = $_SERVER[ 'HTTP_HOST' ];
define( 'BASE_URL', $protocol . '://' . $host . '/' . APP_ROOT . '/' );

// Used to make token
$shared_secret = 'year2000';

// Used to decrypt payload from WordPress endpoint
$key = 'qEF4X00KUZFVkEU3qaAtsFvT32xB6hwh';
