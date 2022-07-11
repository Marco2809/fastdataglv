<?php

require_once('examples/tcpdf_include.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->AddPage();


  $html = '<head>
    <meta charset="UTF-8">        
    <title>Fatttura</title>
    <link rel="stylesheet" type="text/css" href="stile.css">
    </head>
    <body>
    <img class="logo" alt="webloom-logo"src="Webloom_logo.jpg"></body>';




$pdf->writeHTMLCell(0, 0, $pdf->GetX(), $pdf->GetY(), $html, "LTRB", 1);

$pdf->Output('ddt_spedizione.pdf', 'I');



