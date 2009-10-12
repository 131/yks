<?


if(!$query) abort(405);
$export = (bool) $sub0;


$params_values = sess::retrieve('query_params');
$sql_query = $query->prepare_query($params_values);


$config->head->title = $query['query_name'];

if(!$query->ready) return;

$res = sql::query($sql_query);
if($res === false)
    rbx::error("L'appel de la requete a echoué");

$cols = array();
  for ($i = 0, $max=pg_num_fields($res); $i < $max; $i++) {
    $cols[$fieldname = pg_field_name($res, $i)] = array(
        'name'=>$fieldname ,
        'type'=>pg_field_type($res, $i),
    );
  }

sql::reset($res);
$data = sql::brute_fetch();

if($export){
    header(sprintf(HEADER_FILENAME_MASK, $config->head->title.".xls"));
    exyks::$headers["excel-server"] = TYPE_CSV;
    exyks::store('XSL_SERVER_PATH', RSRCS_PATH."/xsl/specials/excel.xsl");
    exyks::store('RENDER_SIDE', 'server');
    exyks::store('RENDER_MODE', 'excel');
    exyks::store('RENDER_START', '<html');

}

