<?php

require_once('../ThumbGen.class.php');
require_once('../Watermark.class.php');

// Enable caching, in the "cache" folder with infinite duration (until the source image is modified)
$thumbGen = new ThumbGen(true, 'cache', 0);
$tgWatermark = new \ThumbGen\Watermark();

// JPEG thumbnail
$thumbGen->setFormat('jpg');

// 90% quality
$thumbGen->setQuality(90);

// 320 x 240 pixels
$thumbGen->setDimensions(300, 200);

// Prepare the thumbnail
$thumbGen->getThumbnail('images/pic.jpg');

// Set a watermark with default opacity, 30 x 30 pixels, in the center of the
// image, no repetition
$tgWatermark->setWatermark('images/wm.png', null, array(30, 30), array('center', 'middle', 0, 0));

// Apply the watermark
$tgWatermark->addWatermark($thumbGen);

// Update the original thumbnail data with the watermarked version
$thumbGen->updateThumbnailData($tgWatermark->getThumbnailData(), true);

// Output the thumbnail
$thumbGen->outputThumbnail();