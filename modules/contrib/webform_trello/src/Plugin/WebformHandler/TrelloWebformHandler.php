<?php

namespace Drupal\webform_trello\Plugin\WebformHandler;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Webform trello handler.
 *
 * @WebformHandler(
 *   id = "trello",
 *   label = @Translation("Add Trello Card"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Add trello card of the submission."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class TrelloWebformHandler extends WebformHandlerBase {

  /**
   * The token manager.
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->tokenManager = $container->get('webform.token_manager');
    $instance->trelloApiService = $container->get('webform_trello.api');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $trelloSettings = $this->configFactory->get("webform_trello.settings");
    return [
      'board' => '',
      'list' => '',
      'name' => $trelloSettings->get('ticket.name') ?? "",
      'position' => 'top',
      'labels' => '',
      'members' => '',
      'description' => $trelloSettings->get('ticket.description') ?? "",
      'debug' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // Board settings.
    $form['board_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Board Settings'),
      '#open' => TRUE,
    ];

    $trelloSettings = $this->configFactory->get("webform_trello.settings");
    $apiKey = $trelloSettings->get('app.apikey') ?? "";
    $token = $trelloSettings->get('app.token') ?? "";
    if (empty($apiKey) || empty($token)) {
      $form['board_settings']['warning'] = [
        '#markup' => $this->t('Please input the <a href="@settings">trello app settings</a> first', [
          '@settings' => '/admin/structure/webform/config/handlers/trello',
        ]),
      ];
      return $this->setSettingsParents($form);
    }
    $form['board_settings']['board'] = [
      '#type' => 'select',
      '#title' => $this->t('Board'),
      '#description' => $this->t('Select the board you want to create ticket.'),
      '#options' => $this->trelloApiService->getBoardOptions(),
      '#default_value' => $this->configuration['board'],
      "#empty_option" => $this->t('- Select -'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'trelloListAjaxCallback'],
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => [
          'edit-list-wrapper',
          'edit-label-wrapper',
          'edit-member-wrapper',
        ],
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Searching Lists...'),
        ],
      ],
    ];

    if ($form_state->getValue('board')) {
      $form['board_settings']['list'] = [
        '#type' => 'select',
        '#title' => $this->t('List'),
        '#description' => $this->t('Select the list you want to create ticket.'),
        '#prefix' => '<div id="edit-list-wrapper">',
        '#suffix' => '</div>',
        "#empty_option" => $this->t('- Select -'),
        '#options' => $this->trelloApiService->getBoardListsOptions($form_state->getValue("board")),
        '#default_value' => $this->configuration['list'],
        '#required' => TRUE,
      ];
    }
    else {
      $form['board_settings']['list'] = [
        '#type' => 'select',
        '#title' => $this->t('List'),
        '#description' => $this->t('Select the list you want to create ticket.'),
        '#prefix' => '<div id="edit-list-wrapper">',
        '#suffix' => '</div>',
        "#empty_option" => $this->t('- Select -'),
        '#options' => $this->configuration['board'] ? $this->trelloApiService->getBoardListsOptions($this->configuration['board']) : [],
        '#default_value' => $this->configuration['list'],
        '#required' => TRUE,
      ];
    }
    // ticket.
    $form['ticket'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Trello ticket settings'),
    ];
    $form['ticket']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The title of the card'),
      '#default_value' => $this->configuration['name'],
      '#required' => TRUE,
    ];
    $form['ticket']['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => [
        'top' => $this->t('Top'),
        'bottom' => $this->t('Bottom'),
      ],
      '#default_value' => $this->configuration['position'] ?? "top",
      '#required' => TRUE,
      '#description' => $this->t('Top means the card will be created at the top of the list. Bottom means the card will be created at the bottom of the list.'),
    ];
    if ($form_state->getValue("board")) {
      $form['ticket']['labels'] = [
        '#type' => 'select',
        '#title' => $this->t('labels'),
        '#prefix' => '<div id="edit-label-wrapper">',
        '#suffix' => '</div>',
        '#multiple' => TRUE,
        '#options' => $this->trelloApiService->getBoardLabelOptions($form_state->getValue("board")),
        '#default_value' => $this->configuration['labels'],
      ];
      $form['ticket']['members'] = [
        '#type' => 'select',
        '#title' => $this->t('members'),
        '#prefix' => '<div id="edit-member-wrapper">',
        '#suffix' => '</div>',
        '#multiple' => TRUE,
        '#options' => $this->trelloApiService->getBoardMemberOptions($form_state->getValue("board")),
        '#default_value' => $this->configuration['members'],
      ];
    }
    else {
      $form['ticket']['labels'] = [
        '#type' => 'select',
        '#title' => $this->t('labels'),
        '#prefix' => '<div id="edit-label-wrapper">',
        '#suffix' => '</div>',
        '#multiple' => TRUE,
        '#options' => $this->configuration['board'] ? $this->trelloApiService->getBoardLabelOptions($this->configuration['board']) : [],
        '#default_value' => $this->configuration['labels'],
      ];
      $form['ticket']['members'] = [
        '#type' => 'select',
        '#title' => $this->t('members'),
        '#prefix' => '<div id="edit-member-wrapper">',
        '#suffix' => '</div>',
        '#multiple' => TRUE,
        '#options' => $this->configuration['board'] ? $this->trelloApiService->getBoardMemberOptions($this->configuration['board']) : [],
        '#default_value' => $this->configuration['members'],
      ];
    }

    $form['ticket']['description'] = [
      '#type' => 'webform_codemirror',
      '#title' => $this->t('Description'),
      '#default_value' => $this->configuration['description'],
      '#required' => TRUE,
      '#rows' => 5,
    ];

    // Development.
    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
    ];
    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, every handler method invoked will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['debug'],
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * Get the corresponding lists of trello board.
   */
  public function trelloListAjaxCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#edit-list-wrapper', $form["settings"]["board_settings"]["list"]));
    $response->addCommand(new ReplaceCommand('#edit-label-wrapper', $form["settings"]["ticket"]["labels"]));
    $response->addCommand(new ReplaceCommand('#edit-member-wrapper', $form["settings"]["ticket"]["members"]));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $boardId = $form_state->getValue('board');
    if (!$boardId) {
      $form_state->setErrorByName("['board_settings']['board']", $this->t('There is something wrong.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $boardId = $form_state->getValue('board');
    $listId = $form_state->getValue('list');
    $this->configuration['board'] = $boardId;
    $this->configuration['boardLabel'] = $this->trelloApiService->getBoardOptions()[$boardId];
    $this->configuration['list'] = $listId;
    $this->configuration['listLabel'] = $this->trelloApiService->getBoardListsOptions($boardId)[$listId];
    $this->configuration['name'] = $form_state->getValue('name');
    $this->configuration['description'] = $form_state->getValue('description');
    $this->configuration['position'] = $form_state->getValue('position');
    $this->configuration['labels'] = $form_state->getValue('labels');
    $this->configuration['members'] = $form_state->getValue('members');
    $this->configuration['debug'] = (bool) $form_state->getValue('debug');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {
    $list = $this->configuration['list'];
    $name = $this->configuration['name'];
    $name = $this->replaceTokens($name, $this->getWebformSubmission());
    $description = $this->configuration['description'];
    $description = $this->replaceTokens($description, $this->getWebformSubmission());
    $card = [
      'name' => $name,
      'desc' => Markup::create(Xss::filter($description))->__toString(),
      'pos' => $this->configuration['position'],
      'idLabels' => implode(",", $this->configuration['labels']),
      'idMembers' => implode(",", $this->configuration['members']),
    ];
    $this->trelloApiService->createCard($list, $card);
    $this->debug(__FUNCTION__);
  }

  /**
   * Display the invoked plugin method to end user.
   *
   * @param string $method_name
   *   The invoked method name.
   * @param string $context1
   *   Additional parameter passed to the invoked method name.
   */
  protected function debug($method_name, $context1 = NULL) {
    if (!empty($this->configuration['debug'])) {
      $t_args = [
        '@id' => $this->getHandlerId(),
        '@class_name' => get_class($this),
        '@method_name' => $method_name,
        '@context1' => $context1,
      ];
      $this->messenger()->addWarning($this->t('Invoked @id: @class_name:@method_name @context1', $t_args), TRUE);
    }
  }

}
