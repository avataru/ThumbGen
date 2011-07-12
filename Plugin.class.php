<?php
/**
 * ThumbGen Plugin
 *
 * This abstract class is a template for ThumbGen plugins
 *
 * LICENSE: CC BY-NC-SA 3.0
 * http://creativecommons.org/licenses/by-nc-sa/3.0/
 *
 * Requires PHP 5.2+
 *
 * @package ThumbGen
 * @subpackage Core
 * @version 1.1.0
 * @link https://github.com/avataru/ThumbGen
 * @author Mihai Zaharie <mihai@zaharie.ro>
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/   CC BY-NC-SA 3.0
 */

namespace ThumbGen;
abstract class Plugin
{
    /**
     * The thumbnail
     *
     * @var image
     */
    protected $thumbnail            = null;


    /**
     * Returns the thumbnail data for further processing
     *
     * @return string Returns the thumbnail data
     */
    public function getThumbnailData()
    {
        return $this->thumbnail;
    }
}