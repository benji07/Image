<?php

namespace Image;

use Image\Image;

/**
 * Must implements execute method
 */
abstract class Plugin{

  protected $name = null;

  protected $image = null;

  public function getName(){
    return $this->name;
  }

  /**
   *
   * @param Image $image
   */
  public function setImage(Image $image){
    $this->image = $image;
  }

  /**
   *
   * @return Image
   */
  public function getImage(){
    return $this->image;
  }

}