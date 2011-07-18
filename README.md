ThumbGen
========

Thumbnail generator with caching and watermark support.

How to use
----------

1. Load the core class in the PHP file that will output the thumbnail:

    require_once('path_to/ThumbGen.class.php');

2. Create a new ThumbGen object:

    $thumbGen = new ThumbGen();

3. Setup the ThumbGen object as needed and generate the thumbnail:

    $thumbGen->getThumbnail('path_to/image.jpg');

4. Output the thumbnail image:

    $thumbGen->outputThumbnail();

Note: If you want to use caching, make sure the `cache` folder is writable.

Watermark
----------

1. In order to use the Watermark features you need to also load the Watermark
   plugin class:

    require_once('path_to/ThumbGen.class.php');
    require_once('path_to/Watermark.class.php');

2. Create a Watermark object:

    $tgWatermark = new \ThumbGen\Watermark();

3. Setup the Watermark options:

    $tgWatermark->setWatermark('path_to/watermark.png');

4. Apply the watermark to the thumbnail:

    $tgWatermark->addWatermark($thumbGen);

5. Update the thumbnail with the watermarked version:

    $thumbGen->updateThumbnailData($tgWatermark->getThumbnailData());

6. And finally, generate the thumbnail:

    $thumbGen->outputThumbnail();

Note: The watermark image must be PNG.

Please see the examples for actual code.

Changelog
---------

##### 1.1.1
 - Added the magic method __get() in the ThumbGen class so properties can be 
   easily retrieved from a plugin. Only specific properties are available.

##### 1.1.0

 - Changed the plugin arhitecture so the plugins are now separate objects that
   extend the abstract Plugin object.

##### 1.0.1

 - Fixed the caching to use the set duration instead of being infinite (util the
   source image was modified);

 - Watermark: the setWatermark repetition doesn't need to be an array and
   include the padding when it's set to 'no-repeat'.

##### 1.0.0

 - Initial version


To Do
-----

 - Documentation
 - Unit testing

----------------------------------

Copyright (c) 2011 [Mihai Zaharie](http://mihai.zaharie.ro)

License: [CC BY-NC-SA 3.0](http://creativecommons.org/licenses/by-nc-sa/3.0/)