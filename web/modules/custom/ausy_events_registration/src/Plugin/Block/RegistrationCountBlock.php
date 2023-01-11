<?php

namespace Drupal\ausy_events_registration\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\ausy_events_registration\EventsRegistrationService;

/**
 * Provides a 'Registration Count' block.
 *
 * @Block(
 *   id = "registration_count_block",
 *   admin_label = @Translation("Registrations Count Block"),
 * )
 */
class RegistrationCountBlock extends BlockBase {

  /**
   * Build block.
   *
   * @inheritDoc
   */
  public function build(): array {
    $registered_events = EventsRegistrationService::getTotalRegisteredEvents();

    return [
      '#markup' => $this->t('Number of events registered: @total', ['@total' => $registered_events]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return Cache::mergeTags(parent::getCacheTags(), ['registration_count_block']);
  }

}
