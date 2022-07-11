<?php
/*********************************************************************
    class.format.php

    Pagenation  support class

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/

class PageNate {

    var $start;
    var $limit;
    var $total;
    var $page;
    var $pages;


    function PageNate($total,$page,$limit=20,$url='') {
        $this->total = intval($total);
        $this->limit = max($limit, 1 );
        $this->page  = max($page, 1 );
        $this->start = max((($page-1)*$this->limit),0);
        $this->pages = ceil( $this->total / $this->limit );

        if (($this->limit > $this->total) || ($this->page>ceil($this->total/$this->limit))) {
            $this->start = 0;
        }
        if (($this->limit-1)*$this->start > $this->total) {
            $this->start -= $this->start % $this->limit;
        }
        $this->setURL($url);
    }

    function setURL($url='',$vars='') {
        if ($url) {
            if (strpos($url, '?')===false)
                $url .= '?';
        } else {
         $url = THISPAGE.'?';
        }

        if ($vars && is_array($vars))
            $vars = Http::build_query($vars);

        $this->url = $url.$vars;
    }

    function getStart() {
        return $this->start;
    }

    function getLimit() {
        return $this->limit;
    }


    function getNumPages(){
        return $this->pages;
    }

    function getPage() {
        return ceil(($this->start+1)/$this->limit);
    }

    function showing() {
        $html = '';
        $from= $this->start+1;
        if ($this->start + $this->limit < $this->total) {
            $to= $this->start + $this->limit;
        } else {
            $to= $this->total;
        }
        $html="&nbsp;".__('Showing')."&nbsp;&nbsp;";
        if ($this->total > 0) {
            $html .= sprintf(__('%1$d - %2$d of %3$d' /* Used in pagination output */),
               $from, $to, $this->total);
        }else{
            $html .= " 0 ";
        }
        return $html;
    }

    function getPageLinks($source) {
        $html                 = '';
        $file                =$this->url;
        //$file = 'tickets.php?';
        $displayed_span     = 5;
        $total_pages         = ceil( $this->total / $this->limit );
        $this_page             = ceil( ($this->start+1) / $this->limit );


        
        
        $last=$this_page-1;
        
   
        $next=$this_page+1;
        

        
        

        $start_loop         = floor($this_page-$displayed_span);
        $stop_loop          = ceil($this_page + $displayed_span);



        $stopcredit    =($start_loop<1)?0-$start_loop:0;
        $startcredit   =($stop_loop>$total_pages)?$stop_loop-$total_pages:0;

        $start_loop =($start_loop-$startcredit>0)?$start_loop-$startcredit:1;
        $stop_loop  =($stop_loop+$stopcredit>$total_pages)?$total_pages:$stop_loop+$stopcredit;

   
        
    
         
		    if ($source=="secondo") {
			   if ($this_page===$total_pages AND $this_page!=1) {
               $html .= "<table><tr><td><a href=\"$file&p=$_SESSION[pagina_a]&ps=$last&pl=$_SESSION[pagina_l]&pd=$_SESSION[pagina_d]\" ><img src=\"../images/previous.png\"></a></td><td><b>".$this_page."/".$total_pages."</b></td><td><img src=\"../images/next-des.png\"></td></tr></table>";
               } elseif ($this_page==1 AND $total_pages!=1 AND $total_pages!=0){
			   $html .= "<table><tr><td><img src=\"../images/previous-des.png\"></td><td><b>".$this_page."/".$total_pages."</b></td><td><a href=\"$file&p=$_SESSION[pagina_a]&ps=$next&pl=$_SESSION[pagina_l]&pd=$_SESSION[pagina_d]\" ><img src=\"../images/next.png\"></a></td></tr></table>"; 	   
			   } elseif ($total_pages==1 OR $total_pages==0){
			   $html .= "<table><tr><td><img src=\"../images/previous-des.png\"></td><td><b>1/1</b></td><td><img src=\"../images/next-des.png\"></td></tr></table>"; 	   
			   } else {
			   $html .= "<table><tr><td><a href=\"$file&p=$_SESSION[pagina_a]&ps=$last&pl=$_SESSION[pagina_l]&pd=$_SESSION[pagina_d]\" ><img src=\"../images/previous.png\"></a></td><td><b>".$this_page."/".$total_pages."</b></td><td><a href=\"$file&p=$_SESSION[pagina_a]&ps=$next&pl=$_SESSION[pagina_l]&pd=$_SESSION[pagina_d]\" ><img src=\"../images/next.png\"></a></td></tr></table>"; 	   
			   }   
			   
            } elseif ($source=="terzo"){
			   if ($this_page===$total_pages AND $this_page!=1) {
			   $html .= "<table><tr><td><a href=\"$file&p=$_SESSION[pagina_a]&ps=$_SESSION[pagina_s]&pl=$last&pd=$_SESSION[pagina_d]\" ><img src=\"../images/previous.png\"></a></td><td><b>".$this_page."/".$total_pages."</b></td><td><img src=\"../images/next-des.png\"></td></tr></table>";
			   } elseif ($this_page==1 AND $total_pages!=1 AND $total_pages!=0){	
			   $html .= "<table><tr><td><img src=\"../images/previous-des.png\"></td><td><b>".$this_page."/".$total_pages."</b></td><td><a href=\"$file&p=$_SESSION[pagina_a]&ps=$_SESSION[pagina_s]&pl=$next&pd=$_SESSION[pagina_d]\" ><img src=\"../images/next.png\"></a></td></tr></table>";	   
			   } elseif ($total_pages==1 OR $total_pages==0){
			   $html .= "<table><tr><td><img src=\"../images/previous-des.png\"></td><td><b>1/1</b></td><td><img src=\"../images/next-des.png\"></td></tr></table>";
			   } else {	  	      	
			   $html .= "<table><tr><td><a href=\"$file&p=$_SESSION[pagina_a]&ps=$_SESSION[pagina_s]&pl=$last&pd=$_SESSION[pagina_d]\" ><img src=\"../images/previous.png\"></a></td><td><b>".$this_page."/".$total_pages."</b></td><td><a href=\"$file&p=$_SESSION[pagina_a]&ps=$_SESSION[pagina_s]&pl=$next&pd=$_SESSION[pagina_d]\" ><img src=\"../images/next.png\"></a></td></tr></table>";
               }
               
            } elseif ($source=="quarto"){
			   if ($this_page===$total_pages AND $this_page!=1) {
			   $html .= "<table><tr><td><a href=\"$file&p=$_SESSION[pagina_a]&ps=$_SESSION[pagina_s]&pl=$_SESSION[pagina_l]&pd=$last\" ><img src=\"../images/previous.png\"></a></td><td><b>".$this_page."/".$total_pages."</b></td><td><img src=\"../images/next-des.png\"></td></tr></table>";
			   } elseif ($this_page==1 AND $total_pages!=1 AND $total_pages!=0){	
			   $html .= "<table><tr><td><img src=\"../images/previous-des.png\"></td><td><b>".$this_page."/".$total_pages."</b></td><td><a href=\"$file&p=$_SESSION[pagina_a]&ps=$_SESSION[pagina_s]&pl=$_SESSION[pagina_l]&pd=$next\" ><img src=\"../images/next.png\"></a></td></tr></table>";	   
			   } elseif ($total_pages==1 OR $total_pages==0){
			   $html .= "<table><tr><td><img src=\"../images/previous-des.png\"></td><td><b>1/1</b></td><td><img src=\"../images/next-des.png\"></td></tr></table>";
			   } else {	  	      	
			   $html .= "<table><tr><td><a href=\"$file&p=$_SESSION[pagina_a]&ps=$_SESSION[pagina_s]&pl=$_SESSION[pagina_l]&pd=$last\" ><img src=\"../images/previous.png\"></a></td><td><b>".$this_page."/".$total_pages."</b></td><td><a href=\"$file&p=$_SESSION[pagina_a]&ps=$_SESSION[pagina_s]&pl=$_SESSION[pagina_l]&pd=$next\" ><img src=\"../images/next.png\"></a></td></tr></table>";
               }
			   	
			} else {
			   if ($this_page===$total_pages AND $this_page!=1) {
			   $html .= "<table><tr><td><a href=\"$file&p=$last&ps=$_SESSION[pagina_s]&pl=$_SESSION[pagina_l]&pd=$_SESSION[pagina_d]\" ><img src=\"../images/previous.png\"></a></td><td><b>".$this_page."/".$total_pages."</b></td><td><img src=\"../images/next-des.png\"></td></tr></table>";
			   } elseif ($this_page==1 AND $total_pages!=1 AND $total_pages!=0){
			   $html .= "<table><tr><td><img src=\"../images/previous-des.png\"></td><td><b>".$this_page."/".$total_pages."</b></td><td><a href=\"$file&p=$next&ps=$_SESSION[pagina_s]&pl=$_SESSION[pagina_l]&pd=$_SESSION[pagina_d]\" ><img src=\"../images/next.png\"></a></td></tr></table>";	   
			   } elseif ($total_pages==1 OR $total_pages==0){
			   $html .= "<table><tr><td><img src=\"../images/previous-des.png\"></td><td><b>1/1</b></td><td><img src=\"../images/next-des.png\"></td></tr></table>";
			   } else {      	
			   $html .= "<table><tr><td><a href=\"$file&p=$last&ps=$_SESSION[pagina_s]&pl=$_SESSION[pagina_l]&pd=$_SESSION[pagina_d]\" ><img src=\"../images/previous.png\"></a></td><td><b>".$this_page."/".$total_pages."</b></td><td><a href=\"$file&p=$next&ps=$_SESSION[pagina_s]&pl=$_SESSION[pagina_l]&pd=$_SESSION[pagina_d]\" ><img src=\"../images/next.png\"></a></td></tr></table>";
               }	
               
            }
         
         
        


        return $html;
    }

}
?>
