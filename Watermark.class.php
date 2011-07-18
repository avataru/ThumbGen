<?php
/**
 * ThumbGen Watermark Plugin
 *
 * This class adds a watermark to the generated thumbnails
 *
 * LICENSE: CC BY-NC-SA 3.0
 * http://creativecommons.org/licenses/by-nc-sa/3.0/
 *
 * Requires PHP 5.2+
 *
 * @package ThumbGen
 * @subpackage Plugin
 * @version 1.1.1
 * @link https://github.com/avataru/ThumbGen
 * @author Mihai Zaharie <mihai@zaharie.ro>
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/   CC BY-NC-SA 3.0
 */

namespace ThumbGen;
require_once('Plugin.class.php');
class Watermark extends Plugin
{
    /**
     * Watermark image file
     *
     * @var string
     */
    protected $watermarkImage       = null;

    /**
     * Watermark dimensions
     *
     * @var array of width and height
     */
    protected $watermarkDimensions  = array(
        'width'                     => 20,
        'height'                    => 10
    );

    /**
     * Watermark position
     *
     * @var array of horizontal and vertical alignment, x and y
     */
    protected $watermarkPosition    = array(
        'hAlign'                    => 'right',
        'vAlign'                    => 'bottom',
        'x'                         => 5,
        'y'                         => 5
    );

    /**
     * Watermark pattern repetition
     * Type values: no-repeat, repeat-x, repeat-y, repeat-xy
     *
     * @var array of type and padding
     */
    protected $watermarkRepetition  = array(
        'type'                      => 'no-repeat',
        'paddingX'                  => 0,
        'paddingY'                  => 0
    );

    /**
     * Watermark opacity (percentage)
     * Values: 0-100
     *
     * @var integer
     */
    protected $watermarkOpacity     = 75;


    /**
     * Adds the watermark to the thumbnail
     *
     * @param ThumbGen $thumbGen ThumbGen object
     * @return boolean Returns true if the watermark was applied sucessfully
     */
    public function addWatermark(\ThumbGen $thumbGen)
    {
        $thumbnail = imagecreatefromstring($thumbGen->getThumbnailData());
        $width = $thumbGen->thumbnailDimensions['width'];
        $height = $thumbGen->thumbnailDimensions['height'];

        if ($thumbnail != null)
        {
            $imWatermark = imagecreatefrompng($this->watermarkImage);
            imagealphablending($imWatermark, false);
            imagesavealpha($imWatermark, true);

            list($watermarkSourceWidth, $watermarkSourceHeight) = getimagesize($this->watermarkImage);
            $thumbnailWidth = ($width != null && is_int($width) && $width > 0) ? $width : $this->thumbnailDimensions['width'];
            $thumbnailHeight = ($height != null && is_int($height) && $height > 0) ? $height : $this->thumbnailDimensions['height'];

            if ($this->watermarkPosition['hAlign'] == 'right')
            {
                $position['x'] = $thumbnailWidth - $this->watermarkDimensions['width'] - $this->watermarkPosition['x'];
            }
            elseif ($this->watermarkPosition['hAlign'] == 'center')
            {
                $position['x'] = round($thumbnailWidth / 2) - round($this->watermarkDimensions['width'] / 2) + $this->watermarkPosition['x'];
            }
            else
            {
                $position['x'] = $this->watermarkPosition['x'];
            }

            if ($this->watermarkPosition['vAlign'] == 'bottom')
            {
                $position['y'] = $thumbnailHeight - $this->watermarkDimensions['height'] - $this->watermarkPosition['y'];
            }
            elseif ($this->watermarkPosition['vAlign'] == 'middle')
            {
                $position['y'] = round($thumbnailHeight / 2) - round($this->watermarkDimensions['height'] / 2) + $this->watermarkPosition['y'];
            }
            else
            {
                $position['y'] = $this->watermarkPosition['y'];
            }

            // Opacity
            $imTransparentWatermark = imagecreatetruecolor($watermarkSourceWidth, $watermarkSourceHeight);
            imagealphablending($imTransparentWatermark, false);
            imagesavealpha($imTransparentWatermark, true);
            $this->imagecopymergealpha($imTransparentWatermark, $imWatermark, 0, 0, 0, 0, $watermarkSourceWidth, $watermarkSourceHeight, $this->watermarkOpacity);

            // Repetition
            switch ($this->watermarkRepetition['type'])
            {
                case 'repeat-x':
                    for ($position['x'] = 0; $position['x'] < $thumbnailWidth; $position['x'] = $position['x'] + $this->watermarkDimensions['width'] + $this->watermarkRepetition['paddingX'])
                    {
                        imagecopyresampled($thumbnail, $imTransparentWatermark, $position['x'], $position['y'], 0, 0, $this->watermarkDimensions['width'], $this->watermarkDimensions['height'], $watermarkSourceWidth, $watermarkSourceHeight);
                    }
                    break;
                case 'repeat-y':
                    for ($position['y'] = 0; $position['y'] < $thumbnailHeight; $position['y'] = $position['y'] + $this->watermarkDimensions['height'] + $this->watermarkRepetition['paddingY'])
                    {
                        imagecopyresampled($thumbnail, $imTransparentWatermark, $position['x'], $position['y'], 0, 0, $this->watermarkDimensions['width'], $this->watermarkDimensions['height'], $watermarkSourceWidth, $watermarkSourceHeight);
                    }
                    break;
                case 'repeat-xy':
                    for ($position['x'] = 0; $position['x'] < $thumbnailWidth; $position['x'] = $position['x'] + $this->watermarkDimensions['width'] + $this->watermarkRepetition['paddingX'])
                    {
                        for ($position['y'] = 0; $position['y'] < $thumbnailHeight; $position['y'] = $position['y'] + $this->watermarkDimensions['height'] + $this->watermarkRepetition['paddingY'])
                        {
                            imagecopyresampled($thumbnail, $imTransparentWatermark, $position['x'], $position['y'], 0, 0, $this->watermarkDimensions['width'], $this->watermarkDimensions['height'], $watermarkSourceWidth, $watermarkSourceHeight);
                        }
                    }
                    break;
                case 'no-repeat':
                default:
                    imagecopyresampled($thumbnail, $imTransparentWatermark, $position['x'], $position['y'], 0, 0, $this->watermarkDimensions['width'], $this->watermarkDimensions['height'], $watermarkSourceWidth, $watermarkSourceHeight);
            }

            // Save the image data for future processing
            ob_start();
            imagegd2($thumbnail, null, null, IMG_GD2_RAW);
            $this->thumbnail = ob_get_clean();

            return true;
        }

        return false;
    }

    /**
     * Sets the watermark
     *
     * @param string $watermark OPTIONAL Watermark image path
     * @param array $dimensions OPTIONAL Watermark width and height in pixels
     * @param array $position OPTIONAL Watermark vertical and horizontal alignment and x and y coordinates
     * @param mixed $repetition OPTIONAL Watermark repetition type and padding if needed
     * @return bool
     */
    public function setWatermark($watermark = null, $opacity = null, array $dimensions = null, array $position = null, $repetition = null)
    {
        if ($watermark != null && !$this->setWatermarkImage($watermark))
        {
            $this->throwError('The watermark file does not exist or is not a valid PNG image');
            return false;
        }

        if ($opacity != null && !$this->setWatermarkOpacity($opacity))
        {
            $this->throwError('The watermark opacity is not valid');
            return false;
        }

        if ($dimensions != null && !$this->setWatermarkDimensions($dimensions[0], $dimensions[1]))
        {
            $this->throwError('The watermark dimensions are not valid');
            return false;
        }

        if ($position != null && !$this->setWatermarkPosition($position[0], $position[1], $position[2], $position[3]))
        {
            $this->throwError('The watermark position is not valid');
            return false;
        }

        if ($repetition != null)
        {
            if (
                (is_array($repetition) && !$this->setWatermarkRepetition($repetition[0], $repetition[1], $repetition[2]))
                || ($repetition == 'no-repeat' && !$this->setWatermarkRepetition($repetition))
            ){
                $this->throwError('The watermark repetition is not valid');
                return false;
            }
        }

        return true;
    }

    /**
     * Sets the watermark image
     *
     * @param string $watermark Watermark image path
     * @return bool
     */
    public function setWatermarkImage($watermark)
    {
        if (file_exists($watermark))
        {
            $imageInfo = getimagesize($watermark);
            if ($imageInfo && $imageInfo['2'] == IMAGETYPE_PNG)
            {
                $this->watermarkImage = $watermark;
                return true;
            }
        }

        return false;
    }

    /**
     * Sets the watermark dimensions
     *
     * @param integer $width Watermark width in pixels
     * @param integer $height Watermark height in pixels
     * @return bool
     */
    public function setWatermarkDimensions($width, $height)
    {
        if (is_int($width) && is_int($height) && $width > 0 && $height > 0)
        {
            $this->watermarkDimensions['width'] = $width;
            $this->watermarkDimensions['height'] = $height;
            return true;
        }
        return false;
    }

    /**
     * Sets the watermark position
     *
     * @param string $vAlign Vertical watermark alignment (top, middle or bottom)
     * @param string $hAlign Horizontal watermark alignment (left, center or right)
     * @param integer $x Watermark x coordinate
     * @param integer $y Watermark y coordinate
     * @return bool
     */
    public function setWatermarkPosition($hAlign, $vAlign, $x, $y)
    {
        if (in_array($hAlign, array('left', 'center', 'right')) && in_array($vAlign, array('top', 'middle', 'bottom')) && is_int($x) && is_int($y))
        {
            $this->watermarkPosition = array(
                'hAlign' => $hAlign,
                'vAlign' => $vAlign,
                'x' => $x,
                'y' => $y
            );
            return true;
        }
        return false;
    }

    /**
     * Sets the watermark opacity
     *
     * @param integer $opacity Watermark opacity in percents
     * @return bool
     */
    public function setWatermarkOpacity($opacity)
    {
        if (is_int($opacity) && $opacity >= 0 && $opacity <= 100)
        {
            $this->watermarkOpacity = $opacity;
            return true;
        }
        return false;
    }

    /**
     * Sets the watermark repetition
     *
     * @param string $repetition Watermark repetition
     * @return bool
     */
    public function setWatermarkRepetition($type, $paddingX = 0, $paddingY = 0)
    {
        if (in_array($type, array('no-repeat', 'repeat-x', 'repeat-y', 'repeat-xy')) && is_int($paddingX) && is_int($paddingY))
        {
            $this->watermarkRepetition = array(
                'type' => $type,
                'paddingX' => $paddingX,
                'paddingY' => $paddingY
            );
            return true;
        }
        return false;
    }

    /**
     * PNG ALPHA CHANNEL SUPPORT for imagecopymerge();
     * This is a function like imagecopymerge but it handle alpha channel well!!!
     **/

    // A fix to get a function like imagecopymerge WITH ALPHA SUPPORT
    // Main script by aiden dot mail at freemail dot hu
    // Transformed to imagecopymerge_alpha() by rodrigo dot polo at gmail dot com
    private function imagecopymergealpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
    {
        if(!isset($pct))
        {
            return false;
        }

        $pct /= 100;

        // Get image width and height
        $w = imagesx($src_im);
        $h = imagesy($src_im);

        // Turn alpha blending off
        imagealphablending($src_im, false);

        // Find the most opaque pixel in the image (the one with the smallest alpha value)
        $minalpha = 127;
        for($x = 0; $x < $w; $x++)
        {
            for($y = 0; $y < $h; $y++)
            {
                $alpha = (imagecolorat($src_im, $x, $y) >> 24) & 0xFF;
                if($alpha < $minalpha)
                {
                    $minalpha = $alpha;
                }
            }
        }

        // Loop through image pixels and modify alpha for each
        for($x = 0; $x < $w; $x++)
        {
            for($y = 0; $y < $h; $y++)
            {
                // Get current alpha value (represents the TANSPARENCY!)
                $colorxy = imagecolorat($src_im, $x, $y);
                $alpha = ($colorxy >> 24) & 0xFF;

                // Calculate new alpha
                if ($minalpha !== 127)
                {
                    $alpha = 127 + 127 * $pct * ($alpha - 127) / (127 - $minalpha);
                }
                else
                {
                    $alpha += 127 * $pct;
                }
                // Get the color index with new alpha
                $alphacolorxy = imagecolorallocatealpha($src_im, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);

                // Set pixel with the new color + opacity
                if(!imagesetpixel($src_im, $x, $y, $alphacolorxy))
                {
                    return false;
                }
            }
        }

        // The image copy
        imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
    }
}