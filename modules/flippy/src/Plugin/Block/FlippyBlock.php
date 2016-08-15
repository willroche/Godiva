<?php

namespace Drupal\flippy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a "Flippy" block.
 *
 * @Block(
 *   id = "flippy_block",
 *   admin_label = @Translation("Flippy Block")
 * )
 */
class FlippyBlock extends BlockBase {

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    $build = array();
    // Detect if we're viewing a node
    if ($node = \Drupal::request()->attributes->get('node')) {
      // Make sure this node type is still enabled
      if (_flippy_use_pager($node)) {
        $children = [
          '#theme'    => 'flippy',
          '#list'     => \Drupal::service('flippy.pager')->flippy_build_list($node),
          '#attached' => array(
            'library' => array(
              'flippy/drupal.flippy',
            ),
          ),
        ];
        // Generate the block
        $build['#children'] = render($children);

        // Set head elements
        if (is_object($node)) {
          if (\Drupal::config('flippy.settings')->get('flippy_head_' . $node->getType())) {
            $links = \Drupal::service('flippy.pager')->flippy_build_list($node);
            if ($links['prev']['nid'] != FALSE) {
              $build['#attached']['html_head_link'][][] = array(
                'rel' => 'prev',
                'href' => Url::fromRoute('entity.node.canonical', array('node' => $links['prev']['nid']))->toString(),
              );
            }
            if ($links['next']['nid'] != FALSE) {
              $build['#attached']['html_head_link'][][] = array(
                'rel' => 'next',
                'href' => Url::fromRoute('entity.node.canonical', array('node' => $links['next']['nid']))->toString(),
              );
            }
          }
        }
      }
    }

    return $build;
  }
}
