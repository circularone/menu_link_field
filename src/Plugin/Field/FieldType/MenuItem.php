<?php

/**
 * @file
 * Contains \Drupal\menu_link_field\Plugin\Field\FieldType\MenuItem.
 */

namespace Drupal\menu_link_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'MenuItem' field type.
 *
 * @FieldType (
 *   id = "menu_link_field",
 *   label = @Translation("Menu Item"),
 *   description = @Translation("Stores a menu ID and menu item ID."),
 *   default_widget = "menu_link_field",
 *   default_formatter = "menu_link_field"
 * )
 */
class MenuItem extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'menu_id' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ),
        'menu_item_id' => array(
          'type' => 'varchar',
          'length' => 1024,
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $menu_id = $this->get('menu_id')->getValue();
    return empty($menu_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Add our properties.
    $properties['menu_id'] = DataDefinition::create('string')
      ->setLabel(t('Menu ID'))
      ->setDescription(t('The ID of the selected menu'));

    $properties['menu_item_id'] = DataDefinition::create('string')
      ->setLabel(t('Menu Item ID'))
      ->setDescription(t('The ID of the selected menu item'));

    return $properties;
  }
}