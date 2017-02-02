<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Base class for solution migrations.
 */
abstract class SolutionBase extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->select('joinup_migrate_mapping', 'm')
      ->condition('m.migrate', 1)
      ->condition('m.type', 'asset_release');

    $this->alias['collection'] = $query->join('joinup_migrate_collection', 'c', "m.collection = %alias.collection");
    $this->alias['node'] = $query->join('node', 'n', "m.nid = %alias.nid");
    $this->alias['node_revision'] = $query->join('node_revisions', 'node_revision', "{$this->alias['node']}.vid = %alias.vid");

    return $query->fields($this->alias['node'], ['nid']);
  }

}
