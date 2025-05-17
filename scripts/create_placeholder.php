<?php
// Set the content type to be an image
header('Content-Type: image/jpeg');

// Create a blank image (200x200 pixels)
$image = imagecreatetruecolor(200, 200);

// Set background color (light gray)
$bg_color = imagecolorallocate($image, 240, 240, 240);
imagefill($image, 0, 0, $bg_color);

// Set text color (dark gray)
$text_color = imagecolorallocate($image, 80, 80, 80);

// Add a border
$border_color = imagecolorallocate($image, 200, 200, 200);
imagerectangle($image, 0, 0, 199, 199, $border_color);

// Add text
$text = "Accommodation Image";
$font_size = 5;
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$x = (200 - $text_width) / 2;
$y = (200 - $text_height) / 2;
imagestring($image, $font_size, $x, $y, $text, $text_color);

// Add a camera icon representation
$icon_color = imagecolorallocate($image, 100, 100, 100);
$icon_x = 100;
$icon_y = 120;
imagefilledrectangle($image, $icon_x - 20, $icon_y - 15, $icon_x + 20, $icon_y + 15, $icon_color);
imagefilledellipse($image, $icon_x, $icon_y - 5, 15, 15, $bg_color);

// Output the image
imagejpeg($image, '../public/images/placeholder.jpg');

// Free memory
imagedestroy($image);

echo "Placeholder image created successfully in public/images/placeholder.jpg";
?> 