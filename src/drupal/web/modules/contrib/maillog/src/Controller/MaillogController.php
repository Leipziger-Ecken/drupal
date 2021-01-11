<?php

namespace Drupal\maillog\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Primary controler behind the Maillog module.
 */
class MaillogController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a \Drupal\maillog\Controller\MaillogController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Get the Maillog entry.
   *
   * @param int $maillog_id
   *   The Maillog ID.
   *
   * @return array
   *   The output fields
   */
  public function details($maillog_id) {
    $maillog_entry = $this->getMaillogEntry(intval($maillog_id));

    if (!$maillog_entry) {
      throw new NotFoundHttpException();
    }

    $output = [];

    $output['#title'] = $maillog_entry['subject'];

    $output['header_from'] = [
      '#title' => t('From'),
      '#type' => 'item',
      '#markup' => Html::escape($maillog_entry['header_from']),
    ];
    $output['header_to'] = [
      '#title' => t('To'),
      '#type' => 'item',
      '#markup' => Html::escape($maillog_entry['header_to']),
    ];
    $output['header_reply_to'] = [
      '#title' => t('Reply to'),
      '#type' => 'item',
      '#markup' => Html::escape($maillog_entry['header_reply_to']),
    ];
    $output['header_all'] = [
      '#title' => t('All'),
      '#type' => 'item',
      '#markup' => '<pre>',
    ];

    foreach ($maillog_entry['header_all'] as $header_all_name => $header_all_value) {
      $output['header_all']['#markup'] .= Html::escape($header_all_name) . ': ' . Html::escape($header_all_value) . '<br/>';
    }

    $output['header_all']['#markup'] .= '</pre>';

    $output['body'] = [
      '#title' => t('Body'),
      '#type' => 'item',
      '#markup' => '<pre>' . Html::escape($maillog_entry['body']) . '</pre>',
    ];

    return $output;
  }

  /**
   * Delete a specific maillog entry.
   *
   * @param int $maillog_id
   *   The maillog ID.
   */
  public function delete($maillog_id) {
    $id = intval($maillog_id);
    $this->database->query("DELETE FROM {maillog} WHERE id = :id", [':id' => $id]);
    $this->messenger()->addStatus($this->t('Mail with ID @id has been deleted!', ['@id' => $id]));

    return $this->redirect('view.maillog_overview.page_1');
  }

  /**
   * Loads the Maillog entry.
   *
   * @param int $id
   *   The maillog ID.
   *
   * @return array
   *   A Maillog record.
   */
  protected function getMaillogEntry($id) {
    $result = $this->database->query("SELECT id, header_from, header_to, header_reply_to, header_all, subject, body FROM {maillog} WHERE id=:id", [
      ':id' => $id,
    ]);

    if ($maillog = $result->fetchAssoc()) {
      // Unserialize values.
      $maillog['header_all'] = unserialize($maillog['header_all']);
    }

    return $maillog;
  }

}
