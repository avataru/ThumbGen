<?php

require_once('../ThumbGen.class.php');

// Disable caching
$thumbGen = new ThumbGen(false);

// 75% quality
$thumbGen->setQuality(90);

// Output a JPEG thumbnail at the default dimensions
$thumbGen->getThumbnail('images/pic.jpg', null, null, 'jpg');