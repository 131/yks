<?php

class exyks_renderer_excel {

  private static $XSL_SERVER_PATH;
  private static $XSL_TPL_TOP    = "Yks/Renderers/excel_top";
  private static $XSL_TPL_BOTTOM = "Yks/Renderers/excel_bottom";
  static $creator = 'Anonymous';

  static function init(){
    self::$XSL_SERVER_PATH = RSRCS_PATH."/xsl/specials/excel.xsl";
  }

  static function process(){ //prepare exyks rendering engine
    tpls::register_custom_element("table[contains(@class,'table')]", array(__CLASS__, 'extract_data'));
  }

  /**
  * Creation d un xml puis generation excel grâce à un tableau html.
  * Attention die à la fin de la methode
  *
  * @param DOMDocument $doc
  * @param DOMDocument $table_xml
  */
  const styles = "
    .header {
        font-weight:bold;
        background-color:#AEAEAE;
        font:Calibri 11;
    }
    .cell {
      font:Calibri 9;
      vertical-align:middle;
      border:1px solid #000000;
    }
  ";


  /**
  * Extract data from table and export to xlsx
  *
  * @param DOMDocument $doc
  * @param DomElement $table_xml
  */
  public static function extract_data($doc, $table_xml){
    $data_headers = array();
    $data_results = array();

    foreach($table_xml->getElementsByTagName("tr") as $row){
      $is_header  = (bool) $row->getElementsByTagName('th')->length;

      if($is_header && empty($data_headers)){
        foreach ($row->childNodes as $cell) {
          $data_headers[$cell->nodeValue] = $cell->nodeValue;
        }
      }
      else{
        $row_cell = array();
        foreach ($row->childNodes as $cell) {
          $row_cell[] = $cell->nodeValue;
        }
        $data_results[] = $row_cell;
      }
    }

    return exyks_renderer_excel::export($data_headers, $data_results, array(
      'title' => pick((String)exyks::$head->title, "No name"),
    ));

  }

  //meta creator, title
  public static function export($data_headers, $data_results, $metas){
    $title = pick($metas['title'], "No name");

    $out_xml = new DOMDocument('1.0', 'utf-8');
    $root_xml = $out_xml->createElement("data");
    $root_xml->appendChild($out_xml->createElement('style'))
      ->appendChild($out_xml->createTextNode(self::styles));

    $worksheet = $out_xml->createElement('Worksheet');
    $worksheet->setAttribute('Name', pick($metas['title'], 'Page 1'));

    //Pour les datas
    $xml_row = $out_xml->createElement('Row');
    foreach ($data_headers as $name => $value) {
      $cell = $out_xml->createElement('Cell');
      $cell->setAttribute('class', 'header cell');
      $cell->appendChild($out_xml->createTextNode($name));
      $cell->setAttribute('Type', 'String');

      $xml_row->appendChild($cell);
    }

    $worksheet->appendChild($xml_row);

    foreach ($data_results as $row) {
      $xml_row = $out_xml->createElement('Row');

      foreach ($row as $header => $cell_value) {
        $cell = $out_xml->createElement('Cell');
        $cell->setAttribute('class', 'cell');
        $cell->appendChild($out_xml->createTextNode($cell_value));
        $cell->setAttribute('Type', 'String');

        $xml_row->appendChild($cell);
      }

       $worksheet->appendChild($xml_row);
    }

    $root_xml->appendChild($worksheet);
    $out_xml->appendChild($root_xml);
    $xml_to_xlsx = new xml_to_xlsx($out_xml);

    $xml_to_xlsx->create();
    if($meta['creator'])
      $xml_to_xlsx->set_creator($meta['creator']);

    header(sprintf(HEADER_FILENAME_MASK, $title.'.xlsx')); //filename
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $safe_name = files::safe_name("{$title}.xlsx");
    $file_path = files::tmpdir().DIRECTORY_SEPARATOR.$safe_name;

    $xml_to_xlsx->save($file_path);
    echo file_get_contents($file_path);
    unlink($file_path);
    die;

  }

  public static function render($str){
    self::process();
    $str = file_get_contents(tpls::tpl(self::$XSL_TPL_TOP))
          .$str
          .file_get_contents(tpls::tpl(self::$XSL_TPL_BOTTOM));
    exyks::render($str);
    die;
  }



}