<?php

require_once('../ThumbGen.class.php');

// Enable caching, in the "cache" folder with a 1 minute expiration
$thumbGen = new ThumbGen(true, 'cache', 60);

// JPEG thumbnail
$thumbGen->setFormat('png');

// 100% quality
$thumbGen->setQuality(100);

// 150 x 260 pixels
$thumbGen->setDimensions(150, 260);

// Prepare the thumbnail
$thumbGen->getThumbnail('images/pic.jpg');

// Output the thumbnail
$thumbGen->outputThumbnail();