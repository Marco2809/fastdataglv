<html>
    <head>
    <h2> Inserisci le informazioni necessarie per completare il sondaggio </h2>
    </head>
    
    <body>
        <table border="10" width="40%">
            <align center> 
                <form action="daHTMLaPDF.php" method="POST">
               <td> <font color="blue" > Pratichi sport? 
               
                <font color="black" >  <input type="radio" name="sport" value="si" > Si
                <input type="radio" name="sport" value="no"> No
                
                </td>
                
                
                
                <tr> 
                    <td> <font color="blue" > Che sport pratichi? <font color="black" > 
                        <select name="nomeSport">
                            <option selected="selected" value="0">scorri l'elenco sport</option> 
                            <option value="Calcio">Calcio</option>
                            <option value="Pallavolo">Pallavolo</option>
                            <option value="Tennis">Tennis</option>
                            <option value="Basket">Basket</option>
                            <option value="Nuoto">Nuoto</option>
                            <option value="Ciclismo">Ciclismo</option>
                            <option value="Altro">Altro</option>

                         </select>
                    </td>
                </tr>
                
                
                
                <tr> 
                    <td> <font color="blue" > Professione? <font color="black" > 
                         <select name="professione">
                            <option selected="selected" value="0">scorri l'elenco</option> 
                            <option value="Studente">Studente</option>
                            <option value="Lavoro">Lavoro</option>
                            <option value="Disoccupato">Disoccupato</option>
                    

                        </select>
                    </td>
                </tr>
                
                <tr> 
                     <td> <font color="blue" > Ti piace andare al Cinema? <font color="black" > 
                          <font color="black" >  <input type="radio" name="cinema" value="si" > Si
                          <input type="radio" name="cinema" value="no"> No
                
                     </td>
                     
                  <tr> 
                    <td> <font color="blue" > Che genere di film ti piace? <font color="black" > 
                         <select name="genere">
                            <option selected="selected" value="0">scorri l'elenco </option> 
                            <option value="Azione">Azione</option>
                            <option value="Giallo">Giallo</option>
                            <option value="Horror">Horror</option>
                            <option value="Comico">Comico</option>
                            <option value="Fantascienza">Fantascienza</option>
                            <option value="Romantico">Romantico</option>
                            <option value="Biografico">Biografico</option>

                        </select>
                    </td>
                </tr>   
                     
                     
                <td>  <center> <input type="submit" name="invia" value="invia"/> </center> </td>
                </form>
            </align>
        </table>   
        
    </body>
</html>