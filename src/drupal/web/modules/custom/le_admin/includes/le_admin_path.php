<?php
use Drupal\path_alias\Entity\PathAlias;

/**
 * Provide smooth URI's for system-paths
 */
function le_admin_path_alias()
{
  PathAlias::create([
    'path' => '/node/add/webbuilder',
    'alias' => '/webbuilders/neu',
  ])->save();

  PathAlias::create([
    'path' => '/node/add/project',
    'alias' => '/projects/neu',
  ])->save();

  PathAlias::create([
    'path' => '/node/add/sponsor',
    'alias' => '/sponsors/neu',
  ])->save();

  PathAlias::create([
    'path' => '/node/add/partner',
    'alias' => '/partners/neu',
  ])->save();

  PathAlias::create([
    'path' => '/node/add/blog_article',
    'alias' => '/blog-articles/neu',
  ])->save();
}