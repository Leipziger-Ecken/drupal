<?php

// Use your module's testing namespace such as the one below.
namespace Drupal\Tests\moduleName\ExampleWebDriverTest;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Vocabulary;
use weitzman\DrupalTestTraits\ExistingSiteWebDriverTestBase;

/**
 * A WebDriver test suitable for testing Ajax and client-side interactions.
 */
class ExampleWebDriverTest extends ExistingSiteWebDriverTestBase
{

    /**
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     * @throws \Behat\Mink\Exception\ExpectationException
     * @throws \Behat\Mink\Exception\ResponseTextException
     */
    public function testContentCreation()
    {
        // Create a taxonomy term. Will be automatically cleaned up at the end of the test.
        $vocab = Vocabulary::load('tags');
        $this->createTerm($vocab, ['name' => 'Term 1']);
        $this->createTerm($vocab, ['name' => 'Term 2']);
        $session = $this->getSession();
        $this->visit('/user/login');
        $web_assert = $this->assertSession();
        $web_assert->statusCodeEquals(200);

        // These lines are left here as examples of how to debug requests.
        // file_put_contents('public://screenshot.png', $session->getScreenshot());
        // file_put_contents('public://' . drupal_basename($session->getCurrentUrl()) . '.html', $this->getCurrentPageContent());

        $page = $this->getCurrentPage();
        $page->fillField('name', 'admin');
        $page->fillField('pass', 'password');
        $submit_button = $page->findButton('Log in');
        $submit_button->press();
        $web_assert->statusCodeEquals(200);
        // Test autocomplete on article creation.
        $this->visit('/node/add/article');
        $web_assert->statusCodeEquals(200);
        $page = $this->getCurrentPage();
        $page->fillField('title[0][value]', 'Article Title');
        $tags = $page->findField('field_tags[target_id]');
        $tags->setValue('Ter');
        $tags->keyDown('m');
        $result = $web_assert->waitForElementVisible('css', '.ui-autocomplete li');
        $this->assertNotNull($result);
        // Click the autocomplete option
        $result->click();
        // Verify that correct the input is selected.
        $web_assert->pageTextContains('Term 1');
        $submit_button = $page->findButton('Save');
        $submit_button->press();
        // Verify the URL and get the nid.
        $this->assertTrue((bool) preg_match('/.+node\/(?P<nid>\d+)/', $session->getCurrentUrl(), $matches));
        $node = Node::load($matches['nid']);
        $this->markEntityForCleanup($node);
        // Verify the text on the page.
        $web_assert->pageTextContains('Article Title');
        $web_assert->pageTextContains('Term 1');
    }
}
