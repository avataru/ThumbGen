<?php

require_once('../ThumbGen.class.php');
require_once('../Watermark.class.php');

// Enable caching, in the "cache" folder with a 2 minutes expiration
$thumbGen = new Watermark(true, 'cache', 120);

// JPEG thumbnail
$thumbGen->setFormat('jpg');

// 90% quality
$thumbGen->setQuality(90);

// 320 x 240 pixels
$thumbGen->setDimensions(340, 240);

// Set a watermark with 50% opacity, 30 x 30 pixels, in the center of the image,
// repeated over the whole thumbnail with 3 pixels padding horizontally and
// vertically
$thumbGen->setWatermark('images/wm.png', 50, array(30, 30), null, array('repeat-xy', 5, 5));

// Output the thumbnail
$thumbGen->getThumbnail('images/pic.jpg');