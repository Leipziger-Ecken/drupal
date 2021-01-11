<?php

namespace Drupal\rules\ContextProvider;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sets the current node as a context on node routes.
 */
class CurrentDateContext implements ContextProviderInterface {
  use StringTranslationTrait;

  /**
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $datetime;

  /**
   * Constructs a new CurrentDateContext.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $datetime
   *   The datetime.time service.
   */
  public function __construct(TimeInterface $datetime) {
    $this->datetime = $datetime;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $datetime = $this->datetime->getCurrentTime();

    $context_definition = new ContextDefinition('timestamp', $this->t('Current date'));
    $context = new Context($context_definition, $datetime);

    $result = [
      'current_date' => $context,
    ];

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return $this->getRuntimeContexts([]);
  }

}
