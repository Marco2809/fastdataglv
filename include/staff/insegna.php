<?php
require_once("dbcontroller.php");
$db_handle = new DBController();
if(!empty($_POST["ref_num"])) {
$ref_num=$_POST["ref_num"];
$query ="SELECT ref_num FROM ost_ticket__cdata where ref_num LIKE '" .$ref_num. "%' ORDER BY customer_middle_name LIMIT 0,6";
$result = $db_handle->runQuery($query);
if(!empty($result)) {
?>
<ul id="country-list">
<?php
foreach($result as $country) {
?>
<li onClick="selectCountry('<?php echo $country["ref_num"]; ?>');"><?php echo $country["ref_num"]; ?></li>
<?php } ?>
</ul>
<?php } } ?>
