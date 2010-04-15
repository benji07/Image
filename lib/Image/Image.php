<?php

namespace Image;

use Image\Plugin;

/*
 * This file is part of the Image.
 * (c) 2010 Benjamin Lévêque (benjamin@leveque.me)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

/**
 * Image allow you to resize/crop and apply transformation on image file.
 *
 * @package    Image
 * @author     Benjamin Lévêque (benjamin@leveque.me)
 * @version    1.0.0
 */
class Image
{
  const GRAVITY_TOP_RIGHT      = 'tr';
  const GRAVITY_TOP_CENTER     = 'tc';
  const GRAVITY_TOP_LEFT       = 'tl';
  const GRAVITY_MIDDLE_RIGHT   = 'mr';
  const GRAVITY_MIDDLE_CENTER  = 'mc';
  const GRAVITY_MIDDLE_LEFT    = 'ml';
  const GRAVITY_BOTTOM_RIGHT   = 'br';
  const GRAVITY_BOTTOM_CENTER  = 'bc';
  const GRAVITY_BOTTOM_LEFT    = 'bl';

  protected $originalFilename = '';

  protected $originalImage = null;

  protected $workingImage = null;

  protected $format = '';

  protected $currentDimension = array();

  protected static $_plugins = array();

  /**
   * Create an Image objet to do something on the image file
   * @param string $image the image source file
   */
  public function __construct($image)
  {
    $this->originalFilename = $image;

    $this->_determineFormat();

    switch ($this->format)
    {
      case 'image/jpeg':
        $this->originalImage = imagecreatefromjpeg($image);
        break;
      case 'image/png':
        $this->originalImage = imagecreatefrompng($image);
        break;
      case 'image/gif':
        $this->originalImage = imagecreatefromgif($image);
        break;
      default:
        throw new \Exception('Unkown file type:'.$this->format);
        break;
    }

    $this->currentDimension = array(
            'width'  => imagesx($this->originalImage),
            'height' => imagesy($this->originalImage)
    );
  }

  public function getWorkingImage()
  {
    return $this->workingImage;
  }

  public function setWorkingImage($image)
  {
    $this->workingImage = $image;
  }

  public function getOriginalImage()
  {
    return $this->originalImage;
  }

  public function setOriginalImage($image)
  {
    $this->originalImage = $image;
  }

  public function setHeight($height)
  {
    $this->currentDimension['height'] = $height;
  }

  public function getFormat(){
    return $this->format;
  }

  /**
   * Get the current height
   * @return int the current height
   */
  public function getHeight()
  {
    return $this->currentDimension['height'];
  }


  public function setWidth($width)
  {
    $this->currentDimension['width'] = $width;
  }
  
  /**
   * Get the current width
   * @return int the current width
   */
  public function getWidth()
  {
    return $this->currentDimension['width'];
  }

  /**
   * Resize image to maxWidth and maxHeight and conserve the original ratio
   *
   * @param string $maxWidth
   * @param string $maxHeight
   * @return Image
   */
  public function resize($maxWidth = null, $maxHeight = null)
  {

    $newSize = $this->_calcSize($maxWidth, $maxHeight);
    $dw = $newSize['width'];
    $dh = $newSize['height'];
    $sw = $this->currentDimension['width'];
    $sh = $this->currentDimension['height'];

    $this->workingImage = imagecreatetruecolor($dw, $dh);

    $this->_preserveAlpha();

    imagecopyresampled($this->workingImage, $this->originalImage, 0, 0, 0, 0, $dw, $dh, $sw, $sh);

    $this->originalImage = $this->workingImage;

    $this->currentDimension = $newSize;

    return $this;
  }

  /**
   * Resize and crop the image to $height and $width with $gravity
   *
   * @param string $width The new width
   * @param string $height The new height
   * @param string $gravity Image gravity
   * @return Image
   */
  public function adaptiveResize($width, $height, $gravity = self::GRAVITY_MIDDLE_CENTER)
  {

    $old_ratio = $this->getWidth()/$this->getHeight();

    $new_ratio = $width/$height;

    if($new_ratio < $old_ratio)
    {
      $newWidth = null;
      $newHeight = $height;
    }
    else
    {
      $newWidth =  $width;
      $newHeight = $height;
    }

    $this->resize($newWidth, $newHeight);

    $this->crop($width, $height, $gravity);

    return $this;
  }

  /**
   * Crop the image to $height and $width with $gravity
   *
   * @param string $width The new width
   * @param string $height The new height
   * @param string $gravity Image gravity
   * @return Image
   */
  public function crop($width, $height, $gravity = self::GRAVITY_MIDDLE_CENTER)
  {
    $this->workingImage = imagecreatetruecolor($width, $height);

    $this->_preserveAlpha();

    $pos = $this->_calcPositionForCrop($width, $height, $gravity);

    imagecopyresampled($this->workingImage, $this->originalImage, 0, 0, $pos['x'], $pos['y'], $width, $height, $width, $height);

    $this->originalImage = $this->workingImage;

    $this->currentDimension = array('height' => $height, 'width' => $width);

    return $this;
  }

  /**
   * Save the current image on the filesystem
   * @param string $filename
   * @return Image
   */
  public function save($filename)
  {

    if(!is_writable(dirname($filename)))
    {
      throw new \RuntimeException('File is not writable: '.$filename);
    }

    switch ($this->format)
    {
      case 'image/jpeg':
        imagejpeg($this->originalImage, $filename, 100);
        break;
      case 'image/png':
        imagepng($this->originalImage, $filename);
        break;
      case 'image/gif':
        imagegif($this->originalImage, $filename);
        break;
      default:
        break;
    }

    return $this;
  }

  /**
   * Get the new image size accoriding to maxWidth, maxHeight and orignal ratio
   * @param int $maxWidth
   * @param int $maxHeight
   * @return array ths max size for respect the original ratio
   */
  protected function _calcSize($maxWidth, $maxHeight)
  {

    $ratio = $this->currentDimension['width']/$this->currentDimension['height'];
    $newHeight = 0;
    $newWidth = 0;
    if($maxHeight != null && $maxWidth != null)
    {
      if($ratio > 1)
      {
        $newWidth = $maxWidth;
        $newHeight = $maxWidth/$ratio;
      }
      else
      {
        $newHeight = $maxHeight;
        $newWidth = $maxHeight*$ratio;
      }
    }
    else if($maxHeight == null)
    {
      $newHeight = $maxWidth/$ratio;
      $newWidth = $maxWidth;
    }
    else if($maxWidth == null)
    {
      $newHeight = $maxHeight;
      $newWidth = $maxHeight*$ratio;
    }
    else
    {
      throw new \Exception('newHeight or newWidth is required');
    }

    return array('height' => floor($newHeight), 'width' => floor($newWidth));
  }

  /**
   * Remove stuff, to prevent memory leaks
   */
  public function __destruct()
  {

    if(is_resource($this->originalImage))
    {
      imagedestroy($this->originalImage);
    }

    if(is_resource($this->workingImage))
    {
      imagedestroy($this->workingImage);
    }
  }


  /**
   * Find the image ratio
   */
  protected function _determineFormat()
  {
    list($width, $height, $type, $attr) = getimagesize($this->originalFilename);

    switch ($type)
    {
      case \IMAGETYPE_JPEG:
        $this->format = 'image/jpeg';
        break;
      case \IMAGETYPE_GIF:
        $this->format = 'image/gif';
        break;
      case \IMAGETYPE_PNG:
        $this->format = 'image/png';
        break;
      default:
        $this->format = '';
    }
  }


  /**
   * Conserve alpha opacity and image transparency for gif and png image
   */
  protected function _preserveAlpha()
  {

    if($this->format == 'image/png')
    {
      imagealphablending($this->workingImage, false);

      $color = imagecolorallocatealpha($this->workingImage, 255, 255, 255, 0);

      imagefill($this->workingImage, 0, 0, $color);
      imagesavealpha($this->workingImage, true);
    }
    else if($this->format == 'image/gif')
    {


      $color = imagecolorallocate($this->workingImage, 0, 0, 0);

      imagecolortransparent($this->workingImage, $color);

      imagetruecolortopalette($this->workingImage, true, 256);
    }
  }

  /**
   * Find the cropping area
   * @param int $width
   * @param int $height
   * @param string $gravity
   * @return array the cropping area(x,y,h,w)
   */
  protected function _calcPositionForCrop($width, $height, $gravity = self::GRAVITY_MIDDLE_CENTER)
  {

    $top = 0;
    $left = 0;

    switch ($gravity)
    {
      case self::GRAVITY_TOP_CENTER:
      case self::GRAVITY_MIDDLE_CENTER:
      case self::GRAVITY_BOTTOM_CENTER:
        $left = ($this->getWidth() - $width)/2;
        break;
      case self::GRAVITY_TOP_RIGHT:
      case self::GRAVITY_MIDDLE_RIGHT:
      case self::GRAVITY_BOTTOM_RIGHT:
        $left = $this->getWidth() - $width;
        break;
      case self::GRAVITY_TOP_LEFT:
      case self::GRAVITY_MIDDLE_LEFT:
      case self::GRAVITY_BOTTOM_LEFT:
      default:
        $left = 0;
        break;
    }

    switch ($gravity)
    {
      case self::GRAVITY_MIDDLE_LEFT:
      case self::GRAVITY_MIDDLE_CENTER:
      case self::GRAVITY_MIDDLE_RIGHT:
        $top = ($this->getHeight() - $height)/2;
        break;
      case self::GRAVITY_BOTTOM_LEFT:
      case self::GRAVITY_BOTTOM_CENTER:
      case self::GRAVITY_BOTTOM_RIGHT:
        $top = ($this->getHeight() - $height);
        break;
      case self::GRAVITY_TOP_LEFT:
      case self::GRAVITY_TOP_CENTER:
      case self::GRAVITY_TOP_RIGHT:
      default:
        $top = 0;
        break;
    }


    return array('y' => $top, 'x' => $left);

  }



  /**
   * Register an image manipulation plugin
   * @param Plugin $plugin
   */
  public static function registerPlugin(Plugin $plugin)
  {
    self::$_plugins[$plugin->getName()] = $plugin;
  }



  public function __call($name, $args)
  {

    if(isset(self::$_plugins[$name]))
    {
      $plugin = self::$_plugins[$name];
      if($plugin->getImage() == null)
      {
        $plugin->setImage($this);
      }

      call_user_func_array(array($plugin, 'execute'), $args);

      return $this;
    }

    throw new \Exception('Method not found: '.$name);
  }

}