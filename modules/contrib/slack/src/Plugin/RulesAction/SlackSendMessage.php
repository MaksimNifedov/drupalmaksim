<?php

namespace Drupal\slack\Plugin\RulesAction;

use Drupal\slack\Slack;
use Drupal\rules\Core\RulesActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'Slack send message' action.
 *
 * @RulesAction(
 *   id = "rules_slack_send_message",
 *   label = @Translation("Send message to Slack"),
 *   category = @Translation("Slack"),
 *   context = {
 *     "message" = @ContextDefinition("string",
 *       label = @Translation("Message"),
 *       description = @Translation("Specify the message, which should be sent to Slack."),
 *     ),
 *     "channel" = @ContextDefinition("string",
 *       label = @Translation("Channel"),
 *       description = @Translation("Specify the channel."),
 *       default_value = NULL,
 *       required = FALSE,
 *     ),
 *     "username" = @ContextDefinition("string",
 *       label = @Translation("User name"),
 *       description = @Translation("Specify the user name."),
 *       default_value = NULL,
 *       required = FALSE,
 *     ),
 *   }
 * )
 */
class SlackSendMessage extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * Slack service.
   *
   * @var \Drupal\slack\Slack
   */
  protected $slackService;

  /**
   * Constructs a SlackSendMessage object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\slack\Slack $slack_service
   *   The Slack manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Slack $slack_service) {
    $this->slackService = $slack_service;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\slack\Slack $slack_service */
    $slack_service = $container->get('slack.slack_service');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $slack_service
    );
  }

  /**
   * Send message to slack.
   *
   * @param string $message
   *   The message to be sent.
   * @param string $channel
   *   The slack channel.
   * @param string $username
   *   The slack username.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function doExecute($message, $channel = '', $username = '') {
    $this->slackService->sendMessage($message, $channel, $username);
  }

}
