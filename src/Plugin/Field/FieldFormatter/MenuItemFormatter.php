<?php

/**
 * @file
 * Contains \Drupal\menu_link_field\Plugin\Field\FieldFormatter\MenuItemFormatter.
 */

namespace Drupal\menu_link_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'MenuItemFormatter' formatter.
 *
 * @FieldFormatter (
 *   id = "menu_link_field",
 *   label = @Translation("Menu Item"),
 *   field_types = {
 *     "menu_link_field"
 *   }
 * )
 */
class MenuItemFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Menu tree service
   */
  protected $menuTreeService;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, MenuLinkTreeInterface $menu_link_tree) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->menuTreeService = $menu_link_tree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $elements = array();

    foreach ($items as $delta => $item) {
      // Set the root of the menu equal to the menu_item_id
      $menu_parameters = new MenuTreeParameters();
      $menu_parameters->setRoot($item->menu_item_id);

      // Load the menu tree based on the saved menu_id
      $tree = $this->menuTreeService->load($item->menu_id, $menu_parameters);

      //These manipulators deal with access checking and sorting according to weights.
      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ];
      $tree = $this->menuTreeService->transform($tree, $manipulators);

      // Build the menu
      $elements[$delta] = $this->menuTreeService->build($tree);
    }

    return $elements;
  }
}
