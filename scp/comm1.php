<?php
ini_set('display_errors','On');
error_reporting(E_ALL);
if(!file_exists('../main.inc.php')) die('Fatal error... get technical support');

require_once('../main.inc.php');
//require_once('../bootstrap.php');

if(!defined('INCLUDE_DIR')) die('Fatal error... invalid setting.');

require_once(INCLUDE_DIR.'PasswordHash.php');

require_once(INCLUDE_DIR.'class.ticket.php');


		
                $zona=array('RM','RI','VT','LT','FR');
                //$zona2=array('AQ','PE','TE','CH'); 
                     
				$id_ticket=8198;
					
				$topico=db_query("SELECT `topic_id`,`customer_location_l_addr1` FROM ".TICKET_TABLE." NATURAL JOIN ".TICKET_TABLE."__cdata WHERE `ticket_id`=".$id_ticket);
				   
				  
				   while ($row=db_fetch_array($topico)){
		                 $utopico=$row['topic_id'];
		                 $utopica=$row['customer_location_l_addr1'];
	               }
	            
				if (in_array($utopico, Array(15,16,17))){		
				    if (in_array($utopica,$zona)){
                       $costo_ext =18;
				       $costo_int =9;
                    }else{
                       $costo_ext =0;
				       $costo_int =0;
                    }				
				}elseif(in_array($utopico, Array(13,14))){
					if (in_array($utopica,$zona)){
                       $costo_ext =20;
				       $costo_int =10;
                    }else{
                       $costo_ext =0;
				       $costo_int =0;
                    }
				}elseif($utopico==12){
					if (in_array($utopica,$zona)){
                       $costo_ext =14;
				       $costo_int =7;
                    }else{
                       $costo_ext =0;
				       $costo_int =0;
                    }
				}elseif(in_array($utopico, Array(18,19,20,21,22,23))){
					if (in_array($utopica,$zona)){
                       $costo_ext =24;
				       $costo_int =10;
                    }else{
                       $costo_ext =27;
				       $costo_int =12;
                    }
				}elseif(in_array($utopico, Array(24,25))){
					if (in_array($utopica,$zona)){
                       $costo_ext =10;
				       $costo_int =5;
                    }else{
                       $costo_ext =13;
				       $costo_int =6;
                    }
				}elseif(in_array($utopico, Array(26,27,35))){
					if (in_array($utopica,$zona)){
                       $costo_ext =24;
				       $costo_int =9;
                    }else{
                       $costo_ext =27;
				       $costo_int =12;
                    }
				}elseif($utopico==36){
					if (in_array($utopica,$zona)){
                       $costo_ext =14;
				       $costo_int =7;
                    }else{
                       $costo_ext =17;
				       $costo_int =8;
                    }
				}else{
                       $costo_ext =0;
				       $costo_int =0;         
				}
				
				echo ("UPDATE ".TICKET_TABLE."__cdata SET costo_ext='".$costo_ext."',costo_int='".$costo_int."',prezzo='".($costo_ext-$costo_int)."' WHERE `ticket_id`=".$id_ticket);
				 
				

?>
