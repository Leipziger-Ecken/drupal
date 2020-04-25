<?php

namespace weitzman\DrupalTestTraits\Tests\Entity;

use Drupal\taxonomy\Entity\Vocabulary;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests the taxonomy creation trait.
 */
class TaxonomyTestTraitTest extends ExistingSiteBase
{
    public function testTermCreate()
    {
        $vocabulary = Vocabulary::load('tags');
        $term = $this->createTerm($vocabulary);
        $this->assertCount(1, $this->cleanupEntities);
        $this->assertEquals($term->id(), $this->cleanupEntities[0]->id());
    }

    public function testVocabularyCreate()
    {
        $vocabulary = $this->createVocabulary();
        $this->assertCount(1, $this->cleanupEntities);
        $this->assertEquals($vocabulary->id(), $this->cleanupEntities[0]->id());
    }
}
