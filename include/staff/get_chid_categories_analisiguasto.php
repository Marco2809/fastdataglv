<?php
ini_set('display_errors','On');
error_reporting(E_ALL);
$db = new PDO('mysql:host=localhost;dbname=fd_ticket;charset=utf8', 'root', 'mysql1989');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);


try {

    $id 	= $_REQUEST['parent_id'];
    //if ($id=='')
    //exit;
	$query = "select * from ost_analisiguasto where tipo = '".$id."'";
?>
	
	<select name="area_descrizione_intervento"  id="sub_category_id">
	<?php
	if ($_REQUEST['parent_id']){
foreach($db->query($query) as $rows) {?>
<option value="<?php echo $rows['codice'].' | '.$rows['descrizione'];?>"><?php echo $rows['descrizione'];?></option>
<?php }}else{echo '<option value="" selected="selected"></option>';}
$db = null;
} catch(PDOException $ex) {
    echo "Errore!";
    echo($ex->getMessage());
}

?>
</select>	
