<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\Core\Database\Database;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

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

/**
 * Re-insert the new EIF vocabularies to apply new changes.
 */
function joinup_core_post_update_0106401(): void {
  $vids = [
    'eif_conceptual_model',
    'eif_interoperability_layer',
    'eif_principle',
    'eif_recommendation',
  ];

  $sparql_connection = Database::getConnection('default', 'sparql_default');
  $connection_options = $sparql_connection->getConnectionOptions();
  $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
  $graph_store = new GraphStore($connect_string);
  foreach ($vids as $vocabulary) {
    $filepath = __DIR__ . "/../../../../resources/fixtures/{$vocabulary}.rdf";
    $graph_uri = "http://{$vocabulary}";
    $graph = new Graph($graph_uri);
    $sparql_connection->update("CLEAR GRAPH <{$graph_uri}>");
    $graph->parse(file_get_contents($filepath));
    $graph_store->insert($graph);
  }

  $entity_type_manager = \Drupal::entityTypeManager();
  $storage = $entity_type_manager->getStorage('taxonomy_term');
  $tids = $storage->getQuery()->condition('vid', $vids, 'IN')->execute();
  /** @var \Drupal\taxonomy\TermInterface $term */
  foreach ($storage->loadMultiple($tids) as $term) {
    ContentEntity::indexEntity($term);
  }
  /** @var \Drupal\search_api\IndexInterface $index */
  $index = $entity_type_manager->getStorage('search_api_index')->load('published');
  $index->reindex();
  $index->indexItems(-1, 'entity:taxonomy_term');
}
