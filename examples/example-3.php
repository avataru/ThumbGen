<?php

require_once('../ThumbGen.class.php');

// Disable caching
$thumbGen = new ThumbGen(false);

// 75% quality
$thumbGen->setQuality(75);

// Prepare a 200x300 JPEG thumbnail
$thumbGen->getThumbnail('images/pic.jpg', 200, 300, 'jpg');

// Output the thumbnail
$thumbGen->outputThumbnail();