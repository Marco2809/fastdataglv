<?php
ini_set('display_errors','On');
error_reporting(E_ALL);
$db = new PDO('mysql:host=localhost;dbname=fd_ticket;charset=utf8', 'admin', 'Iniziale1!?');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);


try {

    $id 	= $_REQUEST['parent_id'];
    //if ($id=='')
    //exit;
	$query = "select * from ost_asset_fick where tipo = '".$id."'";
?>
	
	<select name="affected_resource_zz_wam_string1"  id="sub_category_id">
	<?php
	if ($_REQUEST['parent_id']){
foreach($db->query($query) as $rows) {?>
<option value="<?php echo $rows['seriale'].'-'.$rows['cod_fick'];?>" ><?php echo $rows['seriale'].' - '.$rows['cod_fick'];?></option>
<?php }}else{echo '<option value="" selected="selected"></option>';}
$db = null;
} catch(PDOException $ex) {
    echo "Errore!";
    echo($ex->getMessage());
}

?>
</select>	
