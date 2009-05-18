<box theme="&pref.dft;" id="users_list" caption="<?="Utilisateur : ".$user_infos['user_name']?>" style="width:500px">

<?
$is_admin = auth::verif("admin",'admin');

?>

<p>Vous êtes ici : <?=$parent_path?></p>

<?
foreach($addrs_list as $addr_id) {
    echo "<box src='/?$href_fold//$user_id/Manage/Addrs//$addr_id'/>";
}

if($addrs_list)echo "<clear/><hr/>";
?>


<ks_form ks_action="users_manage" id="users_manage">
<table style='width:100%' class='table'>
  <tr class='line_head'>
	<th style="width:30px">#</th>
	<th>Nom</th>
	<th>Type</th>
	<th>Actions</th>
	<th style="width:15px"><input type='checkbox' id='users_ids'/></th>
  </tr>
<tbody>
<?

if($children_infos) foreach($children_infos as $user_id=>$user_infos){
  $actions="";
  if($is_admin)
        $actions.="<div class='rbx_close' onclick='user_delete($user_id)'>&#160;</div>";

echo <<<EOS
<tr class='line_pair'>
	<td>$user_id</td>
	<td><a href='/?$href_fold//$user_id/Manage' target='user_infos'><img src="&COMMONS_URL;/css/Yks/skin/noia0/imgs/user1.png"/></a><a href='/?$href_fold//$user_id/Manage/access' target='user_access'><img src="&COMMONS_URL;/css/Yks/skin/noia0/imgs/lock_16.png"/></a>&#160;&#160;<a href='/?$href_fold//$user_id/list'>{$user_infos['user_name']}</a></td>
	<td>&user_type.{$user_infos['user_type']};</td>
	<td>$actions
	</td>
	<td><input type='checkbox' name='users_id[{$user_id}]'/></td>
</tr>
EOS;
} else echo "<tr><td colspan='2'>Aucun utilisateur à ce niveau</td></tr>";
?>
</tbody>
</table>
Pages : <?=$pages?><br/>

<hr/>

<?if($is_admin){?>

Pour la selection : <br/>
Deplacer vers<br/>
<box src="?&href_fold;/check_name//where_id"/>
<br/>
<span onclick="Jsx.action({data:$('users_manage').toQueryString()},$('users_manage'))">Deplacer</span><br/>


    <span onclick="Jsx.action({data:$('users_manage').toQueryString()+'&amp;users_delete=1'},$('users_manage'),this.innerHTML)">Supprimer</span>
<?}?>

<div class='align_right'>
<button theme="action"  target="user_manage" href="/?/Admin/Users//<?=$parent_id?>/manage">Ajouter un utilisateur ici</button>

<button theme="action" target="addr_manage" href="/?/Admin/Users//<?=$parent_id?>/Manage/Addrs/manage">Ajouter des coordonnees ici</button>
</div>

</ks_form>

<script>

function user_delete(user_id){
  Jsx.action({
	ks_action:'users_manage',
	'users_delete':1,
	user_id:user_id
  }, $('users_manage'), 'Supprimer');
}

</script>
<domready>



$('users_ids').addEvent('click',function(){
	this.getParent('table').getElements('input[name^=users_id]').set("checked",this.checked)
});
</domready>


</box>