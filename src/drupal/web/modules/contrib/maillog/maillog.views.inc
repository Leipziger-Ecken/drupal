<?php

/**
 * @file
 * Views integration for maillog module.
 */

/**
 * Implements hook_views_data().
 */
function maillog_views_data() {
  $data['maillog']['table']['group'] = t('Maillog');

  $data['maillog']['table']['base'] = [
    'field' => 'id',
    'title' => t('Maillog'),
    'help' => t("This table contains the logged e-mails."),
    'weight' => -10,
  ];

  $data['maillog']['id'] = [
    'title' => t('Maillog_ID'),
    'help' => t('The primary key of the maillog table.'),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['maillog']['header_message_id'] = [
    'title' => t('Message_ID'),
    'help' => t("The 'Message_ID' e-mail address."),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['maillog']['header_from'] = [
    'title' => t('From'),
    'help' => t("The 'From' field of the e-mail address."),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['maillog']['header_to'] = [
    'title' => t('To'),
    'help' => t("The 'To' field of the e-mail address."),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['maillog']['header_reply_to'] = [
    'title' => t('Reply To'),
    'help' => t("The 'Reply-To' field of the e-mail address."),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['maillog']['header_all'] = [
    'title' => t('Header'),
    'help' => t("The 'Header' field of the e-mail."),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['maillog']['body'] = [
    'title' => t('Body'),
    'help' => t("The 'Body' field of the e-mail."),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['maillog']['subject'] = [
    'title' => t('Subject'),
    'help' => t("The 'Subject' field of the e-mail."),
    'field' => [
      'id' => 'standard',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['maillog']['sent_date'] = [
    'title' => t('Date'),
    'help' => t("The 'Date' field of the e-mail."),
    'field' => [
      'id' => 'date',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['maillog']['delete_maillog'] = [
    'field' => [
      'title' => t('Delete link'),
      'help' => t('Provide a simple link to delete an eMail entry from the maillog table.'),
      'id' => 'maillog_field_delete',
      'real field' => 'id',
    ],
  ];

  return $data;
}
