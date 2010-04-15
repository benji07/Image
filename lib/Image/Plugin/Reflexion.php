<?php

namespace Image\Plugin;

use Image\Image;
use Image\Plugin;


class Reflexion extends Plugin
{


  protected $name = 'reflexion';

  public function execute($percent, $reflection, $white, $border, $borderColor)
  {
    
    $width = $this->getImage()->getWidth();
    $height = $this->getImage()->getHeight();
    $reflectionHeight = intval($height * ($reflection / 100));
    $newHeight = $height + $reflectionHeight;
    $reflectedPart = $height * ($percent / 100);

    $newImage = $this->getImage()->getWorkingImage();


    $workingImage = imagecreatetruecolor($width, $newHeight);

    imagealphablending($workingImage, true);

    $colorToPaint = imagecolorallocatealpha($workingImage,255,255,255,0);
    imagefilledrectangle($workingImage,0,0,$width,$newHeight,$colorToPaint);

    imagecopyresampled($workingImage,$newImage,0,0,0,$reflectedPart,$width,$reflectionHeight,$width,($height - $reflectedPart));

    $workingImage = $this->imageFlipVertical($workingImage);

    imagecopy($workingImage, $newImage, 0, 0, 0, 0, $width, $height);

    imagealphablending($workingImage, true);

    for ($i = 0; $i < $reflectionHeight; $i++)
    {
      $colorToPaint = imagecolorallocatealpha($workingImage, 255, 255, 255, ($i/$reflectionHeight*-1+1)*$white);

      imagefilledrectangle($workingImage, 0, $height + $i, $width, $height + $i, $colorToPaint);
    }

    if($border == true)
    {
      $rgb = $this->hex2rgb($borderColor, false);
      $colorToPaint = imagecolorallocate($workingImage, $rgb[0], $rgb[1], $rgb[2]);

      imageline($workingImage, 0, 0, $width, 0, $colorToPaint); //top line
      imageline($workingImage, 0, $height, $width, $height, $colorToPaint); //bottom line
      imageline($workingImage, 0, 0, 0, $height, $colorToPaint); //left line
      imageline($workingImage, $width-1, 0, $width-1, $height, $colorToPaint); //right line
    }

    if ($this->getImage()->getFormat() == 'image/png')
    {
      $colorTransparent = imagecolorallocatealpha($workingImage,255,255,255,0);

      imagefill($workingImage, 0, 0, $colorTransparent);
      imagesavealpha($workingImage, true);
    }

    $this->getImage()->setOriginalImage($workingImage);
    $this->getImage()->setWidth($width);
    $this->getImage()->setHeight($newHeight);
    
  }


  protected function imageFlipVertical ($workingImage)
  {
    $x_i = imagesx($workingImage);
    $y_i = imagesy($workingImage);

    for ($x = 0; $x < $x_i; $x++)
    {
      for ($y = 0; $y < $y_i; $y++)
      {
        imagecopy($workingImage, $workingImage, $x, $y_i - $y - 1, $x, $y, 1, 1);
      }
    }

    return $workingImage;
  }


  protected function hex2rgb($hex, $asString = false)
  {
    // strip off any leading #
    if (0 === strpos($hex, '#'))
    {
      $hex = substr($hex, 1);
    }
    elseif (0 === strpos($hex, '&H'))
    {
      $hex = substr($hex, 2);
    }

    // break into hex 3-tuple
    $cutpoint = ceil(strlen($hex) / 2)-1;
    $rgb = explode(':', wordwrap($hex, $cutpoint, ':', $cutpoint), 3);

    // convert each tuple to decimal
    $rgb[0] = (isset($rgb[0]) ? hexdec($rgb[0]) : 0);
    $rgb[1] = (isset($rgb[1]) ? hexdec($rgb[1]) : 0);
    $rgb[2] = (isset($rgb[2]) ? hexdec($rgb[2]) : 0);

    return ($asString ? "{$rgb[0]} {$rgb[1]} {$rgb[2]}" : $rgb);
  }

}