<?php

namespace weitzman\DrupalTestTraits\Tests;

use Drupal\user\Entity\User;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * @coversDefaultClass \weitzman\DrupalTestTraits\GoutteTrait
 */
class FormHiddenFieldTest extends ExistingSiteBase
{

    /**
     * Tests finding hidden fields.
     */
    public function testHiddenFieldExists()
    {
        $admin = User::load(1);
        $admin->passRaw = 'password';
        $this->drupalLogin($admin);
        $this->visit('/node/add/article');
        $this->assertSession()->hiddenFieldExists('form_id');
    }
}
