<?php

namespace Image\Tests\Image;

require __DIR__.'/../../../../lib/Image/Image.php';

class ImageTest extends \PHPUnit_Framework_TestCase
{

  protected $image = null;

  public function setUp(){
    $this->image = new \Image\Image(realpath(__DIR__.'/../../../fixtures/image.png'));
    $this->image2 = new \Image\Image(realpath(__DIR__.'/../../../fixtures/image2.png'));
  }


  /**
   * @dataProvider resizeData
   */
  public function testResizeImage1($maxWidth, $maxHeight, $exceptedWidth, $exceptedHeight)
  {
    $this->image->resize($maxWidth, $maxHeight);

    $this->assertEquals($exceptedWidth, $this->image->getWidth());
    $this->assertEquals($exceptedHeight, $this->image->getHeight());
  }

  /**
   * @dataProvider resizeData
   */
  public function testResizeImage2($maxHeight, $maxWidth, $exceptedHeight, $exceptedWidth)
  {
    $this->image2->resize($maxWidth, $maxHeight);

    $this->assertEquals($exceptedWidth, $this->image2->getWidth());
    $this->assertEquals($exceptedHeight, $this->image2->getHeight());
  }

  
  public function resizeData(){

    return array(
      array(100, 45, 100, 45),
      array(200, 90, 200, 90),
      array(50, 22, 50, 22),
      array(200, null, 200, 90),
      array(null, 90, 200, 90),
      array(100, 100, 100, 45)
    );
  }
}