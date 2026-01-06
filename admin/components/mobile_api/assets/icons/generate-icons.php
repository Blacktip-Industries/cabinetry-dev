<?php
/**
 * Generate placeholder PWA icons
 * Run this script to create all required icon sizes
 */

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

foreach ($sizes as $size) {
    // Create a simple colored square with text
    $image = imagecreatetruecolor($size, $size);
    
    // Background color (blue)
    $bgColor = imagecolorallocate($image, 66, 133, 244); // #4285F4
    imagefill($image, 0, 0, $bgColor);
    
    // Text color (white)
    $textColor = imagecolorallocate($image, 255, 255, 255);
    
    // Add text
    $text = "PWA";
    $fontSize = $size / 4;
    $font = 5; // Built-in font
    
    // Center text
    $textWidth = imagefontwidth($font) * strlen($text);
    $textHeight = imagefontheight($font);
    $x = ($size - $textWidth) / 2;
    $y = ($size - $textHeight) / 2;
    
    imagestring($image, $font, $x, $y, $text, $textColor);
    
    // Save image
    $filename = __DIR__ . "/icon-{$size}.png";
    imagepng($image, $filename);
    imagedestroy($image);
    
    echo "Created: icon-{$size}.png\n";
}

echo "All icons generated successfully!\n";

