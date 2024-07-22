<?php

/**
* @file
* A form to collect an user review about website using controllers.
*/

namespace Drupal\websitereview\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\websitereview\Form\WebsitereviewForm;
use Drupal\websitereview\Services;
use Drupal\websitereview\Services\CustomServices;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;


class ReviewFormUsingController extends ControllerBase {
  
  /** 
   * @var \Drupal\websitereview\Services\CustomServices
  */

  // dependency injection
  protected $customService;
 
  public function __construct(CustomServices $custom_service) {
    $this->customService = $custom_service;
  }
 
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('websitereview.custom_services')
    );
  }

  // render form
  public function content() {
    $reviewformusingcontroller = \Drupal::formBuilder()->getForm('Drupal\websitereview\Form\WebsitereviewForm');
    return $reviewformusingcontroller;
  }

  public function getNodes(){
    
    $arr = [];
    // $nodes = \Drupal::service('websitereview.custom_services');
    $allNodes = $this->customService->getAllNodes();
 
    foreach ($allNodes as $node) {
      $email_address = $node->get('field_email_address')->value;
      $first_name = $node->get('field_first_name')->value;
      $last_name = $node->get('field_last_name')->value;
      $rating = $node->get('field_rating')->value;
      $review = $node->get('body')->value;
      $arr[] = [
          'email_address' => $email_address,
          'first_name' => $first_name,
          'last_name' => $last_name,
          'rating' => $rating,
          'review' => $review,
      ];
    }
    return new JsonResponse($arr);
  }

  public function postNodes(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    if(empty($data)) {
      return new JsonResponse(['status' => 'error', 'message' => 'No data found.'], 400);
    }

    $requiredFields = ['email_address', 'first_name', 'last_name', 'rating', 'review'];
    foreach($requiredFields as $fields) {
      if(empty($data[$fields])) {
        return new JsonResponse(['status' => '400', 'message' => 'Field ' . $fields . ' is not present here']);
      }
    }

    $errors = [];

    if (!\Drupal::service('email.validator')->isValid($data['email_address'])) {
      $errors[] = 'Invalid Email ' . $data['email_address'] . ', try again';
    }

    // if ( ((!\Drupal::service('websitereview.custom_services')->CheckAlpha($data['first_name'])) ||
    //   (!\Drupal::service('websitereview.custom_services')->CheckLen(strlen($data['first_name'])))) ) {
    //   $errors[] = 'Invalid first name ' . $data['first_name'] . ', try again';
    // }

    if ( ((!$this->customService->CheckAlpha($data['first_name'])) ||
      (!$this->customService->CheckLen(strlen($data['first_name'])))) ) {
      $errors[] = 'Invalid first name ' . $data['first_name'] . ', try again';
    }

    // if ( ((!\Drupal::service('websitereview.custom_services')->CheckAlpha($data['last_name'])) ||
    //   (!\Drupal::service('websitereview.custom_services')->CheckLen(strlen($data['last_name'])))) ) {
    //   $errors[] = 'Invalid last name ' . $data['last_name'] . ', try again';
    // }

    if ( ((!$this->customService->CheckAlpha($data['last_name'])) ||
      (!$this->customService->CheckLen(strlen($data['last_name'])))) ) {
      $errors[] = 'Invalid last name ' . $data['last_name'] . ', try again';
    }

    // if (!\Drupal::service('websitereview.custom_services')->CheckNum($data['rating'])) {
    //   $errors[] = 'Invalid rating ' . $data['rating'] . ', try again';
    // }

    if (!$this->customService->CheckNum($data['rating'])) {
      $errors[] = 'Invalid rating ' . $data['rating'] . ', try again';
    }

    if (!empty($errors)) {
      return new JsonResponse(['errors' => $errors], 400);
    }

    $node = Node::create([
      'type' => 'website_review',
      'title' => $data['review'],
      'field_first_name' => $data['first_name'],
      'field_last_name' => $data['last_name'],
      'field_email_address' => $data['email_address'],
      'field_rating' => $data['rating'],
      'body' => $data['review'],
      'status' => 1,
    ]);
    $node->save();
 
    return new JsonResponse([
      'status' => '200',
      'message' => 'Your data has been saved successfully.',
      'node_id' => $node->id(),
    ]);  
  }
}