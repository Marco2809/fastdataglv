
	<table width="100%"><tr><td width="33%">
        <b>Cerca Tutti</b>
        <form method="POST" action="?">
			<?php csrf_token(); ?>
			<input type="hidden" name="ws" value="dolibarr">
            <input type="text" name="name" />
            <input type="submit"  name="submit" value="Search" />
        </form>
       </td>
       <td width="33%">
        <b>Ricerca per Censire</b>
        <form method="POST" action="?">
			<?php csrf_token(); ?>
			<input type="hidden" name="ws" value="dolibarr">
            <select name="tipo">
                <option value="cod_famiglia">Codice Famiglia</option>
                <option value="cod_asset">Codice Asset</option>
                <option value="label">Etichetta</option>
            </select>
            <input type="text" name="ricerca_all" />
            <input type="submit" name="submit" value="Cerca" />
        </form>
        
        </td>
               <td width="33%">
        <b>Ricerca per Aggiungere</b>
        <form method="POST" action="?">
			<?php csrf_token(); ?>
			<input type="hidden" name="ws" value="dolibarr">
            <select name="ricerca">
                <option value="cod_asset">Codice Asset</option>
                 <option value="cod_famiglia">Codice Famiglia</option>
                  <option value="label">Etichetta</option>
            </select>
            <input type="text" name="parametro" />
            <input type="submit" name="submit" value="Cerca Asset" />
        </form>
        </td>
       <tr></table>
             
      
<?php
           if($_POST['submit']=="Cerca"){
       
        try{
$gsearch = new SoapClient('http://glvservice.fast-data.it/webservices/ws/wsdl_engine2.wsdl',array('connection_timeout'=>5,'trace'=>true,'soap_version'=>SOAP_1_2,'cache_wsdl' => WSDL_CACHE_NONE));

$result=$gsearch->searchP_F_A($_POST['tipo'].",".$_POST['ricerca_all']);
       $labels= explode("?",$result);
       if($labels[0]!="update"){
       ?>
       <table width="100%">
	   <tr><td width="33%">
       <form method="POST" action="?">
		   <?php csrf_token(); ?>
		   <input type="hidden" name="ws" value="dolibarr">
                <select name="etichetta">
                <?php for($i=0;$i<count($labels);$i++){
                    ?>
                <option value="<?php echo $labels[$i]?>"><?php echo $labels[$i]?></option>
                <?php } ?>
            </select>
            <input type="submit" name="submit" value="Popola" />
        </form>
        </td>
        <td width="33%"></td>
        <td width="33%"></td>
        </tr>
        </table>
        <?php 
       }
       else
       {
           ?>
           
           <form method="POST" action="?">
			   <?php csrf_token(); ?>
			   <input type="hidden" name="ws" value="dolibarr">
			   <table width="100%">
               <tr><td width="33%"> Etichetta: <input type="text" name="etichetta" readonly="readonly" value="<?php echo $labels[1]?>"></td>
               <td width="33%"> Codice Asset: <input type="text" name="newCodAsset" readonly="readonly" value="<?php echo $labels[2]?>"></td>
               <td width="33%">Codice Famiglia: <input type="text" name="cod_famiglia" readonly="readonly" value="<?php echo $labels[3]?>"></td>
               </tr>
               <tr><td> 
                <select name="stato_tecnico">
                <option value="3">Guasto</option>
                <option value="4">Sconosciuto</option>
            </select>
            </select>
            <input type="submit" name="submit" value="Crea" />
            <input type="hidden" value="update" name="update">
             </td>
        <td></td>
        <td></td>
        </tr>
        </table>
        </form>
       
        <?php
       }
 } catch(SoapFault $fault) {
    trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);
}
           }
           
                if($_POST['submit']=="Popola"){
       
        try{
$gsearch = new SoapClient('http://glvservice.fast-data.it/webservices/ws/wsdl_engine2.wsdl',array('connection_timeout'=>5,'trace'=>true,'soap_version'=>SOAP_1_2,'cache_wsdl' => WSDL_CACHE_NONE));

$result=$gsearch->newAsset($_POST['etichetta']);
       $newAsset= explode("?",$result);
       ?>
      
    
       <form method="POST" action="?">
		   <?php csrf_token(); ?>
		   <input type="hidden" name="ws" value="dolibarr">
		    <table width="100%">
               <tr><td width="33%">Etichetta: <input type="text" name="etichetta" readonly="readonly" value="<?php echo $newAsset[1]?>"></td>
                <td width="33%">Codice Asset: <input type="text" name="newCodAsset" readonly="readonly" value="<?php echo $newAsset[2]?>"></td>
               <td width="33%"> Codice Famiglia: <input type="text" name="cod_famiglia" readonly="readonly" value="<?php echo $newAsset[0]?>"></td>
               </tr>
               <tr><td> 
                <select name="stato_tecnico">
                <option value="3">Guasto</option>
                <option value="4">Sconosciuto</option>
            </select>
            </select>
            <input type="submit" name="submit" value="Crea" />
            <input type="hidden" value="new" name="new">
         </td>
        <td></td>
        <td></td>
        </tr>
        </table>
        </form>
       
        <?php 
 } catch(SoapFault $fault) {
    trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);
}
           }
           
        ?> 



<?php


try{
$gsearch = new SoapClient('http://glvservice.fast-data.it/webservices/ws/wsdl_engine2.wsdl',array('connection_timeout'=>5,'trace'=>true,'soap_version'=>SOAP_1_2,'cache_wsdl' => WSDL_CACHE_NONE));


         if($_POST['update']=="update")
         {
             $result=$gsearch->updateAsset("magazzino,".$_POST['newCodAsset'].",".$_POST['stato_tecnico']);
        print_r("Stampa del risultato: ".$result." <br>");
         }
         
         if($_POST['new']=="new")
         {
             $result=$gsearch->newAsset("magazzino,".$_POST['etichetta'].",".$_POST['newCodAsset'].",".$_POST['cod_famiglia'].",".$_POST['stato_tecnico']);
        print_r("Stampa del risultato: ".$result." <br>");
         }
         
         
         
         
         if($_POST['submit']=="Search")
         {
             $result=$gsearch->getAllFromMag($_POST['name']);
         $arrresult=explode(";",$result);

        ?>
        <br>ASSET<br><br><table width="100%"><tr style="font-weight: bold;"><td>Tipo</td><td>Id</td><td>Cod Asset</td><td>Cod Famiglia</td><td>Etichetta</td><td>Stato Fisico</td><td>Stato Tecnico</td><td>Data Creazione</td><td>Data Modifica</td></tr>
        <?php
        $control=0;
        for($i=0;$i<count($arrresult);$i++)
        {
            ?>
            <tr>
            <?php
            $resulta=explode("?",$arrresult[$i]);
            for($u=0;$u<count($resulta);$u++)
            {
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
            ?>
            </tr><?php
        }
        ?>
        </table>
        <?php
         }
         
         
         
         
           if($_POST['submit']=="Cerca Asset")
         {
             $result=$gsearch->searchByMag("magazzino,".$_POST['ricerca'].",".$_POST['parametro']);
        $arrresult=explode(";",$result);

        ?>
        <br>ASSET<br><br><table width="100%"><tr style="font-weight: bold;"><td>Tipo</td><td>Id</td><td>Cod Asset</td><td>Cod Famiglia</td><td>Etichetta</td><td>Stato Fisico</td><td>Stato Tecnico</td><td>Data Creazione</td><td>Data Modifica</td></tr>
        <?php
        $control=0;
        for($i=0;$i<count($arrresult);$i++)
        {
            ?>
            <tr>
            <?php
            $resulta=explode("?",$arrresult[$i]);
            for($u=0;$u<count($resulta);$u++)
            {
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
            ?>
            </tr><?php
        }
        ?>
        </table>
        <?php
         }
         
         
       
} catch (SoapFault $exception) {
	print_r($exception);
}
?>        
