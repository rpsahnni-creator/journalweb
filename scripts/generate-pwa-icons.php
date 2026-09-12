<?php

if (! function_exists('imagecreatetruecolor')) {
    fwrite(STDERR, "GD is required to generate PWA icons.\n");
    exit(1);
}

$directory = dirname(__DIR__).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'images';
if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
    fwrite(STDERR, "Unable to create {$directory}\n");
    exit(1);
}

foreach ([192, 512] as $size) {
    $image = imagecreatetruecolor($size, $size);
    $background = imagecolorallocate($image, 10, 22, 40);
    $accent = imagecolorallocate($image, 176, 122, 50);
    $white = imagecolorallocate($image, 255, 255, 255);

    imagefilledrectangle($image, 0, 0, $size, $size, $background);
    $pad = (int) ($size * 0.18);
    imagefilledrectangle($image, $pad, $pad, $size - $pad, $size - $pad, $accent);

    $text = 'SRT';
    $font = 5;
    $textWidth = imagefontwidth($font) * strlen($text);
    $textHeight = imagefontheight($font);
    imagestring($image, $font, (int) (($size - $textWidth) / 2), (int) (($size - $textHeight) / 2), $text, $white);

    $path = $directory.DIRECTORY_SEPARATOR.'pwa-'.$size.'.png';
    imagepng($image, $path);
    imagedestroy($image);
    echo "wrote {$path}\n";
}
