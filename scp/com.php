<?php
if(!file_exists('../main.inc.php')) die('Fatal error... get technical support');

require_once('../main.inc.php');
//require_once('../bootstrap.php');

if(!defined('INCLUDE_DIR')) die('Fatal error... invalid setting.');

require_once(INCLUDE_DIR.'PasswordHash.php');

require_once(INCLUDE_DIR.'class.ticket.php');

$carico = array();

$sql="SELECT ticket_id, status_id, staff_id, topic_id,customer_location_l_addr1  FROM ost_ticket natural join ost_ticket__cdata where status_id=23";
    $result = db_query($sql) or die(db_error());
		while ($row = db_fetch_array($result)) {
			$carico[]=array($row['ticket_id'],$row['status_id'],$row['staff_id'],$row['topic_id'],$row['customer_location_l_addr1']);
		}
/*
echo '<pre>';
print_r($carico);
echo '</pre>';
*/
$zona=array('RM','RI','VT','LT','FR');
$i=0;
foreach ($carico as $terna){
	
	
$utopico=$terna[3];
$utopica=$terna[4];
$tecnico=$terna[2];
	
if (in_array($utopico, Array(15,16,17))){		
				    if (in_array($utopica,$zona)){
                       $costo_ext =18;
				       $costo_int =($tecnico==80)?0:10;
                    }else{
                       $costo_ext =0;
				       $costo_int =0;
                    }				
				}elseif(in_array($utopico, Array(13,14))){
					if (in_array($utopica,$zona)){
                       $costo_ext =20;
				       $costo_int =($tecnico==80)?0:11;
                    }else{
                       $costo_ext =0;
				       $costo_int =0;
                    }
				}elseif($utopico==12){
					if (in_array($utopica,$zona)){
                       $costo_ext =14;
				       $costo_int =($tecnico==80)?0:8;
                    }else{
                       $costo_ext =0;
				       $costo_int =0;
                    }
				}elseif(in_array($utopico, Array(18,19,20,21,22,23))){
					if (in_array($utopica,$zona)){
                       $costo_ext =24;
				       $costo_int =($tecnico==80)?0:11;
                    }else{
                       $costo_ext =27;
				       $costo_int =($tecnico==80)?0:12;
                    }
				}elseif(in_array($utopico, Array(24,25))){
					if (in_array($utopica,$zona)){
                       $costo_ext =10;
				       $costo_int =($tecnico==80)?0:6;
                    }else{
                       $costo_ext =13;
				       $costo_int =($tecnico==80)?0:6;
                    }
				}elseif(in_array($utopico, Array(26,27,35))){
					if (in_array($utopica,$zona)){
                       $costo_ext =24;
				       $costo_int =($tecnico==80)?0:10;
                    }else{
                       $costo_ext =27;
				       $costo_int =($tecnico==80)?0:12;
                    }
				}elseif($utopico==36){
					if (in_array($utopica,$zona)){
                       $costo_ext =14;
				       $costo_int =($tecnico==80)?0:8;
                    }else{
                       $costo_ext =17;
				       $costo_int =($tecnico==80)?0:8;
                    }
				}else{
                       $costo_ext =0;
				       $costo_int =0;         
				}
								
$prezzo=$costo_ext-$costo_int;	

//echo '<pre>';
$sql="UPDATE `ost_ticket__cdata` SET `costo_ext`=$costo_ext, `costo_int`=$costo_int, `prezzo`=$prezzo WHERE ticket_id=$terna[0];";
//echo '</pre>';
//db_query($sql);
$i++;
}
echo $i;
?>
