<?php

namespace Drupal\ausy_events_registration\Form;

use Drupal\ausy_events_registration\EventsRegistrationService;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Component\Serialization\Yaml;

/**
 * Create custom form for Event Registering.
 */
class EventRegistrationForm extends FormBase {

  /**
   * Return form ID.
   *
   * @return string
   *   Return form ID.
   */
  public function getFormId(): string {
    return 'event_registration_form';
  }

  /**
   * Build event registration form.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState.
   *
   * @return array
   *   Built form/
   */
  public function buildForm(array $form, FormStateInterface $form_state, $department = NULL): array {
    // Check the departments list.
    $department_exits = EventsRegistrationService::checkExistingDepartment($department);

    if (!$department_exits) {
      \Drupal::messenger()->addError('Department not available in AUSSY.');
    }

    $form['employee_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Employee Name'),
      '#required' => TRUE,
    ];

    $form['plus_one'] = [
      '#type' => 'select',
      '#title' => $this->t('Plus one'),
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
      '#required' => TRUE,
    ];

    $form['number_of_kids'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of kids'),
      '#required' => TRUE,
    ];

    $form['number_of_vegetarians'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of vegetarians'),
      '#required' => TRUE,
    ];

    $form['employee_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    if (isset($departments['aussy_departments'][$department])) {
      $form['department'] = [
        '#type' => 'hidden',
        '#name' => 'department',
        '#value' => $departments['aussy_departments'][$department],
      ];
    }

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
      '#button_type' => 'primary',
    ];

    // Disable submit button when accessing inexisting Department.
    if (!$department_exits) {
      $form['actions']['submit']['#attributes']['disabled'] = 'disable';
    }

    return $form;
  }

  /**
   * Validate Form.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Check Employee Name.
    if (empty($form_state->getValue('employee_name'))) {
      $form_state->setErrorByName('employee_name', $this->t('Please provide an "Employee Name"'));
    }

    // Check Plus One.
    $plus_one = $form_state->getValue('plus_one');
    if (!isset($plus_one)) {
      $form_state->setErrorByName('plus_one', $this->t('Please select a "Plus one" option.'));
    }

    // Check Number of kids.
    $number_of_kids = $form_state->getValue('number_of_kids');
    if (!isset($number_of_kids)) {
      $form_state->setErrorByName('number_of_kids', $this->t('Please enter the number of kids attending.'));
    }

    // Check number of vegetarians.
    $number_of_vegetarians = $form_state->getValue('number_of_vegetarians');
    if (!isset($number_of_vegetarians) && $number_of_vegetarians !== 0) {
      $form_state->setErrorByName('number_of_vegetarians', $this->t('Please enter the number of vegetarians attending.'));
    }

    // Check amount of vegetarians
    // (hardcoded integer 1 is used as current employee).
    $total_family_members = $number_of_kids + $plus_one + 1;
    if ($number_of_vegetarians > $total_family_members) {
      $form_state->setErrorByName('number_of_vegetarians',
        $this->t('Number of vegetarians cannot be greater than the total family members.')
      );
    }

    // Check Employee Email.
    $employee_email = $form_state->getValue('employee_email');
    if (empty($employee_email)) {
      $form_state->setErrorByName('employee_email', $this->t('Please provide an Email Address.'));
    }

    // Check if Employee Email is valid.
    if (!\Drupal::service('email.validator')->isValid($employee_email)) {
      $form_state->setErrorByName(
        'employee_email',
        $this->t('the provided %email address is not correct.',
        ['%email' => $form_state->getValue('employee_email')])
      );
    }

    // Check if there is an already existing event Registration based on email.
    try {
      $existing_registration = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadByProperties(['field_email' => $employee_email]);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      \Drupal::messenger()->addError($this->t('There has been an error in checking existing registrations for your email address.'));
    }

    if (!empty($existing_registration)) {
      $form_state->setErrorByName('employee_email', $this->t('An event has been already registered with this email address.'));
    }

  }

  /**
   * Submit Form.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Create a new  'Registration' Node
    // based on the 'event_registration_form' data.
    $event_registration = Node::create(['type' => 'registration']);

    try {
      $event_registration
        ->setTitle($form_state->getValue('employee_name'))
        ->set('field_plus_one', $form_state->getValue('plus_one'))
        ->set('field_number_of_kids', $form_state->getValue('number_of_kids'))
        ->set('field_number_of_vegetarians', $form_state->getValue('number_of_vegetarians'))
        ->set('field_email', $form_state->getValue('employee_email'))
        ->set('status', 1)
        ->set('field_department', $form_state->getValue('department'))
        ->save();

      Cache::invalidateTags(['registration_count_block']);

      \Drupal::messenger()->addMessage($this->t('You have registered to "AUSSY Family Event" successfully!'));
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($this->t('An error has occurred when Registering the event'));
    }

  }

}
