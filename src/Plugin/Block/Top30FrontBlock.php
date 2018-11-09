<?php

namespace Drupal\top30\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\top30\Controller\Top30Controller;

/**
 * Provides a 'Top30FrontBlock' block.
 *
 * @Block(
 *  id = "top30frontblock",
 *  admin_label = @Translation("Top30Frontblock"),
 * )
 */
class Top30FrontBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $controller_variable = new Top30Controller;
    $rendering_in_block = $controller_variable->getTOP30('list_clips_top30front', 9);

    return $rendering_in_block;
  }

}