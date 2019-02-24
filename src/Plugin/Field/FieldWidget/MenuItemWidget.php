<?php

/**
 * @file
 * Contains \Drupal\menu_link_field\Plugin\Field\FieldWidget\MenuItemWidget.
 */

namespace Drupal\menu_link_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'MenuItemWidget' widget.
 *
 * @FieldWidget (
 *   id = "menu_link_field",
 *   label = @Translation("Menu Item widget"),
 *   field_types = {
 *     "menu_link_field"
 *   }
 * )
 */
class MenuItemWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager
   */
  protected $entityTypeManager;

  /**
   * Menu storage
   */
  protected $menuStorage;

  /**
   * Menu link tree
   */
  protected $menuLinkTree;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, MenuLinkTreeInterface $menu_link_tree) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->menuStorage = $this->entityTypeManager->getStorage('menu');
    $this->menuLinkTree = $menu_link_tree;
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
      $configuration['third_party_settings'], 
      $container->get('entity_type.manager'),
      $container->get('menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $menus = $this->menuStorage->loadMultiple();

    /**
     * Create the menu options
     */
    $menu_options = ['' => t('- None -')];
    foreach($menus as $key => $menu) {
      $menu_options[$key] = $menu->label();
    }

    $field_name = $items->getName();

    /**
     * Create the menu_item options
     */
    // Check if menu is already set
    $menu_id = $items[$delta]->menu_id;
    // If it's not set, set it from the first in the menus
    if(!$menu_id){
      reset($menus);
      $menu_id = key($menus);
    }

    // If the menu has been set in $form_state load that menu instead
    $values = $form_state->getValue($field_name); 

    if (!empty($values)) {
      $menu_id = $values[$delta]['menu_id'];
    }

    $menu_item_id_options = ['' => t('- None -')];
    $menu_tree = $this->menuLinkTree->load($menu_id, new MenuTreeParameters());
    $this->generateMenuItemList($menu_item_id_options, $menu_tree);

    // Menu Selection
    $element['menu_id'] = [
      '#type' => 'select',
      '#options' => $menu_options,
      '#title' => t('Menu'),
      '#default_value' => isset($items[$delta]->menu_id) ? $items[$delta]->menu_id : '',
      '#ajax' => [
        'callback' => [$this, 'updateMenuItemsCallback'],
        'event' => 'change',
        'wrapper' => 'menu-item-select',
      ],
    ];

    // Menu item Selection
    $element['menu_item_id'] = [
      '#title' => t('Menu item'),
      '#type' => 'select',
      '#default_value' => isset($items[$delta]->menu_item_id) ? $items[$delta]->menu_item_id : '',
      '#options' => $menu_item_id_options,
      '#empty_value' => '',
      '#required' => FALSE,
      '#prefix' => '<div id="menu-item-select">',
      '#suffix' => '</div>',
    ];

    return $element;
  }

  /**
   * Returns menu item id form element after Menu is selected
   */
  public function updateMenuItemsCallback(array &$form, FormStateInterface $form_state) : array {
    // Get the value from the menu_id element that triggered the state change
    $triggering_element = $form_state->getTriggeringElement();
    $field_element = $triggering_element['#parents'][0];

    $element = $form[$field_element]['widget'][0]['menu_item_id'];

    return $element;
  }

  /**
   * Generate an array of Menu Items for use in an #options list
   */
  private function generateMenuItemList(&$output, &$input) {
    foreach($input as $key => $item) {
      //If menu element disabled skip this branch
      if ($item->link->isEnabled()) {
        $key = $key;
        $name = $item->link->getTitle();

        $output[$key] = $name;
        if($item->hasChildren){
          $this->generateMenuItemList($output, $item->subtree);
        }
      }
    }
  }

}