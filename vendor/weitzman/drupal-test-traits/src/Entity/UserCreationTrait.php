<?php

namespace weitzman\DrupalTestTraits\Entity;

use Drupal\Tests\user\Traits\UserCreationTrait as CoreUserCreationTrait;
use Drupal\user\Entity\Role;

/**
 * Wraps core user test traits to track entities for deletion.
 */
trait UserCreationTrait
{
    use CoreUserCreationTrait {
        createUser as coreCreateUser;
        createRole as coreCreateRole;
    }

    /**
    /**
     * Create a user with a given set of permissions.
     *
     * @param array $permissions
     *   Array of permission names to assign to user. Note that the user always
     *   has the default permissions derived from the "authenticated users" role.
     * @param string $name
     *   The user name.
     * @param bool $admin
     *   (optional) Whether the user should be an administrator
     *   with all the available permissions.
     * @param array $values
     *   (optional) An array of initial user field values.
     *
     * @return \Drupal\user\Entity\User|false
     *   A fully loaded user object with pass_raw property, or FALSE if account
     *   creation fails.
     *
     * @throws \Drupal\Core\Entity\EntityStorageException
     *   If the user creation fails.
     */
    protected function createUser(array $permissions = [], $name = null, $admin = false, $values = [])
    {
        $user = $this->coreCreateUser($permissions, $name, $admin, $values);
        $this->markEntityForCleanup($user);
        return $user;
    }

    /**
     * Creates a role and tracks it for automatic cleanup.
     *
     * @param array $permissions
     * @param null  $rid
     * @param null  $name
     * @param null  $weight
     *
     * @return string
     */
    protected function createRole(array $permissions, $rid = null, $name = null, $weight = null)
    {
        $role_id = $this->coreCreateRole($permissions, $rid, $name, $weight);
        $role = Role::load($role_id);
        $this->markEntityForCleanup($role);
        return $role_id;
    }
}
