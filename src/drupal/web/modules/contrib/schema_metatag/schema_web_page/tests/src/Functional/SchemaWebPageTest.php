<?php

namespace Drupal\Tests\schema_web_page\Functional;

use Drupal\Tests\schema_metatag\Functional\SchemaMetatagTagsTestBase;

/**
 * Tests that each of the Schema Metatag Articles tags work correctly.
 *
 * @group schema_metatag
 * @group schema_web_page
 */
class SchemaWebPageTest extends SchemaMetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['schema_web_page'];

  /**
   * {@inheritdoc}
   */
  public $moduleName = 'schema_web_page';

  /**
   * {@inheritdoc}
   */
  public $schemaTagsNamespace = '\\Drupal\\schema_web_page\\Plugin\\metatag\\Tag\\';

  /**
   * {@inheritdoc}
   */
  public $schemaTags = [
    'schema_web_page_breadcrumb' => 'SchemaWebPageBreadcrumb',
    'schema_web_page_has_part' => 'SchemaWebPageHasPart',
    'schema_web_page_id' => 'SchemaWebPageId',
    'schema_web_page_is_accessible_for_free' => 'SchemaWebPageIsAccessibleForFree',
    'schema_web_page_type' => 'SchemaWebPageType',
    'schema_web_page_author' => 'SchemaWebPageAuthor',
    'schema_web_page_description' => 'SchemaWebPageDescription',
    'schema_web_page_publisher' => 'SchemaWebPagePublisher',
    'schema_web_page_speakable' => 'SchemaWebPageSpeakable',
    'schema_web_page_in_language' => 'SchemaWebPageInLanguage',
    'schema_web_page_translation_of_work' => 'SchemaWebPageTranslationOfWork',
    'schema_web_page_work_translation' => 'SchemaWebPageWorkTranslation',

  ];

}
