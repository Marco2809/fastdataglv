<?php

if(($subnav=$nav->getSubMenu()) && is_array($subnav)){
    $activeMenu=$nav->getActiveMenu();
    if($activeMenu>0 && !isset($subnav[$activeMenu-1]))
        $activeMenu=0;
        //print_r($subnav);
    foreach($subnav as $k=> $item) {
        if($item['droponly']) continue;
        if ((strpos($item['desc'],'Disallineati')!==false)&&$thisstaff->getDeptId()!=6) {
		      continue;
		}
		
		if ((strpos($item['desc'],'Asset Magazzino')!==false)&&$thisstaff->getDeptId()!=6) {
		      continue;
		}
		
        $class=$item['iconclass'];
        if ($activeMenu && $k+1==$activeMenu
                or (!$activeMenu
                    && (strpos(strtoupper($item['href']),strtoupper(basename($_SERVER['SCRIPT_NAME']))) !== false
                        or ($item['urls']
                            && in_array(basename($_SERVER['SCRIPT_NAME']),$item['urls'])
                            )
                        )))
            $class="$class active";
        if (!($id=$item['id']))
            $id="subnav$k";
        if ( $thisstaff->getDeptId()!=7 AND $thisstaff->getDeptId()!=8 AND $thisstaff->getDeptId()!=9 ) {// laboratorio, magazzino e partner non possono chiudere ticket (quindi vedere ticket chiusi)          
        echo sprintf('<li><a class="%s" href="%s" title="%s" id="%s">%s</a></li>',
                $class, $item['href'], $item['title'], $id, $item['desc']);
        }else{
		
		if ($thisstaff->getDeptId()==7) {// siamo nel lab         
        	
		if ($thisstaff->getId()==5) {// è il responsabile di laboratorio          
           if (strpos($item['desc'],'Chiuso')===false) {// tutti tranne i chiusi          
                echo sprintf('<li><a class="%s" href="%s" title="%s" id="%s">%s</a></li>',
                $class, $item['href'], $item['title'], $id, $item['desc']);
           }
        }else{
		   if ( strpos($item['desc'],'Aperto')===false) {// tutti tranne i chiusi e gli aperti          
                echo sprintf('<li><a class="%s" href="%s" title="%s" id="%s">%s</a></li>',
                $class, $item['href'], $item['title'], $id, $item['desc']);
           }
        }	
		}
		
		if ($thisstaff->getDeptId()==8) {// siamo nel magazzino        
        	
		   if (strpos($item['desc'],'Chiuso')===false) {// tutti tranne i chiusi e gli aperti          
                echo sprintf('<li><a class="%s" href="%s" title="%s" id="%s">%s</a></li>',
                $class, $item['href'], $item['title'], $id, $item['desc']);
           }
        	
		}
		
		if ($thisstaff->getDeptId()==9) {// siamo nei partner tecnici         
        	
		   if (strpos($item['desc'],'Aperto')===false) {// tutti tranne gli aperti. Attenzione: "Risolto" è uno stato chiuso!!!
                echo sprintf('<li><a class="%s" href="%s" title="%s" id="%s">%s</a></li>',
                $class, $item['href'], $item['title'], $id, $item['desc']);
           }
        	
		}	
			

        }         
    }
    /*token di query necessaria
    $classe="export-csv no-pjax";
    $href=Http::build_query(array('a' => 'export', 'h' => $hash,'status' => $_REQUEST['status']));
    $titolo="esporta";
    $ido="esportazione";
    $descrizione=__('Export').'&nbsp;<i class="help-tip icon-question-sign" href="#export"></i>';
    echo sprintf('<li><a class="%s" href="?%s" title="%s" id="%s">%s</a></li>',
                $classe, $href, $titolo, $ido, $descrizione);
                */
}
