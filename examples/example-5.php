<?php

require_once('../ThumbGen.class.php');
require_once('../Watermark.class.php');

// Enable caching, in the "cache" folder with a 2 minute expiration
$thumbGen = new Watermark(true, 'cache', 120);

// JPEG thumbnail
$thumbGen->setFormat('jpg');

// 90% quality
$thumbGen->setQuality(90);

// 320 x 240 pixels
$thumbGen->setDimensions(320, 240);

// Watermark image
$thumbGen->setWatermarkImage('images/wm.png');

// 20 x 20 pixels watermark
$thumbGen->setWatermarkDimensions(20, 20);

// Placed in the bottom-left corner at 5 pixels distance from the bottom and
// left edges
$thumbGen->setWatermarkPosition('bottom', 'left', 5, 5);

// 80% opacity
$thumbGen->setWatermarkOpacity(80);

// No repetition
$thumbGen->setWatermarkRepetition('no-repeat');

// Output the thumbnail
$thumbGen->getThumbnail('images/pic.jpg');