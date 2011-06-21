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
 * @subpackage ThumbGen_Core
 * @version 1.0
 * @link https://github.com/avataru/ThumbGen
 * @author Mihai Zaharie <mihai@zaharie.ro>
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/   CC BY-NC-SA 3.0
 */

// TODO: remove trailing spaces, replace tabs with spaces

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
    protected $format      = 'jpg';

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
    protected $cacheDuration        = 86400;

    /**
     * Supported input image formats
     *
     * @var array of IMAGETYPE_XXX constants
     */
    protected $validImageTypes        = array(
        IMAGETYPE_GIF,
        IMAGETYPE_JPEG,
        IMAGETYPE_PNG
    );

    /**
     * Supported thumbnail formats
     *
     * @var array of file types
     */
    protected $validThumbnailTypes    = array(
        'gif',
        'jpg',
        'png'
    );


    /**
     * Class constructor
     *
     * @param bool $useCache OPTIONAL Whether to use cache or not
     * @param string $cacheLocation OPTIONAL Path to the cache folder
     * @param integer $cacheDuration OPTIONAL Cache duration in seconds
     */
    public function __construct($useCache = false, $cacheLocation = '', $cacheDuration = 86400)
    {
        $this->setCaching($useCache);
        $this->setCacheLocation($cacheLocation);
        $this->setCacheDuration($cacheDuration);
        return true;
    }

    /**
     * Fetches the thumbnail for display
     *
     * @param string $sourceImage Source image path
     * @param integer $width OPTIONAL Thumbnail width in pixels
     * @param integer $height OPTIONAL Thumbnail height in pixels
     * @param string $format OPTIONAL Thumbnail format
     * @return image
     */
    public function getThumbnail($sourceImage, $width = null, $height = null, $format = null)
    {
        $format = ($format != null && in_array($format, $this->validThumbnailTypes)) ? $format : $this->format;

        if ($this->useCache && $this->isCached($sourceImage, $width, $height, $format))
        {
            // Load the cached version
            $thumbnail = $this->getCachedThumbnail($sourceImage, $width, $height, $format);
        }
        else
        {
            $thumbnail = $this->makeThumbnail($sourceImage, $width, $height);

            // Cache the thumbnail
            if ($this->useCache)
            {
                $this->cacheThumbnail($thumbnail, $this->getCachedFilePath($sourceImage, $width, $height, $format), $format);
            }
        }

        if ($thumbnail)
        {
            switch($format)
            {
                case 'gif':
                    header('Content-Type: image/gif');
                    imagegif($thumbnail);
                    break;
                case 'jpg':
                    header('Content-Type: image/jpeg');
                    imagejpeg($thumbnail, null, $this->thumbnailQuality);
                    break;
                case 'png':
                    $compression = round(9 - ($this->thumbnailQuality * 0.09), 0, PHP_ROUND_HALF_UP);
                    header('Content-Type: image/png');
                    imagepng($thumbnail, null, $compression);
                    break;
                default:
                    $this->throwError('The output format is not supported');
                    return false;
            }
        }

        // Cleanup
        imagedestroy($thumbnail);

        return false;
    }

    /**
     * Processes the input image and generates the thumbnail
     *
     * @param string $sourceImage Source image path
     * @param integer $width OPTIONAL Thumbnail width in pixels
     * @param integer $height OPTIONAL Thumbnail height in pixels
     * @return mixed Returns the image object or false
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
                return $imThumbnail;
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
     * Sets the thumbnail format
     *
     * @param string $format Thumbnail format
     * @return bool
     */
    public function setFormat($format)
    {
        if (in_array($format, $this->validThumbnailTypes))
        {
            $this->format =  $format;
            return true;
        }
        return false;
    }

    /**
     * Sets the thumbnail dimensions
     *
     * @param integer $width Thumbnail width in pixels
     * @param integer $height Thumbnail height in pixels
     * @return bool
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
     * @return bool
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
     * @param bool $useCache Enable or disable caching
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @param image $data Image data
     * @param string $fileName The file name of cached thumbnail
     * @param string $format OPTIONAL Thumbnail format
     */
    protected function cacheThumbnail($thumbnailData, $fileName, $format = null)
    {
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

        return true;
    }

    /**
     * Checks the thumbnail's cache status
     *
     * @param string $sourceImage Source image path
     * @param integer $width OPTIONAL Thumbnail width in pixels
     * @param integer $height OPTIONAL Thumbnail height in pixels
     * @param string $format OPTIONAL Thumbnail format
     * @return bool
     */
    public function isCached($sourceImage, $width = null, $height = null, $format = null)
    {
        if (file_exists($sourceImage))
        {
            $cachedImage = $this->getCachedFilePath($sourceImage, $width, $height, $format);

            if (file_exists($cachedImage) && filemtime($sourceImage) <= filemtime($cachedImage))
            {
                return true;
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
     * @return string
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
        $cachedImage .= ($format != null && in_array($format, $this->validThumbnailTypes)) ? $format : $this->format;

        return $cachedImage;
    }

    /**
     * Returns the cached thumbnail
     *
     * @param string $sourceImage Source image path
     * @param integer $width OPTIONAL Thumbnail width in pixels
     * @param integer $height OPTIONAL Thumbnail height in pixels
     * @param string $format OPTIONAL Thumbnail format
     * @return image
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
                return $imThumbnail;
            }
            return false;
        }

        return false;
    }

    /**
     * Throws an error
     *
     * @param string $error Error text
     * @return false
     */
    protected function throwError($error)
    {
        die($error);
        return false;
    }
}