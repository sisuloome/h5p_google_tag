<?php

namespace Drupal\h5p_google_tag;

use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Class H5PGoogleTagHelper.
 */
class H5PGoogleTagHelper {

  /**
   * Constructs a new H5PGoogleTagHelper object.
   */
  public function __construct() {

  }

  /**
   * Responds with rendered HTML representation of data.
   * Creates a new RenderContext and converts data structure into renderable,
   * then responds with HTML representation of the result.
   * @param  array $data
   *   Data structure to be rendered
   * @return string
   *   HTML representation of renderable data
   */
  private function renderableToHtml(array $data) {
    // The code is taked from the source below
    // https://www.lullabot.com/articles/early-rendering-a-lesson-in-debugging-drupal-8
    $context = new RenderContext();
    /* @var \Drupal\Core\Cache\CacheableDependencyInterface $result */
    $result = \Drupal::service('renderer')->executeInRenderContext($context, function() use ($data) {
      return render($data);
    });
    // Handle any bubbled cacheability metadata.
    if (!$context->isEmpty()) {
      $bubbleable_metadata = $context->pop();
      BubbleableMetadata::createFromObject($result)
      ->merge($bubbleable_metadata);
    }

    return (string) $result;
  }

  /**
   * Extracts script attachments from service of google_tag module and converts
   * those into HTML string. Appends that to tags. Produces a single string
   * representation even in case of multiple containers.
   * @param  array  $tags
   *   Tags data structure provided by initial hook.
   */
  public function getScriptAttachmentsHtml(array &$tags) {
    $manager = \Drupal::service('google_tag.container_manager');

    $tmp = [];
    $manager->getScriptAttachments($tmp);

    if ($tmp && isset($tmp['#attached']['html_head'])) {
      // Remove any script identifiers added so that renderer would not throw an
      // error
      array_walk($tmp['#attached']['html_head'], function(&$single) {
        if (is_array($single) && count($single) === 2) {
          if (preg_match('/^google_tag_script_tag/', $single[1])) {
            unset($single[1]);
          }
        }
      });
      $tags[] = $this->renderableToHtml($tmp['#attached']['html_head']);
    }
  }

  /**
   * Extracts noscript attachments from service of google_tag module and
   * converts those into HTML string. Appends that data structure. Produces a
   * single string representation even in case of multiple containers.
   * @param  array  $html
   *   Data structure provided by initial hook.
   */
  public function getNoScriptAttachmentsHtml(array &$html) {
    $manager = \Drupal::service('google_tag.container_manager');

    $tmp = [];
    $manager->getNoScriptAttachments($tmp);

    if ($tmp && count($tmp) > 0) {
      $html[] = $this->renderableToHtml($tmp);
    }

  }

}