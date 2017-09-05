<?php

namespace Drupal\joinup\Traits;

use Behat\Mink\Element\NodeElement;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Helper methods to deal with contextual links.
 */
trait ContextualLinksTrait {

  /**
   * Find all the contextual links in a region, without the need for javascript.
   *
   * @param string $region
   *   The name of the region.
   *
   * @return array
   *   An array of links found keyed by title.
   *
   * @throws \Exception
   *   When the region is not found in the page.
   */
  protected function findContextualLinksInRegion($region) {
    return $this->findContextualLinksInElement($this->getRegion($region));
  }

  /**
   * Find all the contextual links in an element.
   *
   * Contextual links are retrieved on the browser side through the use
   * of javascript, but that is not applicable for non-javascript browsers.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The name of the element to check.
   *
   * @return array
   *   An array of links found keyed by title.
   */
  protected function findContextualLinksInElement(NodeElement $element) {
    // We want to make an extra request to the website, using all the cookies
    // from the current logged in user, but doing so will change the last page
    // output, possibly breaking other steps. This can be prevented by cloning
    // the client.
    /** @var \Symfony\Component\BrowserKit\Client $client */
    $client = clone $this->getSession()->getDriver()->getClient();

    $contextual_ids = array_map(function ($element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      return $element->getAttribute('data-contextual-id');
    }, $element->findAll('xpath', '//*[@data-contextual-id]'));

    // @see Drupal.behaviors.contextual.attach(), contextual.js
    $client->request('POST', '/contextual/render', [
      'ids' => $contextual_ids,
    ]);

    $links = [];
    $response = json_decode($client->getResponse()->getContent(), TRUE);
    if ($response) {
      foreach ($contextual_ids as $id) {
        if (isset($response[$id])) {
          $crawler = new Crawler();
          $crawler->addHtmlContent($response[$id]);

          foreach ($crawler->filterXPath('//a') as $node) {
            /** @var \DOMElement $node */
            $links[$node->nodeValue] = $node->getAttribute('href');
          }
        }
      }
    }

    return $links;
  }

}
