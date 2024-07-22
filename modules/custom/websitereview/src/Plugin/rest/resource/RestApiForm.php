<?php

namespace Drupal\websitereview\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Drupal\Core\Controller\ControllerBase;
use Drupal\websitereview\Form\WebsitereviewForm;
use Drupal\websitereview\Services;
use Drupal\websitereview\Services\CustomServices;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\node\Entity\Node;

/**
 * Provides a resource to get view modes by entity and bundle.
 * @RestResource(
 *   id = "custom_get_rest_resource",
 *   label = @Translation("Custom Get Rest Resource"),
 *   uri_paths = {
 *     "canonical" = "/customapi",
 *     "create" = "/customapi"
 *   }
 * )
 */

class RestApiForm extends ResourceBase {
  /**
   * A current user instance which is logged in the session.
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $customService;
  protected $loggedUser;
  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $config
   *   A configuration array which contains the information about the plugin instance.
   * @param string $module_id
   *   The module_id for the plugin instance.
   * @param mixed $module_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A currently logged user instance.
   */
  
  public function __construct(
    array $config,
    $module_id,
    $module_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    CustomServices $custom_service) {
    parent::__construct($config, $module_id, $module_definition, $serializer_formats, $logger);

    $this->loggedUser = $current_user;
    $this->customService = $custom_service;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $config, $module_id, $module_definition) {
    return new static(
      $config,
      $module_id,
      $module_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('sample_rest_resource'),
      $container->get('current_user'),
      $container->get('websitereview.custom_services')
    );
  }

  public function get(){
    
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
    return new ResourceResponse($arr);
  }

  public function post(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    if(empty($data)) {
      return new ResourceResponse(['status' => 'error', 'message' => 'No data found.'], 400);
    }

    $requiredFields = ['email_address', 'first_name', 'last_name', 'rating', 'review'];
    foreach($requiredFields as $fields) {
      if(empty($data[$fields])) {
        return new ResourceResponse(['status' => '400', 'message' => 'Field ' . $fields . ' is not present here']);
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
      return new ResourceResponse(['errors' => $errors], 400);
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
 
    return new ResourceResponse([
      'status' => '200',
      'message' => 'Your data has been saved successfully.',
      'node_id' => $node->id(),
    ]);  
  }
}

