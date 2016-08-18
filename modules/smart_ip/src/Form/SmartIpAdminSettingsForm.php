<?php

/**
 * @file
 * Contains \Drupal\smart_ip\Form\SmartIpAdminSettingsForm.
 */

namespace Drupal\smart_ip\Form;

use Drupal\smart_ip\SmartIp;
use Drupal\smart_ip\SmartIpEvents;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Smart IP main admin settings page.
 *
 * @package Drupal\smart_ip\Form
 */
class SmartIpAdminSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smart_ip_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    $configNames = ['smart_ip.settings'];
    /** @var \Drupal\smart_ip\AdminSettingsEvent $event */
    $event = \Drupal::service('smart_ip.admin_settings_event');
    // Allow Smart IP source module to add their config names
    $event->setEditableConfigNames($configNames);
    \Drupal::service('event_dispatcher')->dispatch(SmartIpEvents::GET_CONFIG_NAME, $event);
    $configNames = $event->getEditableConfigNames();
    return $configNames;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('smart_ip.settings');
    $dataSource = $config->get('data_source');

    if (!empty($dataSource)) {
      $errorSourceId = \Drupal::state()->get('smart_ip.request_db_error_source_id') ?: '';
      if (!empty($errorSourceId)) {
        // Container for update status and manual update
        $form['smart_ip_bin_database_update'] = [
          '#type'        => 'fieldset',
          '#title'       => t('Database Update Status'),
          '#collapsible' => FALSE,
          '#collapsed'   => FALSE,
          '#states'      => [
            'visible' => [
              ':input[name="smart_ip_source"]' => ['value' => $errorSourceId],
            ],
          ],
        ];

        $message = \Drupal::state()->get('smart_ip.request_db_error_message') ?: '';
        if (!empty($message)) {
          $message = "<div class='messages messages--error'>$message</div>";
        }
        $form['smart_ip_bin_database_update']['smart_ip_bin_update_database'] = [
          '#type'   => 'submit',
          '#value'  => t('Update database now'),
          '#submit' => [[get_class($this), 'manualUpdate']],
          '#prefix' => $message,
        ];
      }

      // Container for manual lookup
      $form['smart_ip_manual_lookup'] = [
        '#type'  => 'fieldset',
        '#title' => $this->t('Manual lookup'),
        '#collapsible' => FALSE,
        '#collapsed'   => FALSE,
      ];

      $form['smart_ip_manual_lookup']['smart_ip_lookup'] = [
        '#type'  => 'textfield',
        '#title' => $this->t('IP address'),
        '#description' => $this->t('An IP address may be looked up by entering the address above then pressing the %lookup button below.', ['%lookup' => t('Lookup')]),
      ];

      $storage = $form_state->getStorage();
      $lookupResponse = isset($storage['smart_ip_message']) ? $storage['smart_ip_message'] : '';
      $form['smart_ip_manual_lookup']['smart_ip_lookup_button'] = [
        '#type'   => 'submit',
        '#value'  => $this->t('Lookup'),
        '#submit' => [[get_class($this), 'manualLookup']],
        '#ajax' => [
          'callback' => [get_class($this), 'manualLookupAjax'],
          'effect'   => 'fade',
        ],
        '#suffix' => '<div id="smart-ip-location-manual-lookup">' . $lookupResponse . '</div>',
      ];
    }

    // Container for Smart IP source
    $form['smart_ip_data_source_selection'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Smart IP source'),
      '#collapsible' => FALSE,
      '#collapsed'   => FALSE,
    ];

    // Smart IP source selection
    $form['smart_ip_data_source_selection']['smart_ip_data_source'] = [
      '#type'    => 'radios',
      '#title'   => $this->t('Select Smart IP data source'),
      '#options' => [],
      '#default_value' => $dataSource,
    ];
    /** @var \Drupal\smart_ip\AdminSettingsEvent $event */
    $event = \Drupal::service('smart_ip.admin_settings_event');
    // Allow Smart IP source module to add their form elements
    $event->setForm($form);
    $event->setFormState($form_state);
    \Drupal::service('event_dispatcher')->dispatch(SmartIpEvents::DISPLAY_SETTINGS, $event);
    $form = $event->getForm();
    $form_state = $event->getFormState();

    if (empty($form['smart_ip_data_source_selection']['smart_ip_data_source']['#options'])) {
      // No Smart IP data source module enabled
      $form['smart_ip_data_source_selection']['smart_ip_data_source'] = [
        '#markup' => $this->t(
          'You do not have any Smart IP data source module enabled. Please 
          enable at least one @here.', [
            '@here' => Link::fromTextAndUrl($this->t('here'), Url::fromRoute('system.modules_list', [], ['fragment' => 'edit-modules-smart-ip-data-source']))->toString(),
          ]
        ),
      ];
    }
    // Container for Smart IP preference
    $form['smart_ip_preferences'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Smart IP settings'),
      '#collapsible' => FALSE,
      '#collapsed'   => FALSE,
    ];

    $userRoles = user_roles();
    $roles = [];
    foreach ($userRoles as $role_id => $role) {
      $roles[$role_id] = $role->label();
    }
    $form['smart_ip_preferences']['smart_ip_roles_to_geolocate'] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Roles To Geolocate'),
      '#default_value' => $config->get('roles_to_geolocate'),
      '#options'       => $roles,
      '#description'   => $this->t(
        'Select the roles you wish to geolocate. Note that selecting the 
        anonymous role will add substantial overhead.'),
    ];

    $form['smart_ip_preferences']['smart_ip_save_user_location_creation'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t("Save user's location details upon creation"),
      '#default_value' => $config->get('save_user_location_creation'),
      '#description'   => $this->t("One time storing of user's location details upon registration."),
    ];

    $form['smart_ip_preferences']['smart_ip_allowed_pages'] = [
      '#title'       => $this->t("Acquire/update user's geolocation on specific Drupal native pages"),
      '#type'        => 'textarea',
      '#rows'        => 5,
      '#description' => $this->t(
        "Specify pages by using their paths. Enter one path per line. The '*' 
        character is a wildcard. Example paths are %user for the current user's 
        page and %user-wildcard for every user page. %front is the front page. 
        Leave blank if all pages.", [
          '%user' => '/user',
          '%user-wildcard' => '/user/*',
          '%front' => '<front>',
        ]
      ),
      '#default_value' => $config->get('allowed_pages'),
    ];

    // Container for Smart IP debug tool
    $form['smart_ip_debug_tool'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Smart IP debug tool'),
      '#collapsible' => FALSE,
      '#collapsed'   => FALSE,
    ];

    $form['smart_ip_debug_tool']['smart_ip_debug'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Admin debug'),
      '#default_value' => $config->get('debug_mode'),
      '#description'   => $this->t('Enables administrator to spoof an IP Address for debugging purposes.'),
    ];

    $form['smart_ip_debug_tool']['smart_ip_test_ip_address'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('IP address to use for testing'),
      '#default_value' => $config->get('debug_mode_ip'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Triggers manual database update event to Smart IP data source module
   * listeners.
   */
  public static function manualUpdate() {
    /** @var \Drupal\smart_ip\DatabaseFileEvent $event */
    $event = \Drupal::service('smart_ip.database_file_event');
    // Allow Smart IP source module to act on manual database update
    \Drupal::service('event_dispatcher')->dispatch(SmartIpEvents::MANUAL_UPDATE, $event);
  }

  /**
   * Submit handler to lookup an IP address in the database.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @see \Drupal\smart_ip\Form\SmartIpAdminSettingsForm::manualLookupAjax
   */
  public static function manualLookup(array $form, FormStateInterface $form_state) {
    $ip = $form_state->getValue('smart_ip_lookup');
    $location = SmartIp::query($ip);
    if (isset($location['latitude']) && isset($location['longitude'])) {
      $message = '<p>' . t('IP Address @ip is assigned to the following location details:', ['@ip' => $ip]) . '</p>' .
        '<dl>' .
          '<dt>' . t('Country:') . '</dt>' .
          '<dd>' . t('%country', ['%country' => $location['country']]) . '</dd>' .
          '<dt>' . t('Country code:') . '</dt>' .
          '<dd>' . t('%country_code', ['%country_code' => $location['countryCode']]) . '</dd>' .
          '<dt>' . t('Region:') . '</dt>' .
          '<dd>' . t('%region', ['%region' => $location['region']]) . '</dd>' .
          '<dt>' . t('City:') . '</dt>' .
          '<dd>' . t('%city', ['%city' => $location['city']]) . '</dd>' .
          '<dt>' . t('Postal code:') . '</dt>' .
          '<dd>' . t('%zip', ['%zip' => $location['zip']]) . '</dd>' .
          '<dt>' . t('Latitude:') . '</dt>' .
          '<dd>' . t('%latitude', ['%latitude' => $location['latitude']]) . '</dd>' .
          '<dt>' . t('Longitude:') . '</dt>' .
          '<dd>' . t('%longitude', ['%longitude' => $location['longitude']]) . '</dd>' .
          '<dt>' . t('Time zone:') . '</dt>' .
          '<dd>' . t('%time_zone', ['%time_zone' => $location['timeZone']]) . '</dd>' .
        '</dl>';
    }
    else {
      $message = t('IP Address @ip is not assigned to any location.', ['@ip' => $ip]);
    }
    $storage['smart_ip_message'] = $message;
    $form_state->setStorage($storage);
    $form_state->setRebuild();
  }

  /**
   * Submit handler to lookup an IP address in the database.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   * @see \Drupal\smart_ip\Form\SmartIpAdminSettingsForm::manualLookup
   */
  public static function manualLookupAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $storage  = $form_state->getStorage();
    $value    = isset($storage['smart_ip_message']) ? $storage['smart_ip_message'] : '';
    $response->addCommand(new HtmlCommand('#smart-ip-location-manual-lookup', $value));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('smart_ip_debug') == TRUE && $form_state->isValueEmpty('smart_ip_test_ip_address')) {
      $form_state->setErrorByName('smart_ip_test_ip_address', $this->t('Please enter the IP address to use for testing.'));
    }
    if (!empty($form['smart_ip_data_source_selection']['smart_ip_data_source']['#options']) && empty($form_state->getValue('smart_ip_data_source'))) {
      $form_state->setErrorByName('smart_ip_data_source', $this->t('Please select a Smart IP data source.'));
    }
    /** @var \Drupal\smart_ip\AdminSettingsEvent $event */
    $event = \Drupal::service('smart_ip.admin_settings_event');
    // Allow Smart IP source module to add validation on their form elements
    $event->setForm($form);
    $event->setFormState($form_state);
    \Drupal::service('event_dispatcher')->dispatch(SmartIpEvents::VALIDATE_SETTINGS, $event);
    $form = $event->getForm();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $debugMode = $form_state->getValue('smart_ip_debug');
    if ($debugMode) {
      $ip   = $form_state->getValue('smart_ip_test_ip_address');
      $type = $this->t('debug');
    }
    else {
      $ip   = \Drupal::request()->getClientIp();
      $type = $this->t('actual');
    }
    $location = \Drupal\smart_ip\SmartIp::query($ip);
    if (isset($location['latitude']) && isset($location['longitude'])) {
      drupal_set_message($this->t('Using @type IP: %ip / Country: %country / Region: %region / City: %city / Postal code: %zip / Longitude: %long / Latitude: %lat / Time zone: %time_zone', [
        '@type'      => $type,
        '%ip'        => $location['ipAddress'],
        '%country'   => $location['country'],
        '%region'    => $location['region'],
        '%city'      => $location['city'],
        '%zip'       => $location['zip'],
        '%long'      => $location['longitude'],
        '%lat'       => $location['latitude'],
        '%time_zone' => $location['timeZone'],
      ]));
    }
    \Drupal::service('smart_ip.smart_ip_location')->save();
    $this->config('smart_ip.settings')
      ->set('data_source', $form_state->getValue('smart_ip_data_source'))
      ->set('roles_to_geolocate', $form_state->getValue('smart_ip_roles_to_geolocate'))
      ->set('save_user_location_creation', $form_state->getValue('smart_ip_save_user_location_creation'))
      ->set('debug_mode', $debugMode)
      ->set('debug_mode_ip', $form_state->getValue('smart_ip_test_ip_address'))
      ->set('allowed_pages', $form_state->getValue('smart_ip_allowed_pages'))
      ->save();
    /** @var \Drupal\smart_ip\AdminSettingsEvent $event */
    $event = \Drupal::service('smart_ip.admin_settings_event');
    // Allow Smart IP source module to add validation on their form elements
    $event->setForm($form);
    $event->setFormState($form_state);
    \Drupal::service('event_dispatcher')->dispatch(SmartIpEvents::SUBMIT_SETTINGS, $event);
    $form = $event->getForm();
  }
}