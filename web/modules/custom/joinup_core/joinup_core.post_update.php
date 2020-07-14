<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\rdf_taxonomy\Entity\RdfTerm;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * Insert the new EIF vocabulary into the database.
 */
function joinup_core_post_update_0106300(): void {
  $sparql_connection = Database::getConnection('default', 'sparql_default');
  $connection_options = $sparql_connection->getConnectionOptions();
  $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
  $graph_store = new GraphStore($connect_string);

  $filepath = __DIR__ . '/../../../../resources/fixtures/eif_voc.rdf';
  $graph = new Graph('http://eif_voc');
  $graph->parse(file_get_contents($filepath));
  $graph_store->insert($graph);
}

/**
 * Create the references page in the EIF Toolbox menu.
 */
function joinup_core_post_update_0106301(array &$sandbox): void {
  $menu_name = 'ogmenu-3444';
  $internal_path = Url::fromRoute('view.eif_recommendations.page')->toUriString();
  $link = MenuLinkContent::create([
    'title' => t('Recommendations'),
    'menu_name' => $menu_name,
    'link' => ['uri' => $internal_path],
    'weight' => 4,
  ]);
  $link->save();
}

/**
 * Re-import the fixtures and fix existing solutions.
 */
function joinup_core_post_update_0106302(&$sandbox) {
  // Clean up the existing graph.
  $sparql_connection = Database::getConnection('default', 'sparql_default');
  $sparql_connection->query('WITH <http://eira_skos> DELETE { ?s ?p ?o } WHERE { ?s ?p ?o } ');

  // Re import the file to update the terms.
  $connection_options = $sparql_connection->getConnectionOptions();
  $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
  $graph_store = new GraphStore($connect_string);

  $filepath = __DIR__ . '/../../../../resources/fixtures/EIRA_SKOS.rdf';
  $graph = new Graph('http://eira_skos');
  $graph->parse(file_get_contents($filepath));
  $graph_store->insert($graph);

  // Repeat steps taken after importing the fixtures that target eira terms.
  // @see: \Joinup\Phing\AfterFixturesImportCleanup::main
  $sparql_connection->query('WITH <http://eira_skos> INSERT { ?subject a skos:Concept } WHERE { ?subject a skos:Collection . };');
  $sparql_connection->query('WITH <http://eira_skos> INSERT INTO <http://eira_skos> { ?subject skos:topConceptOf <http://data.europa.eu/dr8> } WHERE { ?subject a skos:Concept .};');
  $sparql_connection->query('WITH <http://eira_skos> INSERT { ?member skos:broaderTransitive ?collection } WHERE { ?collection a skos:Collection . ?collection skos:member ?member };');
}

/**
 * Index the EIF terms since they are now tracked.
 */
function joinup_core_post_update_0106303(): void {
  $filepath = __DIR__ . '/../../../../resources/fixtures/eif_voc.rdf';
  $graph = new Graph('http://eif_voc');
  $graph->parse(file_get_contents($filepath));
  foreach ($graph->resources() as $resource_id => $resource) {
    if ($term = RdfTerm::load($resource_id)) {
      ContentEntity::indexEntity($term);
    }
  }

  $index = \Drupal::entityTypeManager()->getStorage('search_api_index')->load('published');
  $index->indexItems(-1, 'entity:taxonomy_term');
}
