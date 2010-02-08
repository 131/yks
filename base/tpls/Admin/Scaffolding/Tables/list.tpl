<box>
Table : <?=$table_name?>

<table class='table'>
<tr class='line_head'>
    <?foreach($table_fields as $field_name=>$field_type){
        echo "<th>$field_name</th>";
    }?>
    <th>Actions</th>
</tr>

<?

if($data) foreach($data as $line){

  echo "<tr class='line_pair'>";
    foreach($table_fields as $field_name=>$field_type){
        $value = $line[$field_name];
        echo "<td>".dsp::field_value($field_type, $value)."</td>";
    }

    $actions = "";

    $uid = "";
    foreach($table_keys as $key_name=>$key_type)
        $uid[$key_name] = $line[$key_name];
    $do = json_encode(array('ks_action'=>'delete', 'uid'=>$uid));

    $actions.="<img onclick='Jsx.action($do, this, \"Supprimer\")' src='&COMMONS_URL;/css/Yks/icons/trash_24'/>";
    echo "<td>$actions</td>";


  echo "</tr>";
} else echo "<tfail> No data here</tfail>";


?>
</table>

<?=$pages_str?>

</box>