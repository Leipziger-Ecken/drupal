<?php

namespace weitzman\DrupalTestTraits\Entity;

use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\taxonomy\Functional\TaxonomyTestTrait as CoreTaxonomyTestTrait;

/**
 * Wraps core taxonomy test traits to track entities for deletion.
 */
trait TaxonomyCreationTrait
{
    use CoreTaxonomyTestTrait {
        createVocabulary as coreCreateVocabulary;
        createTerm as coreCreateTerm;
    }

    /**
     * Creates a term and tracks it for automatic cleanup.
     *
     * @param \Drupal\taxonomy\VocabularyInterface $vocabulary
     * @param array                                $settings
     *
     * @return \Drupal\taxonomy\Entity\Term
     */
    protected function createTerm(VocabularyInterface $vocabulary, array $settings = [])
    {
        $term = $this->coreCreateTerm($vocabulary, $settings);
        $this->markEntityForCleanup($term);
        return $term;
    }

    /**
     * Creates a vocabulary and tracks it for automatic cleanup.
     *
     * @return \Drupal\Core\Entity\EntityInterface|static
     */
    protected function createVocabulary()
    {
        $vocabulary = $this->coreCreateVocabulary();
        $this->markEntityForCleanup($vocabulary);
        return $vocabulary;
    }
}
