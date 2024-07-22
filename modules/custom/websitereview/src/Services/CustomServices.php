<?php

  namespace Drupal\websitereview\Services;

  use Drupal\Core\Database\Connection;
  use Drupal\node\Entity\Node;

  class CustomServices{

    public function CheckAlpha($name){

      for ($i = 0; $i < strlen($name); $i++) {
        if (!((($name[$i] >= 'a') && ($name[$i] <= 'z')) || 
            (($name[$i] >= 'A') && ($name[$i] <= 'Z')))) {
          return false;
        }
      }
      return true;
    }

    public function CheckLen($len) {
      if ($len < 3) {
        return false;
      }
      return true;
    }

    public function CheckNum($num){
      if ( !(($num >=1) && ($num <= 10)) ){
        return false;
      }
      return true;
    }

    public function getAllNodes() {
      $nodes = \Drupal::entityTypeManager()->getStorage('node')
        ->loadByProperties(['type' => 'website_review', 'status' => 1]);
      return $nodes;
    }
}