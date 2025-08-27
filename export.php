<?php

require 'fpdf.php';
define('DOC_ROOT', realpath(dirname(__FILE__) . '/./'));

function my_autoloader($class_name)
{
    require DOC_ROOT . '/classes/' . strtolower($class_name) . '.php';
}
spl_autoload_register('my_autoloader');
$db = Database::getDatabase();

function addInvoicesTable($pdf, $db, $type)
{
    // Section title
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(220, 220, 220);  // Light gray background for header
    $pdf->Cell(0, 10, ucfirst($type) . ' Invoices', 0, 1, 'L', true);
    $pdf->Ln(3);

    // Table header (without "Amount After VAT" and "Created At")
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(245, 245, 245);  // Light background for rows
    $pdf->SetDrawColor(0, 0, 0); // Black border color

    // Column widths
    $colWidths = [20, 25, 35, 30, 30, 30, 30];

    // Table header
    $header = ['Invoice No.', 'Amount', ($type == 'in' ? 'Source' : 'Target'), 'Reason', 'Remark', 'Issue Date', 'Due Date'];
    for ($i = 0; $i < count($header); $i++) {
        $pdf->Cell($colWidths[$i], 10, $header[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Fetch and display invoice rows
    $pdf->SetFont('Arial', '', 10);
    $result = $db->query("SELECT * FROM invoice_$type WHERE issue_date BETWEEN :start_date AND :end_date", [
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date']
    ]);

    // Alternate row colors for readability
    $fill = false;

    while ($row = $result->fetch_assoc()) {
        $pdf->SetFillColor($fill ? 235 : 255, 255, 235); // Alternate row colors
        $fill = !$fill;

        // Display main row
        $pdf->Cell($colWidths[0], 10, $row['invoice_number'], 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[1], 10, number_format($row['amount'], 2), 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[2], 10, ($type == 'in' ? $row['source'] : $row['target']), 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[3], 10, $row['reason'], 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[4], 10, $row['remark'], 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[5], 10, $row['issue_date'], 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[6], 10, $row['due_date'], 1, 1, 'C', $fill);

        // Display secondary row (Amount After VAT and Created At) under each main row
        $pdf->Cell($colWidths[0], 8, 'Amount After VAT:', 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[1], 8, number_format($row['amount_after_vat'], 2), 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[2] + $colWidths[3], 8, 'Created At:', 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[4] + $colWidths[5] + $colWidths[6], 8, $row['created_at'], 1, 1, 'C', $fill);

        // Draw a line to separate each set of rows
        $pdf->SetLineWidth(0.3);
        $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(2);
    }

    // Add space after the table
    $pdf->Ln(5);
}




// Initialize PDF with margins and title
$pdf = new FPDF();
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'VAT Calculation Report', 0, 1, 'C');
$pdf->Ln(10);

// Display Net VAT Due
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Net VAT Due: ' . number_format($_POST['vat_due'], 2) . ' AED', 0, 1, 'C');
$pdf->Ln(10);

// Add "In Invoices" Table
addInvoicesTable($pdf, $db, 'in');
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 10, 'Total Receipts Amount without VAT: ' . number_format($_POST['in_amount'], 2) . ' AED', 0, 1);
$pdf->Cell(0, 10, 'Total Receipts Amount with VAT: ' . number_format($_POST['in_vat_amount'], 2) . ' AED', 0, 1);
$pdf->Cell(0, 10, 'Total VAT Receipts: ' . number_format($_POST['input_vat'], 2) . ' AED', 0, 1);
$pdf->Ln(10);

// Add "Out Invoices" Table
addInvoicesTable($pdf, $db, 'out');
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 10, 'Total Payments Amount without VAT: ' . number_format($_POST['out_amount'], 2) . ' AED', 0, 1);
$pdf->Cell(0, 10, 'Total Payments Amount with VAT: ' . number_format($_POST['out_vat_amount'], 2) . ' AED', 0, 1);
$pdf->Cell(0, 10, 'Total VAT Payments: ' . number_format($_POST['output_vat'], 2) . ' AED', 0, 1);

// Footer with date and page number
$pdf->AliasNbPages();
$pdf->SetY(-15);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 10, 'Page ' . $pdf->PageNo() . '/{nb}', 0, 0, 'C');

// Output the PDF
$pdf->Output('D', 'VAT_Calculation_Report.pdf');
