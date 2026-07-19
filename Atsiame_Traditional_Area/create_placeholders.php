<?php
// create_placeholders.php - Generates physical mock files for seeded documents and photos

// 1. Ensure directories exist
@mkdir('uploads/documents', 0777, true);
@mkdir('uploads/photos', 0777, true);

// 2. Generate valid minimal single-page PDFs
$pdf1 = "%PDF-1.4
1 0 obj <</Type/Catalog/Pages 2 0 R>> endobj
2 0 obj <</Type/Pages/Kids[3 0 R]/Count 1>> endobj
3 0 obj <</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Resources<</Font<</F1<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>>>>>/Contents 4 0 R>> endobj
4 0 obj <</Length 92>>stream
BT
/F1 16 Tf
50 750 Td
(Atsiame Traditional Area) Tj
/F1 12 Tf
0 -30 Td
(Customary Lands Declaration Act - Stool Records Archive) Tj
ET
endstream
endobj
xref
0 5
0000000000 65535 f 
0000000009 00000 n 
0000000056 00000 n 
0000000111 00000 n 
0000000250 00000 n 
trailer <</Size 5/Root 1 0 R>>
startxref
393
%%EOF";

$pdf2 = "%PDF-1.4
1 0 obj <</Type/Catalog/Pages 2 0 R>> endobj
2 0 obj <</Type/Pages/Kids[3 0 R]/Count 1>> endobj
3 0 obj <</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Resources<</Font<</F1<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>>>>>/Contents 4 0 R>> endobj
4 0 obj <</Length 92>>stream
BT
/F1 16 Tf
50 750 Td
(Atsiame Traditional Area) Tj
/F1 12 Tf
0 -30 Td
(Traditional Council Minutes - January 2026 Assembly) Tj
ET
endstream
endobj
xref
0 5
0000000000 65535 f 
0000000009 00000 n 
0000000056 00000 n 
0000000111 00000 n 
0000000250 00000 n 
trailer <</Size 5/Root 1 0 R>>
startxref
393
%%EOF";

file_put_contents('uploads/documents/customary_lands_act.pdf', $pdf1);
file_put_contents('uploads/documents/minutes_jan_2026.pdf', $pdf2);
echo "Written PDF documents successfully.\n";

// 3. Generate traditional blue-gold JPG images using GD library
function generateImage($filename, $text) {
    if (!function_exists('imagecreate')) {
        // Fallback: write a valid tiny 1x1 grey JPEG hex
        $tiny_jpeg = hex2bin('ffd8e000104a46494600010101006000600000ffdb004300080606070605080707070909080a0c140d0c0b0b0c1912130f141d1a1f1e1d1a1c1c20242e2720222c231c1c2837292c30313434341f27393d38323c2e333430ffc0000b080001000101011100ffc4000f000101000000000000000000000000000000ffda0008010100003f00abc0ffd9');
        file_put_contents($filename, $tiny_jpeg);
        echo "GD library not found, created valid tiny 1x1 JPEG: $filename\n";
        return;
    }
    
    $width = 800;
    $height = 500;
    $img = imagecreate($width, $height);
    
    // Background: Deep Blue (#0F3057)
    $bg = imagecolorallocate($img, 15, 48, 87);
    
    // Text and frames: Gold (#D4AF37)
    $gold = imagecolorallocate($img, 212, 175, 55);
    
    // Draw double royal borders
    imagerectangle($img, 20, 20, $width - 20, $height - 20, $gold);
    imagerectangle($img, 25, 25, $width - 25, $height - 25, $gold);
    
    // Add title text
    $font_size = 5;
    $text_width = imagefontwidth($font_size) * strlen($text);
    $text_height = imagefontheight($font_size);
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2;
    
    imagestring($img, $font_size, $x, $y, $text, $gold);
    
    // Save image
    imagejpeg($img, $filename, 95);
    imagedestroy($img);
    echo "Generated image placeholder: $filename\n";
}

generateImage('uploads/photos/durbar_chiefs.jpg', 'ATAMIS - Grand Durbar of Chiefs (Atsiame)');
generateImage('uploads/photos/stool_house.jpg', 'ATAMIS - Royal Stool House (Atsiame)');
?>
