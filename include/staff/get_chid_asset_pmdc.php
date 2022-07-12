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
	$query = "select * from ost_asset_pmdc where tipo = '".$id."'";
?><tbody>
	<tr><td>
	<select id="sub_category_id_pmdc">
		<option value=""></option>
	<?php
	if ($_REQUEST['parent_id']){
foreach($db->query($query) as $rows) {?>
<option value="<?php echo $rows['seriale'];?>"  id="<?php echo $rows['seriale'].' | '.$rows['tipo'];?>"><?php echo $rows['seriale'];?></option>
<?php }}else{echo '<option value="" selected="selected"></option>';}
$db = null;
} catch(PDOException $ex) {
    echo "Errore!";
    echo($ex->getMessage());
}

?>
</select>	
<br><br>
	</td>
	<td></td>
	</tr>


      

        <tr>
			<td>Indirizzo Sede:</td>
			<td><input size="16" maxlength="60" placeholder="" name="customer_middle_name"  value=""  type="text"></td>
		</tr>
		<tr>
			<td>Provincia:</td>
			<td><input size="16" maxlength="60" placeholder="" name="customer_location_l_addr1"  value=""  type="text"></td>
		</tr>
		<tr>
			<td>Località:</td>
			<td><input size="16" maxlength="60" placeholder="" name="customer_location_l_addr7"  value="" type="text"></td>
		</tr>
		<tr>
			<td>Codice Sede:</td>
			<td><input size="16" maxlength="60" placeholder="" name="customer_last_name"   type="text"></td>
		</tr>
		<tr>
			<td>Cod. Ticket Cliente:</td>
			<td><input size="16" maxlength="60" name="ref_num" value="N.D."  type="text"></td>
		</tr>
		<tr>
			<td>Codice Bloccante:</td>
			<td><input size="16" maxlength="60" placeholder="" name="zz_block_id_sym" value="" type="text"></td>
		</tr>
		<tr>
			<td>Referenti:</td>
			<td><input size="16" maxlength="256" placeholder="" name="ref_contatto" value=""   type="text"></td>
		</tr>
		<tr>
			<td>Telefono Referenti:</td>
			<td><input size="16" maxlength="60" placeholder="" name="customer_phone_number" value=""  type="text"></td>
		</tr>
		<tr>
			<td>Marca Asset:</td>
			<td><input size="16" maxlength="60" placeholder="" name="affected_resource_zz_wam_string8" value="" type="text"></td>
		</tr>
		<tr>
			<td>Modello Asset:</td>
			<td><input size="16" maxlength="60" placeholder="" name="affected_resource_zz_wam_string9" id="tipo" value="" type="text"></td>
		</tr>
		<tr>
			<td>Serial Number Asset:</td>
			<td><input size="16" maxlength="60" placeholder="" name="affected_resource_zz_wam_string2" id="seriale" value="" type="text"></td>
		</tr>
		<tr>
			<td>PT Number:</td>
			<td><input size="16" maxlength="60" placeholder="" name="affected_resource_zz_wam_string1" value="" type="text"></td>
		</tr>
 </tbody>
 <br>&nbsp;       
<script type="text/javascript">

$('#sub_category_id_pmdc').change(function() {

    var valori = $(this).find(':selected')[0].id.split('|');
    //var id = $(this).find(':selected')[0].id;
    var value0 = valori[0];
    var value1 = valori[1];

    $('#seriale').val(value0);
    $('#tipo').val(value1);


});

</script>

