<?php

require_once('../ThumbGen.class.php');

// Disable caching
$thumbGen = new ThumbGen(false);

// Prepare the thumbnail with the default settings
$thumbGen->getThumbnail('images/pic.jpg');

// Output the thumbnail
$thumbGen->outputThumbnail();