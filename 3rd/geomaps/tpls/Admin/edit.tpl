<form method="post" action="/?&href;" style="width:100%">
<input type="hidden" name="ks_action" value="area_add"/>

<div class="float_left">
<input type="image" src="/?&href_base;/map"/>
</div>

<div class="float_right" style="width:200px">
<?
foreach($users_list as $user){
  $color = substr("000000".dechex(user_geomaps::user_color($user)),-6);
  $selected = $user_id == $user['user_id'] ? "checked='checked'":"";
  echo "<p style='background-color:#$color'><label><input type='radio' $selected name='user_id' value='{$user['user_id']}'/> {$user['user_name']} </label></p>";
}
?>
</div>


</form>