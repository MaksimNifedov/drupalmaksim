<?php

namespace Drupal\webform_trello\Form\AdminConfig;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\webform_trello\TrelloApiServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure webform admin settings for Mattermost.
 */
class WebformAdminConfigTrelloForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webform_trello.settings'];
  }

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The trello api service.
   *
   * @var \Drupal\webform_trello\TrelloApiServiceInterface
   */
  protected $trelloApiService;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_admin_config_trello_form';
  }

  /**
   * Constructs a WebformAdminConfigTrelloForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   * @param \Drupal\webform_trello\TrelloApiServiceInterface $trello_Api_Service
   *   The trello api service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, WebformTokenManagerInterface $token_manager, TrelloApiServiceInterface $trello_Api_Service) {
    parent::__construct($config_factory);
    $this->tokenManager = $token_manager;
    $this->trelloApiService = $trello_Api_Service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('webform.token_manager'),
      $container->get('webform_trello.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform_trello.settings');

    $form['app'] = [
      '#type' => 'details',
      '#title' => $this->t('App settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['app']['apikey'] = [
      '#title' => $this->t('Developer API KEY'),
      '#type' => 'textfield',
      '#description' => $this->t('This api key is provided by Trell. Please take a look on the following <a href="@trello-app-link" target="_blank">Developer API Keys</a>', [
        '@trello-app-link' => 'https://trello.com/app-key/',
      ]),
      '#required' => TRUE,
      '#default_value' => $config->get('app.apikey'),
    ];

    $form['app']['token'] = [
      '#title' => $this->t('Token'),
      '#type' => 'textfield',
      '#description' => $this->t('Generate the corresponding token via <a href="@trello-app-link" target="_blank">Developer API Keys</a>', [
        '@trello-app-link' => 'https://trello.com/app-key/',
      ]),
      '#required' => TRUE,
      '#default_value' => $config->get('app.token'),
    ];

    $form['ticket'] = [
      '#type' => 'details',
      '#title' => $this->t('Trello ticket settings'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['ticket']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The title of the card'),
      '#default_value' => $config->get('ticket.name'),
      '#required' => TRUE,
    ];
    $form['ticket']['description'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Description'),
      '#required' => TRUE,
      '#rows' => 5,
      '#default_value' => $config->get('ticket.description'),
    ];
    $form['ticket']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    $this->tokenManager->elementValidate($form);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->trelloApiService->authorization($form_state->getValue("app")["apikey"], $form_state->getValue("app")["token"])) {
      $form_state->setErrorByName('app[apikey]', $this->t('The key might be wrong'));
      $form_state->setErrorByName('app[token]', $this->t('The token might be wrong'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('webform_trello.settings');
    $config->set('app', $form_state->getValue('app'));
    $config->set('ticket', $form_state->getValue('ticket'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
