<?php

require_once('../ThumbGen.class.php');

// Disable caching
$thumbGen = new ThumbGen(false);

// Output the thumbnail with the default settings
$thumbGen->getThumbnail('images/pic.jpg');