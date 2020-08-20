<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

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
