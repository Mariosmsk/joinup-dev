<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;

/**
 * Update the values of current collections new member role.
 */
function joinup_core_post_update_0106400(&$sandbox) {
  $ids = \Drupal::entityTypeManager()->getStorage('rdf_entity')
    ->getQuery()
    ->condition('rid', 'collection')
    ->execute();

  $graphs = [
    '<http://joinup.eu/collection/published>',
    '<http://joinup.eu/collection/draft>',
  ];

  $connection = \Drupal::getContainer()->get('sparql.endpoint');
  foreach ($graphs as $graph) {
    $enabled = <<<QUERY
WITH {$graph}
DELETE { ?entity_id <http://joinup.eu/collection/moderation> "true"^^<http://www.w3.org/2001/XMLSchema#boolean> }
INSERT { ?entity_id <http://joinup.eu/collection/new_role> "rdf_entity-collection-member" }
WHERE { ?entity_id <http://joinup.eu/collection/moderation> "true"^^<http://www.w3.org/2001/XMLSchema#boolean> }
QUERY;

    $disabled = <<<QUERY
WITH {$graph}
DELETE { ?entity_id <http://joinup.eu/collection/moderation> "false"^^<http://www.w3.org/2001/XMLSchema#boolean> }
INSERT { ?entity_id <http://joinup.eu/collection/new_role> "rdf_entity-collection-author" }
WHERE { ?entity_id <http://joinup.eu/collection/moderation> "false"^^<http://www.w3.org/2001/XMLSchema#boolean> }
QUERY;

    $connection->query($enabled);
    $connection->query($disabled);
  }

  \Drupal::entityTypeManager()->getStorage('rdf_entity')->resetCache($ids);
}

/**
 * Update memberships related to the new role.
 */
function joinup_core_post_update_0106403(&$sandbox) {
  if (empty($sandbox['membership_ids'])) {
    $collection_ids = \Drupal::entityTypeManager()->getStorage('rdf_entity')->getQuery()
      ->condition('rid', 'collection')
      ->condition('field_ar_new_member_role', 'rdf_entity-collection-author')
      ->execute();

    $sandbox['membership_ids'] = \Drupal::entityTypeManager()->getStorage('og_membership')->getQuery()
      ->condition('entity_type', 'rdf_entity')
      ->condition('entity_id', $collection_ids, 'IN')
      ->execute();
    $sandbox['progress'] = 0;
    $sandbox['total'] = count($sandbox['membership_ids']);
  }

  $role = OgRole::load('rdf_entity-collection-author');
  $membership_ids = array_splice($sandbox['membership_ids'], 0, 50);

  /** @var \Drupal\og\OgMembershipInterface $membership */
  foreach (OgMembership::loadMultiple($membership_ids) as $membership) {
    $membership->addRole($role);
    $membership->skip_notification = TRUE;
    $membership->save();
    $sandbox['progress']++;
  }

  $sandbox['#finished'] = (float) $sandbox['progress'] / (float) $sandbox['total'];
  return "Processed {$sandbox['progress']} out of {$sandbox['total']} memberships.";
}
