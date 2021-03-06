<?php

declare(strict_types = 1);

namespace Drupal\joinup_video\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;
use GuzzleHttp\TransferStats;

/**
 * An European Commission video provider plugin.
 *
 * @VideoEmbedProvider(
 *   id = "joinup_ec_video",
 *   title = @Translation("European Commission video")
 * )
 */
class JoinupEcVideo extends ProviderPluginBase {

  /**
   * Static cache for resolved short URLs.
   *
   * @var string[]
   */
  protected static $resolvedUrl = [];

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $iframe = [
      '#type' => 'video_embed_iframe',
      '#provider' => 'joinup_ec_video',
      '#url' => '//audiovisual.ec.europa.eu/embed/index.html',
      '#query' => [
        'ref' => $this->getVideoId(),
        'lg' => $this->getLanguagePreference(),
        'starttime' => $this->getTimeIndex(),
        'autoplay' => $autoplay ? 'true' : 'false',
      ],
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'true',
        'mozallowfullscreen' => 'true',
        'webkitallowfullscreen' => 'true',
      ],
    ];

    return $iframe;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    // The URL may be an European Union short URL. Resolve it.
    if (preg_match('#^(?:(?:https?:)?//)?europa\.eu/\![0-9a-z]+$#i', $input)) {
      if (!isset(static::$resolvedUrl[$input])) {
        // @todo To be refactored in ISAICP-3885.
        /** @var \Psr\Http\Message\UriInterface $uri */
        \Drupal::httpClient()->get($input, [
          'on_stats' => function (TransferStats $stats) use (&$uri) {
            // @todo Allow coding standards checks on the following line once
            //   the Coder module correctly recognizes that the $uri variable is
            //   used.
            // @see https://www.drupal.org/project/coder/issues/3065679
            // @codingStandardsIgnoreLine
            $uri = $stats->getEffectiveUri();
          },
        ]);
        static::$resolvedUrl[$input] = $uri ? $uri->__toString() : $input;
      }
      $input = static::$resolvedUrl[$input];
    }

    $expressions = [
      // Backwards compatible with old style uris.
      '#^(?:(?:https?:)?//)?ec\.europa\.eu/(?:.*ref=)?(?<id>[^&\?]+)#i',
      // New style uris.
      '#^(?:(?:https?:)?//)?audiovisual\.ec\.europa\.eu/embed/index.html(?:.*ref=)?(?<id>[^&\?]+)#i',
    ];
    foreach ($expressions as $expression) {
      preg_match($expression, $input, $matches);
      if (isset($matches['id'])) {
        return $matches['id'];
      }
    }
    return FALSE;
  }

  /**
   * Gets the time index from the URL.
   *
   * @return string
   *   A time index parameter to pass to the frame or 0 if none is found.
   */
  protected function getTimeIndex() {
    preg_match('#[&?]starttime=(?<time_index>\d+)#i', $this->getInput(), $matches);
    return isset($matches['time_index']) ? $matches['time_index'] : 0;
  }

  /**
   * Extracts the language preference from the URL for use in closed captioning.
   *
   * @return string
   *   The language preference if one exists or 'en' if one could not be found.
   */
  protected function getLanguagePreference() {
    preg_match('#[&?](?:videolang|lg)=(?<language>[a-z\-]+)#i', $this->getInput(), $matches);
    return isset($matches['language']) ? $matches['language'] : 'en';
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return '';
  }

}
