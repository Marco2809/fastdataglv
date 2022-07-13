<?php

try{
$gsearch = new SoapClient('http://5.249.147.181:8081/webservices/ws/wsdl_engine2.wsdl',array('connection_timeout'=>5,'trace'=>true,'soap_version'=>SOAP_1_2,'cache_wsdl' => WSDL_CACHE_NONE));


         $result=$gsearch->getAllFromMag($_GET['username']);
         $arrresult=explode(";",$result);
        ?>
       
	   <input type="hidden" name="ws" value="dolibarr">
        <table width="100%" align="center"><tr style="font-size:100%; font-family:play; color:black;"><td>Seleziona</td><td>Sostituisci</td><td>Cod Asset</td><td>Etichetta</td><td>Stato Fisico</td><td>Stato Tecnico</td><td>Data Creazione</td></tr>
        <?php
        $control=0;
        for($i=0;$i<count($arrresult);$i++)
        {
			$resulta=explode("?",$arrresult[$i]);
            ?>
            <tr style="font-size:80%; font-family:play; color:black;" class="<?php echo ($i % 2 == 0)?'pair':'impair' ?>">
			<td><input type="checkbox" id="seleziona_<?php echo $i;?>" value="<?php echo $resulta[2].'|'.$resulta[3].'|'.$resulta[4]; ?>" name="check-list[]" class="seleziona"></td>	
			<td><input type="checkbox" id="sostituisci_<?php echo $i;?>" value="<?php echo $i;?>" name="check-sost[]" class="sostituisci" disabled="disabled"></td>	
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

<script>
$(function(){
$('.sostituisci').change(function() {
	var i = parseInt($(this).attr('id').replace(/[^\d]/g, ''),10);
    $("#seleziona_"+i).val($("#seleziona_"+i).val()+(($(this).is(':checked')) ? "|1" : "|0"));
});
});
</script>

<script>
$('.seleziona').on('click', function () {
    var select = parseInt($(this).attr('id').replace(/[^\d]/g, ''),10);
    $('.sostituisci')
        .prop('disabled', true)
        .eq(select)
            .prop('disabled', !this.checked);
});
</script>
