<?php

namespace Drupal\ausy_events_registration\Form;

use Drupal\ausy_events_registration\EventsRegistrationService;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Add Depertment Form.
 */
class AddDepartmentForm extends ConfigFormBase {

  /**
   * Editable Config name.
   *
   * @inheritDoc
   */
  protected function getEditableConfigNames(): array {
    return ['ausy_events_registration.departments'];
  }

  /**
   * Add Department form ID.
   *
   * @inheritDoc
   */
  public function getFormId(): string {
    return 'add_department_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['department_machine_name'] = [
      '#type' => 'textfield',
      '#name' => 'department_machine_name',
      '#title' => $this->t('Department Machine Readable Name'),
      '#required' => TRUE,
    ];

    $form['department_human_name'] = [
      '#type' => 'textfield',
      '#name' => 'department_human_name',
      '#title' => $this->t('Department Human Readable Name'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add New Department'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Check the departments list.
    $department_exits = EventsRegistrationService::checkExistingDepartment($form_state->getValue('department_machine_name'));

    if ($department_exits) {
      $form_state->setErrorByName('department_machine_name', $this->t('Department already exists.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('ausy_events_registration.departments')
      ->set($form_state->getValue('department_machine_name'), $form_state->getValue('department_human_name'))
      ->save();
  }

}
