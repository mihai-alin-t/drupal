<?php

namespace Drupal\ausy_events_registration;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Events Registration Service.
 */
class EventsRegistrationService {

  /**
   * Return the number of registrations for the event.
   */
  public static function getTotalRegisteredEvents(): int {

    // Check if there is an already existing event Registration based on email.
    try {
      $total_registration = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadByProperties(['type' => 'registration']);

      return count($total_registration);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
    }
  }

  /**
   * Check if a department exists.
   */
  public static function checkExistingDepartment($department_machine_name): bool {
    $departments = \Drupal::config('ausy_events_registration.departments');

    $existing_department = $departments->get($department_machine_name);

    if ($existing_department) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
