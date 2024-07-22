<?php

/**
* @file
* A form to collect an user review about website.
*/

namespace Drupal\websitereview\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\websitereview\Services;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\websitereview\Services\CustomServices;

class WebsitereviewForm extends FormBase {

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

  /**
  * {@inheritdoc}
  */
  public function getFormId() {
    return 'website_review_form';
  }

  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Establish the $form render array. It has an email text field,
    // a submit button, and a hidden field containing the node ID.
    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => t('First Name'),
      '#size' => 255,
      '#description' => t("first name of users"),
      '#required' => TRUE,
    ];
    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => t('last_name'),
      '#size' => 255,
      '#description' => t("last name of users"),
      '#required' => TRUE,
    ];
    $form['email_address'] = [
      '#type' => 'textfield',
      '#title' => t('Email Address'),
      '#size' => 255,
      '#description' => t("Email given by users"),
      '#required' => TRUE,
    ];
    $form['review'] = [
      '#type' => 'textfield',
      '#title' => t('Review'),
      '#size' => 255,
      '#description' => t("Review given by users"),
      '#required' => TRUE,
    ];
    $form['rating'] = [
        '#type' => 'number',
        '#title' => t('website Rating'),
        '#description' => t("Rating out of 10"),
        '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $first_name = $form_state->getvalue('first_name');
    $len = strlen($first_name);
    // $checkalpha = \Drupal::service('websitereview.custom_services');
    // $checklen = \Drupal::service('websitereview.custom_services');
    $checkalpha = $this->customService;
    $checklen = $this->customService;
    if ( (!$checkalpha->CheckAlpha($first_name)) || (!$checklen->CheckLen($len)) ) {
      $form_state->setErrorByName('first_name',
        $this->t('It appears that %first_name is not a valid. Please try again',
         ['%first_name' => $first_name]));
      }

    $last_name = $form_state->getvalue('last_name');
    $len = strlen($first_name);
    // $checkalpha = \Drupal::service('websitereview.custom_services');
    // $checklen = \Drupal::service('websitereview.custom_services');
    $checkalpha = $this->customService;
    $checklen = $this->customService;
    if ( (!$checkalpha->CheckAlpha($last_name)) || (!$checklen->CheckLen($len)) ) {
      $form_state->setErrorByName('last_name',
        $this->t('It appears that %last_name is not a valid. Please try again',
          ['%last_name' => $last_name]));
      }

    $rating = $form_state->getvalue('rating');
    // $checknum = \Drupal::service('websitereview.custom_services');
    $checknum = $this->customService;
    if (!$checknum->CheckNum($rating)) {
      $form_state->setErrorByName('rating',
        $this->t('It appears that %rating is not a valid. Please enter between 1-10',
          ['%rating' => $rating]));
      }

    $email_address = $form_state->getvalue('email_address');
    if( !(\Drupal::service('email.validator')->isValid($email_address))) {
      $form_state->setErrorByName('email_address',
        $this->t('it appears that %email_address is not a valid email. 
          Please try again!', ['%email_address' => $email_address]));
    }
  }

  /**
  * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submitted_first_name = $form_state->getValue('first_name');
    $submitted_last_name = $form_state->getValue('last_name');
    $submitted_email_address = $form_state->getValue('email_address');
    $submitted_review = $form_state->getValue('review');
    $submitted_rating = $form_state->getValue('rating');
    $this->messenger () ->addMessage(t("The form is working! 
      @fn @ln entered @ea, @re and @rt.",
      ['@fn' => $submitted_first_name, '@ln' => $submitted_last_name,
       '@ea' => $submitted_email_address, '@re' => $submitted_review, '@rt' => $submitted_rating]));
   
    $node = Node::create([
      'type' => 'website_review',
      'title' => $form_state->getValue('review'),
      'field_first_name' => $form_state->getValue('first_name'),
      'field_last_name' => $form_state->getValue('last_name'),
      'field_email_address' => $form_state->getValue('email_address'),
      'body' => $form_state->getValue('review'),
      'field_rating' => $form_state->getValue('rating'),
    ]);
    $node->save();
    \Drupal::messenger()->addMessage($this->t('Review Given.'));
    $node_id = $node->id();
    $form_state->setRedirect('entity.node.canonical', ['node' => $node_id]);
  }
    
}
