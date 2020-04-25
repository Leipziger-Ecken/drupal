<?php

namespace weitzman\DrupalTestTraits\Tests\Mail;

use Drupal\Core\Test\AssertMailTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;
use weitzman\DrupalTestTraits\Mail\MailCollectionTrait;

/**
 * @coversDefaultClass \weitzman\DrupalTestTraits\Mail\MailCollectionTrait
 */
class MailCollectorTraitTest extends ExistingSiteBase
{

    use MailCollectionTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->startMailCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $this->restoreMailSettings();
        parent::tearDown();
    }

    public function testMailCollection()
    {
        $this->visit('/contact');
        $subject = $this->randomString();
        $page = $this->getCurrentPage();
        $page->fillField('Your name', $this->randomString());
        $page->fillField('Your email address', 'foo@example.com');
        $page->fillField('Subject', $subject);
        $page->fillField('Message', $this->randomString());
        $page->pressButton('Send message');

        $mails = $this->getMails();
        $this->assertCount(1, $mails);
        $this->assertMail('module', 'contact');
        $this->assertMail('subject', '[Website feedback] ' . $subject);
    }
}
