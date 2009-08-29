<?php

while(@ob_end_clean());

exyks::$page_def = "_all"; //tpls is not loaded

define('XML_CACHE_PATH', CACHE_PATH."/xml");
define('XSL_CACHE_PATH', CACHE_PATH."/xsl");

include_once "$class_path/sql/".SQL_DRIVER.".php";
include_once "$class_path/stds/files.php";
include_once "$class_path/dom/dtds.php";
include_once "$class_path/myks/parser.php";
include_once "$class_path/myks/elements.php";
include_once "$class_path/myks/generator.php";
include_once "$class_path/xsl/generator.php";

$myks_config = config::retrieve("myks");
$privileges = $myks_config->privileges;

privileges::declare_root_privileges($privileges);



include "cache_dir.php";

try {
    $process_sql = true;
    sql::connect($myks_config['link_admin']);
}catch(rbx $e){
    $process_sql = false;
    rbx::ok("Skipping SQL management");
}


$xml_filename    = RSRCS_PATH."/xsl/root.xsl";        //meta XSL source
$xsl_filename    = RSRCS_PATH."/xsl/metas/xsl_gen.xsl";     //meta XSL stylesheet
$mykse_file_path = XML_CACHE_PATH."/myks.xml";
$mykse_file_url  = "$site_url/".ltrim(end(explode(WWW_PATH, $mykse_file_path,2)),"/");    //W00t

$browsers_engine = array( 'trident', 'gecko', 'webkit', 'presto');
$rendering_sides = array( 'server', 'client');
