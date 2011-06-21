#ThumbGen

Thumbnail generator with caching and watermark support.

License: [CC BY-NC-SA 3.0](http://creativecommons.org/licenses/by-nc-sa/3.0/)

### How to use

1. Load the core class in the PHP file that will output the thumbnail:

    `require_once('path_to/ThumbGen.class.php');`

2. Create a new ThumbGen object:

    `$thumbGen = new ThumbGen();`
    
3. Setup the ThumbGen object as needed and generate the thumbnail:

    `$thumbGen->getThumbnail('path_to/image.jpg');`

Note: If you want to use caching, make sure the `cache` folder is writable.

#### Watermark

1. In order to use the Watermark features you need to load both the Core and 
   the Watermark classes:
   
    `require_once('path_to/ThumbGen.class.php');`
    `require_once('path_to/Watermark.class.php');`
    
2. Create a Watermark object instead of a ThumbGen object:

    `$thumbGen = new Watermark();`
    
3. Setup the Watermark options:

    `$thumbGen->setWatermark('path_to/watermark.png');`
    
4. And finally, generate the thumbnail:

    `$thumbGen->getThumbnail('path_to/image.jpg');`
    
Note: The watermark image must be PNG.

Please see the examples for actual code.

### Changelog

#### 1.0.0

 - Initial version
 
### To Do

 - Documentation
 - Unit testing
 - Modify setWatermark to support a variable number of arguments for repetition (ie. no padding needed for no-repeat)
 
Copyright (c) 2011 [Mihai Zaharie](http://mihai.zaharie.ro)