<?php 

require_once 'vendor/autoload.php';
require_once 'src/crossroads.php';

define( 'CROSSROAD_BASE_DIR', dirname( __FILE__ ) );
define( 'CROSSROAD_PUBLIC_DIR', CROSSROAD_BASE_DIR . '/public' );
define( 'CROSSROAD_THEME_DIR', CROSSROAD_BASE_DIR . '/themes' );

$crossroads = new CR\Engine;
$crossroads->run( $argc, $argv );


