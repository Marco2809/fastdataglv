<form action="" method="POST">
<?php csrf_token(); ?>
<input type="hidden" name="a" value="stampamassiva" />
<fieldset style="min-width:99%;">
            <label><span style="font-size:14px; font-family:play; font-weight:bold; color:black;"><?php echo __('Date Range');?>:</span></label>
            <input class="dp" type="input" size="20" name="startDate">
            <span style="font-size:14px; font-family:play; font-weight:bold; color:black;"><?php echo __('TO');?></span>
            <input class="dp" type="input" size="20" name="endDate">
            <select id="societa" name="societa">
                        <option value="all" selected>Tutti</option>
                        <option value="cartasi">Cartasì</option>
                        <option value="coopersystem">Coopersystem</option>
                        <option value="sisal">Sisal</option>
                        <option value="altro">Altro</option>

            </select>
            <label>  <span style="font-size:14px; font-family:play; font-weight:bold; color:black;">Oppure singolo ordine:</span></label>
            <input type="input" size="10" name="ordinesingolo">
</fieldset>
<input type="submit" value="Stampa">
</form>
<?php if($blocco){?>
<br><br>
<h1>Attenzione: ordine <?php echo $focus;?> chiuso o recesso!</h1>
<?php
}
unset($blocco);
?>
<?php if($inesistente){?>
<br><br>
<h1>Attenzione: ordine <?php echo $focus;?> non trovato!</h1>
<?php
}
unset($blocco);
?>
