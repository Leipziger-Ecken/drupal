<?php

namespace Drupal\schema_person\Plugin\metatag\Tag;

use Drupal\schema_metatag\Plugin\metatag\Tag\SchemaNameBase;

/**
 * Provides a plugin for the 'schema_person_alternate_name' meta tag.
 *
 * - 'id' should be a globally unique id.
 * - 'name' should match the Schema.org element name.
 * - 'group' should match the id of the group that defines the Schema.org type.
 *
 * @MetatagTag(
 *   id = "schema_person_alternate_name",
 *   label = @Translation("alternateName"),
 *   description = @Translation("An alias for the person."),
 *   name = "alternateName",
 *   group = "schema_person",
 *   weight = -5,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class SchemaPersonAlternateName extends SchemaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
