<?php

function createMinimalPdf(string $path): void
{
    $objects = [];
    $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj";
    $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj";
    $stream = "BT /F1 12 Tf 100 700 Td (Hello World from PDF) Tj ET\n";
    $objects[4] = "4 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream\nendobj";
    $objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj";
    $objects[5] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    for ($i = 1; $i <= 5; $i++) {
        $offsets[$i] = strlen($pdf);
        $pdf .= $objects[$i] . "\n";
    }
    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n";
    $pdf .= "0 6\n";
    $pdf .= sprintf("%010d 65535 f ", 0) . "\n";
    for ($i = 1; $i <= 5; $i++) {
        $pdf .= sprintf("%010d 00000 n ", $offsets[$i]) . "\n";
    }
    $pdf .= "trailer\n";
    $pdf .= "<< /Size 6 /Root 1 0 R >>\n";
    $pdf .= "startxref\n";
    $pdf .= $xrefOffset . "\n";
    $pdf .= "%%EOF\n";

    file_put_contents($path, $pdf);
}

$fixturesDir = __DIR__;
$pdfPath = $fixturesDir . '/hello.pdf';
if (!file_exists($pdfPath)) {
    createMinimalPdf($pdfPath);
    echo "Created: hello.pdf\n";
} else {
    echo "Exists: hello.pdf\n";
}
echo "PDF fixture generation complete.\n";