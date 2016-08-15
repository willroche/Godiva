<?php

namespace Drupal\yamlform\Tests;

/**
 * Tests for YAML form entity.
 *
 * @group YamlForm
 */
class YamlFormAdminSettingsTest extends YamlFormTestBase {

  /**
   * Tests YAML form admin settings.
   */
  public function testAdminSettings() {
    global $base_path;

    $this->drupalLogin($this->adminFormUser);

    /* Elements */

    // Check that description is 'after' the element.
    $this->drupalGet('yamlform/test_element');
    $this->assertPattern('#\{item title\}.+\{item markup\}.+\{item description\}#ms');

    // Set the default description display to 'before'.
    $this->drupalPostForm('admin/structure/yamlform/settings', ['elements[default_description_display]' => 'before'], t('Save configuration'));

    // Check that description is 'before' the element.
    $this->drupalGet('yamlform/test_element');
    $this->assertNoPattern('#\{item title\}.+\{item markup\}.+\{item description\}#ms');
    $this->assertPattern('#\{item title\}.+\{item description\}.+\{item markup\}#ms');

    /* UI settings */

    // Check that dialogs are enabled.
    $this->drupalGet('admin/structure/yamlform');
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/yamlform/add" class="button button-action button--primary button--small use-ajax" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:400}">Add form</a>');

    // Disable dialogs.
    $this->drupalPostForm('admin/structure/yamlform/settings', ['ui[dialog_disabled]' => TRUE], t('Save configuration'));

    // Check that dialogs are disabled. (ie use-ajax is not included)
    $this->drupalGet('admin/structure/yamlform');
    $this->assertNoRaw('<a href="' . $base_path . 'admin/structure/yamlform/add" class="button button-action button--primary button--small use-ajax" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:400}">Add form</a>');
    $this->assertRaw('<a href="' . $base_path . 'admin/structure/yamlform/add" class="button button-action button--primary button--small" data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:400}">Add form</a>');
  }

}
