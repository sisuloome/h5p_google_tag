<?php

namespace Drupal\h5p_google_tag;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;
use Drupal\google_tag\Entity\ContainerManagerInterface;

/**
 * Class H5PGoogleTagHelper.
 */
class H5PGoogleTagHelper {

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * File URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs a new H5PGoogleTagHelper object.
   */
  public function __construct(RendererInterface $renderer, FileUrlGeneratorInterface $file_url_generator, ModuleExtensionList $extension_list_module) {
    $this->renderer = $renderer;
    $this->fileUrlGenerator = $file_url_generator;
    $this->moduleExtensionList = $extension_list_module;
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
    // The code is taken from the source below
    // https://www.lullabot.com/articles/early-rendering-a-lesson-in-debugging-drupal-8
    $context = new RenderContext();
    /* @var \Drupal\Core\Cache\CacheableDependencyInterface $result */
    $result = $this->renderer->executeInRenderContext($context, function() use ($data) {
      return $this->renderer->render($data);
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
    $tmp = [];
    google_tag_page_attachments($tmp);

    if ($tmp && isset($tmp['#attached'])) {
      $script_defaults = [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#value' => '',
        '#attributes' => [
          'type' => 'text/javascript',
        ],
      ];

      if (isset($tmp['#attached']['library']) && count($tmp['#attached']['library']) > 0) {
        $module_version = $this->moduleExtensionList->get('google_tag')->info['version'];

        foreach ($tmp['#attached']['library'] as $name) {
          switch($name) {
            case 'google_tag/gtm':
              $tmp['scripts']['gtm'] = $script_defaults;
              $tmp['scripts']['gtm']['#attributes']['src'] = $this->fileUrlGenerator->generateString($this->moduleExtensionList->getPath('google_tag') . '/js/gtm.js?v=' . $module_version);
              break;
            case 'google_tag/gtag':
              $tmp['scripts']['gtag'] = $script_defaults;
              $tmp['scripts']['gtag']['#attributes']['src'] = $this->fileUrlGenerator->generateString($this->moduleExtensionList->getPath('google_tag') . '/js/gtag.js?v=' . $module_version);
              break;
            default:
          }
        }
      }

      // Solution taken from lib/Drupal/Core/Asset/JsCollectionRenderer.php line 73
      $tmp['scripts']['drupal-settings'] = $script_defaults;
      $tmp['scripts']['drupal-settings']['#value'] = Json::encode($tmp['#attached']['drupalSettings']);
      $tmp['scripts']['drupal-settings']['#attributes']['type'] = 'application/json';
      $tmp['scripts']['drupal-settings']['#attributes']['data-drupal-selector'] = 'drupal-settings-json';
      $tmp['scripts']['drupal-settings']['#weight'] = -10;

      $tmp['scripts']['drupal-settings-loader'] = $script_defaults;
      $tmp['scripts']['drupal-settings-loader']['#attributes']['src'] = $this->fileUrlGenerator->generateString('core/misc/drupalSettingsLoader.js?v=' . \Drupal::VERSION);
      $tmp['scripts']['drupal-settings-loader']['#weight'] = -5;

      $tags[] = $this->renderableToHtml($tmp);
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
    $tmp = [];
    google_tag_page_top($tmp);

    if ($tmp && isset($tmp['google_tag_gtm_iframe']) && count($tmp['google_tag_gtm_iframe']) > 0) {
      $html[] = $this->renderableToHtml($tmp['google_tag_gtm_iframe']);
    }
  }

}
