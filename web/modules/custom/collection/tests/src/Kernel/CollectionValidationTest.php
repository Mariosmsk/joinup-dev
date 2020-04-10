<?php

declare(strict_types = 1);

namespace Drupal\Tests\collection\Kernel;

use Drupal\Tests\joinup_core\Kernel\GroupValidationTestBase;

/**
 * Tests the validation on the collection bundle entity.
 *
 * @group entity_validation
 */
class CollectionValidationTest extends GroupValidationTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'facets',
    'field_group',
    'file',
    'inline_entity_form',
    'joinup_workflow',
    'options',
    'search_api',
    'search_api_field',
    'smart_trim',
    'state_machine',
    'workflow_state_permission',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installModule('collection');
    $this->installConfig('collection');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredFields(): array {
    return [
      'label',
      'field_ar_description',
      'field_policy_domain',
      'field_ar_owner',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function bundle(): string {
    return 'collection';
  }

}
