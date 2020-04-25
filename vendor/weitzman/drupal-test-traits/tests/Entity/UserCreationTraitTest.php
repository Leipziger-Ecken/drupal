<?php

namespace weitzman\DrupalTestTraits\Tests\Entity;

use Drupal\user\Entity\Role;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the user creation trait.
 */
class UserCreationTraitTest extends ExistingSiteBase
{
    public function testUserCreation()
    {
        $user = $this->createUser();
        $this->assertCount(1, $this->cleanupEntities);
        $this->assertEquals($user->id(), $this->cleanupEntities[0]->id());
    }

    public function testRoleCreation()
    {
        $role_id = $this->createRole(['access content']);
        $role = Role::load($role_id);
        $this->assertCount(1, $this->cleanupEntities);
        $this->assertEquals($role->id(), $this->cleanupEntities[0]->id());
    }
}
