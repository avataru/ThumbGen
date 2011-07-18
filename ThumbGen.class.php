<?php
/**
 * ThumbGen
 *
 * This class generates thumbnails for input images
 *
 * LICENSE: CC BY-NC-SA 3.0
 * http://creativecommons.org/licenses/by-nc-sa/3.0/
 *
 * Requires PHP 5.2+
 *
 * @package ThumbGen
 * @subpackage Core
 * @version 1.1.1
 * @link https://github.com/avataru/ThumbGen
 * @author Mihai Zaharie <mihai@zaharie.ro>
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/   CC BY-NC-SA 3.0
 */

class ThumbGen
{
    /**
     * Thumbnail dimensions
     *
     * @var array of width and height
     */
    protected $thumbnailDimensions  = array(
        'width'                     => 200,
        'height'                    => 150
    );

    /**
     * Thumbnail format
     *
     * @var string
     */
    protected $thumbnailFormat      = 'jpg';

    /**
     * Thumbnail quality (percentage)
     * Values: 0-100
     *
     * @var integer
     */
    protected $thumbnailQuality     = 80;

    /**
     * Cache status
     *
     * @var bool
     */
    protected $useCache             = false;

    /**
     * Cache location
     *
     * @var string
     */
    protected $cacheLocation        = null;

    /**
     * Cache duration in seconds
     *
     * @var integer
     */
    protected $cacheDuration        = 0;

    /**
     * Supported input image formats
     *
     * @var array of IMAGETYPE_XXX constants
     */
    protected $validImageTypes      = array(
        IMAGETYPE_GIF,
        IMAGETYPE_JPEG,
        IMAGETYPE_PNG
    );

    /**
     * Supported thumbnail formats
     *
     * @var array of file types
     */
    protected $validThumbnailTypes  = array(
        'gif',
        'jpg',
        'png'
    );

    /**
     * The thumbnail (from cache or processed)
     *
     * @var image
     */
    protected $thumbnail            = null;


    /**
     * Class constructor
     *
     * @param boolean $useCache OPTIONAL Whether to use cache or not
     * @param string $cacheLocation OPTIONAL Path to the cache folder
     * @param integer $cacheDuration OPTIONAL Cache duration in seconds
     * @return boolean Returns true
     */
    public function __construct($useCache = false, $cacheLocation = '', $cacheDuration = 86400)
    {
        $this->setCaching($useCache);
        $this->setCacheLocation($cacheLocation);
        $this->setCacheDuration($cacheDuration);
        return true;
    }

    /**
     * Prepares the thumbnail (gets the cached version or a freshly generated version)
     *
     * @param string $sourceImage Source image path
     * @param integer $width OPTIONAL Thumbnail width in pixels
     * @param integer $height OPTIONAL Thumbnail height in pixels
     * @param string $format OPTIONAL Thumbnail format
     * @return boolean Returns true if the thumbnail data was sucessfully generated
     */
    public function getThumbnail($sourceImage, $width = null, $height = null, $format = null)
    {
        $format = ($format != null && in_array($format, $this->validThumbnailTypes)) ? $format : $this->thumbnailFormat;

        if ($this->useCache && $this->isCached($sourceImage, $width, $height, $format))
        {
            // Load the cached version
            $this->getCachedThumbnail($sourceImage, $width, $height, $format);
            return true;
        }
        else
        {
            $this->makeThumbnail($sourceImage, $width, $height);

            // Cache the thumbnail
            if ($this->useCache)
            {
                $this->cacheThumbnail($this->getCachedFilePath($sourceImage, $width, $height, $format), $format);
            }
            return true;
        }

        return false;
    }

    /**
     * Processes the input image and generates the thumbnail
     *
     * @param string $sourceImage Source image path
     * @param integer $width OPTIONAL Thumbnail width in pixels
     * @param integer $height OPTIONAL Thumbnail height in pixels
     * @return boolean Returns true if the image was sucessfully processed
     */
    protected function makeThumbnail($sourceImage, $width = null, $height = null)
    {
        // Check if the file exists
        if (file_exists($sourceImage))
        {
            // Check if the file is a valid image
            $imageInfo = getimagesize($sourceImage);
            if ($imageInfo && in_array($imageInfo['2'], $this->validImageTypes))
            {
                // Load the image
                switch ($imageInfo[2]) {
                    case 1:
                        $imSource = imagecreatefromgif($sourceImage);
                        break;
                    case 2:
                        $imSource = imagecreatefromjpeg($sourceImage);
                        break;
                    case 3:
                        $imSource = imagecreatefrompng($sourceImage);
                        break;
                    default:
                        $this->throwError('The file is not a valid image');
                        return false;
                }

                // Output dimensions
                $width = ($width != null && is_int($width) && $width > 0) ? $width : $this->thumbnailDimensions['width'];
                $height = ($height != null && is_int($height) && $height > 0) ? $height : $this->thumbnailDimensions['height'];

                // Generate the thumbnail
                $imThumbnail = imagecreatetruecolor($width, $height);

                $data['input']['ratio'] = $imageInfo[0] / $imageInfo[1];
                $data['output']['ratio'] = $width / $height;
                $data['resizeBy'] = ($data['input']['ratio'] > $data['output']['ratio']) ? 'height' : 'width';
                $data['resizeTo']['width'] = ($data['resizeBy'] == 'width') ? $width : ceil($height * $imageInfo[0] / $imageInfo[1]);
                $data['resizeTo']['height'] = ($data['resizeBy'] == 'height') ? $height : ceil($width * $imageInfo[1] / $imageInfo[0]);
                $data['cropFrom']['x'] = ($data['resizeBy'] == 'height') ? floor(($data['resizeTo']['width'] - $width) / 2) : 0;
                $data['cropFrom']['y'] = ($data['resizeBy'] == 'width') ? floor(($data['resizeTo']['height'] - $height) / 2) : 0;

                if ($imageInfo[0] == $width && $imageInfo[1] == $height)
                {
                    // Skip resizing if it's not needed
                    $imThumbnail = $imSource;
                }
                else
                {
                    $imResized = imagecreatetruecolor($data['resizeTo']['width'], $data['resizeTo']['height']);
                    imagecopyresampled($imResized, $imSource, 0, 0, 0, 0, $data['resizeTo']['width'], $data['resizeTo']['height'], $imageInfo[0], $imageInfo[1]);
                    imagecopy($imThumbnail, $imResized, 0, 0, $data['cropFrom']['x'], $data['cropFrom']['y'], $width, $height);
                    imagedestroy($imResized);
                }

                imagedestroy($imSource);

                // Save the image data for future processing
                ob_start();
                imagegd2($imThumbnail, null, null, IMG_GD2_RAW);
                $this->thumbnail = ob_get_clean();

                imagedestroy($imThumbnail);
                return true;
            }
            else
            {
                $this->throwError('The file is not a valid image');
                return false;
            }
        }
        else
        {
            $this->throwError('The file does not exist');
            return false;
        }

        return false;
    }

    /**
     * Outputs the thumbnail
     *
     * @return image Outputs the header and the processed thumbnail or boolean false
     */
    public function outputThumbnail()
    {
        if ($this->thumbnail != null)
        {
            $thumbnailData = imagecreatefromstring($this->thumbnail);

            switch($this->thumbnailFormat)
            {
                case 'gif':
                    header('Content-Type: image/gif');
                    imagegif($thumbnailData);
                    break;
                case 'jpg':
                    header('Content-Type: image/jpeg');
                    imagejpeg($thumbnailData, null, $this->thumbnailQuality);
                    break;
                case 'png':
                    header('Content-Type: image/png');
                    $compression = round(9 - ($this->thumbnailQuality * 0.09), 0, PHP_ROUND_HALF_UP);
                    imagepng($thumbnailData, null, $compression);
                    break;
                default:
                    $this->throwError('The output format is not supported');
                    return false;
            }
        }

        return false;
    }

    /**
     * Sets the thumbnail format
     *
     * @param string $format Thumbnail format
     * @return boolean Returns true if the format was set sucessfully
     */
    public function setFormat($format)
    {
        if (in_array($format, $this->validThumbnailTypes))
        {
            $this->thumbnailFormat =  $format;
            return true;
        }
        return false;
    }

    /**
     * Sets the thumbnail dimensions
     *
     * @param integer $width Thumbnail width in pixels
     * @param integer $height Thumbnail height in pixels
     * @return boolean Returns true if the dimensions were set sucessfully
     */
    public function setDimensions($width, $height)
    {
        if (is_int($width) && is_int($height) && $width > 0 && $height > 0)
        {
            $this->thumbnailDimensions['width'] = $width;
            $this->thumbnailDimensions['height'] = $height;
            return true;
        }
        return false;
    }

    /**
     * Sets the quality of the thumbnail
     *
     * @param integer $quality Thumbnail quality in percents
     * @return boolean Returns true if the quality was set sucessfully
     */
    public function setQuality($quality)
    {
        if (is_int($quality) && $quality >= 0 && $quality <= 100)
        {
            $this->thumbnailQuality = $quality;
            return true;
        }
        return false;
    }

    /**
     * Sets the caching status
     *
     * @param boolean $useCache Enable or disable caching
     * @return boolean Returns true if the caching status was set sucessfully
     */
    public function setCaching($useCache)
    {
        $this->useCache = ($useCache) ? true : false;
        return true;
    }

    /**
     * Sets the cache location
     *
     * @param string $location Cache folder path
     * @return boolean Returns true if the cache location was set sucessfully
     */
    public function setCacheLocation($location)
    {
        if (is_dir($location) && is_writable($location))
        {
            $this->cacheLocation = $location;
            $this->cacheLocation .= (substr($location, -1) != '/') ? '/' : '';
            return true;
        }
        return false;
    }

    /**
     * Sets the cache duration
     *
     * @param integer $duration Cache duration in seconds
     * @return boolean Returns true if the cache duration was set sucessfully
     */
    public function setCacheDuration($duration)
    {
        if (is_int($duration) && $duration >= 0)
        {
            $this->cacheDuration = $duration;
            return true;
        }
        return false;
    }

    /**
     * Updates or saves the cached version of the thumbnail
     *
     * @param string $fileName The file name of cached thumbnail
     * @param string $format OPTIONAL Thumbnail format
     * @return boolean Returns true if the thumbnail image was succesfully cached
     */
    protected function cacheThumbnail($fileName, $format = null)
    {
        if ($this->thumbnail != null)
        {
            $thumbnailData = imagecreatefromstring($this->thumbnail);

            switch($format)
            {
                case 'gif':
                    imagegif($thumbnailData, $fileName);
                    break;
                case 'jpg':
                    imagejpeg($thumbnailData, $fileName, $this->thumbnailQuality);
                    break;
                case 'png':
                    $compression = round(9 - ($this->thumbnailQuality * 0.09), 0, PHP_ROUND_HALF_UP);
                    imagepng($thumbnailData, $fileName, $compression);
                    break;
                default:
                    $this->throwError('The output format is not supported');
                    return false;
            }
        }

        return true;
    }

    /**
     * Checks the thumbnail's cache status
     *
     * @param string $sourceImage Source image path
     * @param integer $width OPTIONAL Thumbnail width in pixels
     * @param integer $height OPTIONAL Thumbnail height in pixels
     * @param string $format OPTIONAL Thumbnail format
     * @return boolean Returns false if there is no cached version or if the cache is expired
     */
    public function isCached($sourceImage, $width = null, $height = null, $format = null)
    {
        if (file_exists($sourceImage))
        {
            $cachedImage = $this->getCachedFilePath($sourceImage, $width, $height, $format);

            if (file_exists($cachedImage))
            {
                // For infinite cache duration (until the source image changes)
                if ($this->cacheDuration <= 0 && filemtime($sourceImage) <= filemtime($cachedImage))
                {
                    return true;
                }
                // For a limited cache duration
                elseif (time() <= filemtime($cachedImage) + $this->cacheDuration)
                {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the thumbnail's cache path
     *
     * @param string $sourceImage Source image path
     * @param integer $width OPTIONAL Thumbnail width in pixels
     * @param integer $height OPTIONAL Thumbnail height in pixels
     * @param string $format OPTIONAL Thumbnail format
     * @return string Returns the cached image path
     */
    public function getCachedFilePath($sourceImage, $width = null, $height = null, $format = null)
    {
        // Get the cache folder
        $cachedImage = $this->cacheLocation;

        // Add / if it's missing
        $cachedImage .= (substr($cachedImage, -1) != '/') ? '/' : '';

        // Append the filename
        $cachedImage .= pathinfo($sourceImage, PATHINFO_FILENAME);

        // Append the dimensions
        $cachedImage .= '_';
        $cachedImage .= ($width != null && is_int($width) && $width > 0) ? $width : $this->thumbnailDimensions['width'];
        $cachedImage .= 'x';
        $cachedImage .= ($height != null && is_int($height) && $height > 0) ? $height : $this->thumbnailDimensions['height'];

        // Append the extension
        $cachedImage .= '.';
        $cachedImage .= ($format != null && in_array($format, $this->validThumbnailTypes)) ? $format : $this->thumbnailFormat;

        return $cachedImage;
    }

    /**
     * Returns the cached thumbnail
     *
     * @param string $sourceImage Source image path
     * @param integer $width OPTIONAL Thumbnail width in pixels
     * @param integer $height OPTIONAL Thumbnail height in pixels
     * @param string $format OPTIONAL Thumbnail format
     * @return boolean Returns true if the cached version was retrieved sucessfully
     */
    protected function getCachedThumbnail($sourceImage, $width = null, $height = null, $format = null)
    {
        $cachedImage = $this->getCachedFilePath($sourceImage, $width, $height, $format);

        if (file_exists($cachedImage))
        {
            $imageInfo = getimagesize($cachedImage);
            if ($imageInfo && in_array($imageInfo['2'], $this->validImageTypes))
            {
                // Load the image
                switch ($imageInfo[2]) {
                    case 1:
                        $imThumbnail = imagecreatefromgif($cachedImage);
                        break;
                    case 2:
                        $imThumbnail = imagecreatefromjpeg($cachedImage);
                        break;
                    case 3:
                        $imThumbnail = imagecreatefrompng($cachedImage);
                        break;
                    default:
                        $this->addError('The cached thumbnail is not a valid image');
                        return false;
                }

                // Save the image data for future processing
                ob_start();
                imagegd2($imThumbnail, null, null, IMG_GD2_RAW);
                $this->thumbnail = ob_get_clean();

                return true;
            }
            return false;
        }

        return false;
    }

    /**
     * Retrieves the thumbnail data so it can be passed to plugins
     *
     * @return string Returns the thumbnail data
     */
    public function getThumbnailData()
    {
        return $this->thumbnail;
    }

    /**
     * Updates the thumbnail data after it was processed externally
     *
     * @param string $thumbnail Thumbnail data
     * @param boolean $checkData OPTIONAL Check if the data is a valid image
     * @return boolean Returns false if the data is invalid
     */
    public function updateThumbnailData($thumbnail, $checkData = false)
    {
        if ($checkData && !@imagecreatefromstring($thumbnail))
        {
            // The image type is unsupported, the data is not in a recognised
            // format, or the image is corrupt and cannot be loaded
            $this->throwError('Invalid image data');
            return false;
        }

        $this->thumbnail = $thumbnail;
        return true;
    }

    /**
     * Throws an error
     *
     * @param string $error Error text
     * @return boolean Returns false if there is an error
     */
    protected function throwError($error)
    {
        die($error);
        return false;
    }

    /**
     * Magic!
     * 
     * @param string $name Property name
     * @return mixed property
     */
    public function __get($name)
    {
        $validProperties = array(
            'thumbnailDimensions',
            'thumbnailFormat',
            'thumbnailQuality',
            'useCache',
            'cacheLocation',
            'cacheDuration',
            'validImageTypes',
            'validThumbnailTypes'
        );

        if (in_array($name, $validProperties))
        {
            return $this->$name;
        }
        else
        {
            $trace = debug_backtrace();
            trigger_error(
                'Undefined property via __get(): ' . $name .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line'],
                E_USER_NOTICE);
            return null;
        }
    }
}