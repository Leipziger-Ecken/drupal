<?php

namespace weitzman\DrupalTestTraits\Mail;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\mailsystem\MailsystemManager;

/**
 * Trait for enabling and disabling mail collection during tests.
 *
 * Usage:
 *   From ::setUp call $this->startMailCollection()
 *   In ::tearDown call $this->restoreMailSettings()
 *
 * @property \Symfony\Component\DependencyInjection\ContainerInterface $container
 */
trait MailCollectionTrait
{

    use AssertMailTrait;

    /**
     * Original configuration values for restoration after the test run.
     *
     * @var array
     *   An array of configuration data, keyed by name.
     */
    protected $originalConfiguration = [];

    /**
     * Capture emails sent during tests.
     */
    protected function startMailCollection()
    {
        $config = $this->container->get('config.factory')->getEditable(
            'system.mail'
        );
        $data = $config->getRawData();

        // Store original values.
        if (!isset($this->originalConfiguration['system.mail'])) {
            $this->originalConfiguration['system.mail'] = $data;
        }

        $data['interface'] = ['default' => 'test_mail_collector'];
        $config->setData($data)->save();

        // Also change mailsystem.
        $this->startMailSystemCollection();
    }

    /**
     * Stop mail collection/restore settings.
     */
    protected function restoreMailSettings()
    {
        // Restore original configurations.
        foreach ($this->originalConfiguration as $name => $data) {
            $this->container->get('config.factory')->getEditable($name)->setData($data)->save();
        }
        $this->originalConfiguration = [];

        // Empty out email collection.
        $this->container->get('state')->set('system.test_mail_collector', []);
    }

    /**
     * Capture mailsystem emails.
     */
    protected function startMailSystemCollection()
    {
        if ($this->container->get('module_handler')->moduleExists(
            'mailsystem'
        )
        ) {
            $config = $this->container->get('config.factory')->getEditable(
                'mailsystem.settings'
            );
            $data = $config->getRawData();

            if (!isset($this->originalConfiguration['mailsystem.settings'])) {
                $this->originalConfiguration['mailsystem.settings'] = $data;
            }

            // Convert all 'senders' to the test collector.
            $data = $this->findMailSystemSenders($data);
            $config->setData($data)->save();
        }
    }

    /**
     * Find and replace all the mail system sender plugins with the test plugin.
     *
     * This method calls itself recursively.
     */
    protected function findMailSystemSenders(array $data)
    {
        foreach ($data as $key => $values) {
            if (is_array($values)) {
                if (isset($values[MailsystemManager::MAILSYSTEM_TYPE_SENDING])) {
                    $data[$key][MailsystemManager::MAILSYSTEM_TYPE_SENDING]
                        = 'test_mail_collector';
                } else {
                    $data[$key] = $this->findMailSystemSenders($values);
                }
            }
        }

        return $data;
    }
}
