<?php

try{
$gsearch = new SoapClient('http://glvservice.fast-data.it/webservices/ws/wsdl_engine2.wsdl',array('connection_timeout'=>5,'trace'=>true,'soap_version'=>SOAP_1_2,'cache_wsdl' => WSDL_CACHE_NONE));


         $result=$gsearch->getAllFromMag($nomeutente);
         $arrresult=explode(";",$result);
        ?>

	    <table width="100%" align="center">
		<th class="liste_titre" colspan="5" style="width:100%; height:50px;" align="left"><h2 style="font-weight:bold; font-family:play; color:#669933;">Asset in dotazione</h2></th>	
			
	    <tr style="font-size:100%; font-family:play; color:black;"><td>Cod Asset</td><td>Etichetta</td><td>Stato Fisico</td><td>Stato Tecnico</td><td>Data Creazione</td></tr>
        
        <?php
        $control=0;
        for($i=0;$i<count($arrresult);$i++)
        {
			$resulta=explode("?",$arrresult[$i]);
            ?>
            <tr style="font-size:80%; font-family:play; color:black;" class="<?php echo ($i % 2 == 0)?'pair':'impair' ?>">
				
            <?php
            
            for($u=0;$u<count($resulta);$u++)
            {
				
		     if ($u!=0 and $u!=1 and $u!=3 and $u!=8) {//rimuovo alcuni risultati
                if($resulta[0]=="PRODOTTO" && $control==0)
                {
                    $control = 1;
                    ?>
                    </tr></table><br>PRODOTTO<br><br><table width="100%"><tr style="font-weight: bold;"><td>Tipo</td><td>Id</td><td>Ref</td><td>Timestamp</td><td>Etichetta</td><td>User Author</td><td>Stock</td><td>Desired Stock</td></tr><tr>
                    <?php
                }
            ?>
            <td><?php echo $resulta[$u]?></td>
            <?php
		     }
            }
            ?>
            </tr><?php
        }
        ?>
        </table>

        <?php
           
       
} catch (SoapFault $exception) {
	print_r($exception);
}
?>        
