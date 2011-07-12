<?php

require_once('../ThumbGen.class.php');
require_once('../Watermark.class.php');

// Enable caching, in the "cache" folder with a 2 minutes expiration
$thumbGen = new ThumbGen(true, 'cache', 120);
$tgWatermark = new \ThumbGen\Watermark();

// JPEG thumbnail
$thumbGen->setFormat('jpg');

// 90% quality
$thumbGen->setQuality(90);

// 320 x 240 pixels
$thumbGen->setDimensions(340, 240);

// Prepare the thumbnail
$thumbGen->getThumbnail('images/pic.jpg');

// Set a watermark with 50% opacity, 30 x 30 pixels, in the center of the image,
// repeated over the whole thumbnail with 3 pixels padding horizontally and
// vertically
$tgWatermark->setWatermark('images/wm.png', 50, array(30, 30), null, array('repeat-xy', 5, 5));

// Apply the watermark
$tgWatermark->addWatermark($thumbGen);

// Update the original thumbnail data with the watermarked version
$thumbGen->updateThumbnailData($tgWatermark->getThumbnailData(), true);

// Output the thumbnail
$thumbGen->outputThumbnail();