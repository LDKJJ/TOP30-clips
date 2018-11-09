<?php

namespace Drupal\top30\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\top30\Controller\Top30Controller;

/**
 * Provides a 'Top30Block' block.
 *
 * @Block(
 *  id = "top30block",
 *  admin_label = @Translation("Top30block"),
 * )
 */
class Top30Block extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $controller_variable = new Top30Controller;
    $rendering_in_block = $controller_variable->getTOP30('list_clips_top30_floating');

    return $rendering_in_block;
  }

}