<?php

print dirname(__FILE__).'/examples/tcpdf_include.php';
include(dirname(__FILE__).'/examples/tcpdf_include.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->AddPage();
$checked_uno = "";
$checked_due = "";
$checked_tre = "";



$checked_uno = ' checked="checked"';

$checked_due = ' checked="checked"';
$checked_tre = ' checked="checked"';

$html = "";
$html .= '<table>';
$html .= '<tr>';
$html .= "<td>";
$html .= '<input type="checkbox" name="mittente" value=' . '"mittente"' . $checked_uno . '>' . " MITTENTE";
$html .= '<input type="checkbox" name="vettore" value=' . '"vettore"' . $checked_due . '>' . " VETTORE ";
$html .= '<input type="checkbox" name="destinazione" value=' . '"destinatario"' . $checked_tre . '>' . " DESTINATARIO ";
$html .= "</td>";
$html .= '</tr>';
$html .= '</table>';
$html .= '<input type="checkbox" name="agree" value="" checked="checked" /> <label for="agree">I agree </label><br /><br />';
$pdf->writeHTMLCell(0, 0, $pdf->GetX(), $pdf->GetY(), $html, "LTRB", 1);

$pdf->Output('ddt_spedizione.pdf', 'I');



