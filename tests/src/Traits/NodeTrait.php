<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use Drupal\node\NodeInterface;

/**
 * Helper methods when dealing with Nodes.
 */
trait NodeTrait {

  /**
   * Returns the node with the given title and bundle.
   *
   * If multiple nodes have the same title the first one will be returned.
   *
   * @param string $title
   *   The node's title.
   * @param string $bundle
   *   Optional content entity bundle.
   *
   * @return \Drupal\node\NodeInterface
   *   The node.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a node with the given name does not exist.
   */
  public function getNodeByTitle(string $title, string $bundle = NULL): ?NodeInterface {
    $query = \Drupal::entityQuery('node')
      ->condition('title', $title)
      ->range(0, 1);
    if (!empty($bundle)) {
      $query->condition('type', $bundle);
    }
    $result = $query->execute();

    if (empty($result)) {
      if ($bundle) {
        $message = "The '$bundle' with the name '$title' does not exist.";
      }
      else {
        $message = "The node with the name '$title' does not exist.";
      }
      throw new \InvalidArgumentException($message);
    }

    // Reload from database to avoid caching issues and get latest version.
    $id = reset($result);
    return \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged($id);
  }

  /**
   * Returns the last version of the node entity.
   *
   * @param string $title
   *   The title of the node.
   * @param string $bundle
   *   The type of the node.
   * @param bool $published
   *   Whether to request the last published or last unpublished verion.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node revision.
   */
  public function getLastNodeVersion(string $title, string $bundle, bool $published = TRUE): ?NodeInterface {
    $published = (int) $published;
    $current_revision = $this->getNodeByTitle($title, $bundle);
    $revision_id = $current_revision->getRevisionId();
    // We gather all revisions and then filter out the one we want as filtering
    // by vid will lead in false results.
    // @see: https://www.drupal.org/project/drupal/issues/2766135
    $revisions = \Drupal::entityQuery('node')
      ->allRevisions()
      ->condition('type', $bundle)
      ->condition('status', $published)
      ->condition('nid', $current_revision->id())
      ->sort('vid', 'DESC')
      ->execute();

    if (isset($revisions[$revision_id])) {
      unset($revisions[$revision_id]);
    }

    if (empty($revisions)) {
      return NULL;
    }

    $revision_ids = array_keys($revisions);
    $latest_id = reset($revision_ids);
    return \Drupal::entityTypeManager()->getStorage('node')->loadRevision($latest_id);
  }

}
