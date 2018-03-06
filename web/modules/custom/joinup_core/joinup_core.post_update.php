<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

use Drupal\rdf_entity\Entity\RdfEntityMapping;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * Enable the Sub-Pathauto module.
 */
function joinup_core_post_update_enable_subpathauto() {
  \Drupal::service('module_installer')->install(['subpathauto']);
}

/**
 * Enable the Views Bulk Operations module.
 */
function joinup_core_post_update_install_vbo() {
  \Drupal::service('module_installer')->install(['views_bulk_operations']);
}

/**
 * Enable the Email Registration module.
 */
function joinup_core_post_update_install_email_registration() {
  \Drupal::service('module_installer')->install(['email_registration']);
}

/**
 * Enable the Joinup Invite module.
 */
function joinup_core_post_update_install_joinup_invite() {
  \Drupal::service('module_installer')->install(['joinup_invite']);
}

/**
 * Move the contact form attachments under the private scheme.
 */
function joinup_core_post_update_move_contact_form_attachments() {
  /** @var \Drupal\Core\File\FileSystemInterface $file_system */
  $file_system = Drupal::service('file_system');

  $message_storage = \Drupal::entityTypeManager()->getStorage('message');
  $ids = $message_storage->getQuery()
    ->condition('template', 'contact_form_submission')
    ->exists('field_contact_attachment')
    ->execute();

  foreach ($message_storage->loadMultiple($ids) as $message) {
    /** @var \Drupal\file\FileInterface $attachment */
    if ($attachment = $message->field_contact_attachment->entity) {
      if (!file_exists($attachment->getFileUri())) {
        continue;
      }
      $target = file_uri_target($attachment->getFileUri());
      $uri = "private://$target";
      $destination_dir = $file_system->dirname($uri);
      if (!file_prepare_directory($destination_dir, FILE_CREATE_DIRECTORY)) {
        throw new \RuntimeException("Cannot create directory '$destination_dir'.");
      }
      if (!file_move($attachment, $uri)) {
        throw new \RuntimeException("Cannot move '{$attachment->getFileUri()}' to '$uri'.");
      }
    }
  }

  // Finally, remove the empty public://contact_form directory.
  file_unmanaged_delete_recursive('public://contact_form');
}

/**
 * Enable the Smart Trim module.
 */
function joinup_core_post_update_install_smart_trim() {
  \Drupal::service('module_installer')->install(['smart_trim']);
}

/**
 * Enable 'rdf_etl' and 'spain_ctt' modules.
 */
function joinup_core_post_update_install_rdf_etl_and_spain_ctt() {
  \Drupal::service('module_installer')->install(['rdf_etl', 'spain_ctt']);
}

/**
 * Enable and configure the 'rdf_schema_field_validation' module.
 */
function joinup_core_post_update_configure_rdf_schema_field_validation() {
  \Drupal::service('module_installer')->install(['rdf_schema_field_validation']);
  $graph_uri = 'http://adms-definition';
  $class_definition = 'http://www.w3.org/2000/01/rdf-schema#Class';

  $sparql_endpoint = \Drupal::service('sparql_endpoint');
  $connection_options = $sparql_endpoint->getConnectionOptions();
  $connect_string = 'http://' . $connection_options['host'] . ':' . $connection_options['port'] . '/sparql-graph-crud';
  // Use a local SPARQL 1.1 Graph Store.
  $gs = new GraphStore($connect_string);
  $graph = new Graph($graph_uri);
  $graph->parseFile(DRUPAL_ROOT . '/../resources/fixtures/adms-definition.rdf');
  $gs->replace($graph);

  $data = ['collection', 'solution', 'asset_release', 'asset_distribution'];
  foreach ($data as $bundle) {
    RdfEntityMapping::loadByName('rdf_entity', $bundle)
      ->setThirdPartySetting('rdf_schema_field_validation', 'property_predicates', ['http://www.w3.org/2000/01/rdf-schema#domain'])
      ->setThirdPartySetting('rdf_schema_field_validation', 'graph', $graph_uri)
      ->setThirdPartySetting('rdf_schema_field_validation', 'class', $class_definition)
      ->save();
  }
}

/**
 * Fix data type of the access url field[ISAICP-4349].
 */
function joinup_core_post_update_fix_access_url_datatype() {
  /** @var \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql_endpoint */
  $sparql_endpoint = \Drupal::service('sparql_endpoint');
  $retrieve_query = <<<QUERY
SELECT ?graph ?entity_id ?predicate ?access_url
WHERE {
  GRAPH ?graph {
    ?entity_id a ?type .
    ?entity_id ?predicate ?access_url .
    VALUES ?type { <http://www.w3.org/ns/dcat#Catalog> <http://www.w3.org/ns/dcat#Distribution> }
    VALUES ?predicate { <http://www.w3.org/ns/dcat#accessURL> <http://xmlns.com/foaf/spec/#term_homepage> }
    VALUES ?graph { <http://joinup.eu/collection/draft> <http://joinup.eu/collection/published> <http://joinup.eu/asset_distribution/published> }
    FILTER (!datatype(?access_url) = xsd:anyURI) .
  }
}
QUERY;
  $results = $sparql_endpoint->query($retrieve_query);
  $items_to_update = [];
  foreach ($results as $result) {
    // The above query should not have errors or duplicated values so lets keep
    // a simple array structure.
    $items_to_update[] = [
      '@graph' => $result->graph->getUri(),
      '@entity_id' => $result->entity_id->getUri(),
      '@predicate' => $result->predicate->getUri(),
      '@access_url' => $result->access_url->getValue(),
    ];
  }

  $update_query = <<<QUERY
  WITH <@graph>
  DELETE {
    <@entity_id> <@predicate> "@access_url"^^<http://www.w3.org/2001/XMLSchema#string>
  }
  INSERT {
    <@entity_id> <@predicate> "@access_url"^^<http://www.w3.org/2001/XMLSchema#anyURI>
  }
  WHERE {
    <@entity_id> <@predicate> "@access_url"^^<http://www.w3.org/2001/XMLSchema#string>
  }
QUERY;
  foreach ($items_to_update as $data_array) {
    $query = str_replace(array_keys($data_array), array_values($data_array), $update_query);
    $sparql_endpoint->query($query);
  }
}
