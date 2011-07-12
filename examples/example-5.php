<?php

require_once('../ThumbGen.class.php');
require_once('../Watermark.class.php');

// Enable caching, in the "cache" folder with a 2 minute expiration
$thumbGen = new ThumbGen(true, 'cache', 120);
$tgWatermark = new \ThumbGen\Watermark();

// JPEG thumbnail
$thumbGen->setFormat('jpg');

// 90% quality
$thumbGen->setQuality(90);

// 320 x 240 pixels
$thumbGen->setDimensions(320, 240);

// Prepare the thumbnail
$thumbGen->getThumbnail('images/pic.jpg');

// Watermark image
$tgWatermark->setWatermarkImage('images/wm.png');

// 20 x 20 pixels watermark
$tgWatermark->setWatermarkDimensions(20, 20);

// Placed in the bottom-left corner at 5 pixels distance from the bottom and
// left edges
$tgWatermark->setWatermarkPosition('bottom', 'left', 5, 5);

// 80% opacity
$tgWatermark->setWatermarkOpacity(80);

// No repetition
$tgWatermark->setWatermarkRepetition('no-repeat');

// Apply the watermark
$tgWatermark->addWatermark($thumbGen);

// Update the original thumbnail data with the watermarked version
$thumbGen->updateThumbnailData($tgWatermark->getThumbnailData(), true);

// Output the thumbnail
$thumbGen->outputThumbnail();