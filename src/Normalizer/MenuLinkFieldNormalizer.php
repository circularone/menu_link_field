<?php

namespace Drupal\menu_link_field\Normalizer;

use Drupal\serialization\Normalizer\FieldItemNormalizer;
use Drupal\menu_link_field\Plugin\Field\FieldType\MenuItem;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuLinkTreeInterface;

/**
 * Normalizes link interface items.
 */
class MenuLinkFieldNormalizer extends FieldItemNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = MenuItem::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $value = $field_item->getValue();

    $menu_tree_service = \Drupal::service('menu.link_tree');

    $menu_parameters = new MenuTreeParameters();

    if ($value['menu_item_id']) {
      $menu_parameters->setRoot($value['menu_item_id']);
    }

    // Load the menu tree based on the saved menu_id
    $tree = $menu_tree_service->load($value['menu_id'], $menu_parameters);

    //These manipulators deal with access checking and sorting according to weights.
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    $tree = $menu_tree_service->transform($tree, $manipulators);

    $menu = $this->serializer->normalize($tree);

    $this->removeKeys($menu);

    return $menu;
  }

  /**
   * Remove array keys.
   */
  protected function removeKeys(array &$data) {
    $tree = [];

    foreach ($data as $value) {
      if (isset($value['children']) && !empty($value['children'])) {
        $this->removeKeys($value['children']);
      }

      $tree[] = $value;
    }

    $data = $tree;
  }

}
