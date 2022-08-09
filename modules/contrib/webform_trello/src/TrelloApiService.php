<?php

namespace Drupal\webform_trello;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Serialization\Json;

/**
 * This service integrates with Trello api and provide what we need in handler.
 */
class TrelloApiService implements TrelloApiServiceInterface {

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new TrelloApiService object.
   */
  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $factory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->loggerFactory = $factory;
    $this->apiUrl = 'https://api.trello.com/1';
  }

  /**
   * Connect to the trello api.
   *
   * @param string $key
   *   The apikey provided by trello.
   * @param string $token
   *   The token provided by trello.
   *
   * @return bool
   *   The authorization is passed or not.
   */
  public function authorization($key = "", $token = "") {
    $config = $this->configFactory->get('webform_trello.settings');
    $apiKey = $key ? $key : $config->get('app.apikey') ?? "";
    $token = $token ? $token : $config->get('app.token') ?? "";
    try {
      $request = $this->httpClient->request('GET', $this->apiUrl . '/members/me', [
        'query' => [
          'key' => $apiKey,
          'token' => $token,
        ],
      ]);
      return $request->getStatusCode() == 200;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('webform_trello')->error($e->getMessage());
      return FALSE;
    }

    return $request->getStatusCode() == 200;
  }

  /**
   * Api Url from Trello.
   *
   * @param string $type
   *   The api data we want to get.
   *
   * @todo we need to add cache api to prevent grab data everytime.
   */
  protected function apiUrl($type) {
    $config = $this->configFactory->get('webform_trello.settings');
    $apiKey = $config->get('app.apikey') ?? "";
    $token = $config->get('app.token') ?? "";
    $request = $this->httpClient->request('GET', $this->apiUrl . $type, [
      'query' => [
        'key' => $apiKey,
        'token' => $token,
      ],
    ]);
    return Json::decode($request->getBody()->getContents());
  }

  /**
   * Get all the boards information.
   */
  public function getBoards() {
    return $this->apiUrl('/members/me/boards');
  }

  /**
   * Get the board information by provide board id.
   *
   * @param string $boardId
   *   The id of the board.
   */
  public function getBoard($boardId) {
    return $this->apiUrl('/boards/' . $boardId);
  }

  /**
   * Get all the lists of the board.
   *
   * @param string $boardId
   *   The id of the board.
   */
  public function getBoardLists($boardId) {
    return $this->apiUrl('/boards/' . $boardId . '/lists');
  }

  /**
   * Get the labels information by providing board id.
   */
  public function getLables($boardId) {
    return $this->apiUrl('/boards/' . $boardId . '/labels');
  }

  /**
   * Get the members information by providing board id.
   */
  public function getMembers($boardId) {
    return $this->apiUrl('/boards/' . $boardId . '/members');
  }

  /**
   * Helper function to get the board options.
   */
  public function getBoardOptions() {
    $options = [];
    $boards = $this->getBoards();
    foreach ($boards as $board) {
      $options[$board['id']] = $board['name'];
    }
    return $options;
  }

  /**
   * Helper function to get the lists options of the board.
   *
   * @param string $boardId
   *   The id of the board.
   */
  public function getBoardListsOptions($boardId) {
    $lists = $this->getBoardLists($boardId);
    $options = [];
    foreach ($lists as $list) {
      $options[$list['id']] = $list['name'];
    }
    return $options;
  }

  /**
   * Helper function to get the members options of the board .
   *
   * @param string $boardId
   *   The id of the board.
   */
  public function getBoardMemberOptions($boardId) {
    $members = $this->getMembers($boardId);
    $options = [];
    foreach ($members as $member) {
      $options[$member['id']] = $member['fullName'];
    }
    return $options;
  }

  /**
   * Helper function to get the labels of the board.
   *
   * @param string $boardId
   *   The id of the board.
   */
  public function getBoardLabelOptions($boardId) {
    $options = [];
    $labels = $this->getLables($boardId);
    foreach ($labels as $label) {
      $options[$label['id']] = $label['name'];
    }
    return $options;
  }

  /**
   * Create a card of the ticket.
   *
   * @param string $idList
   *   The id of the list.
   * @param array $card
   *   The card information.
   */
  public function createCard($idList, array $card) {
    $config = $this->configFactory->get('webform_trello.settings');
    $apiKey = $config->get('app.apikey') ?? "";
    $token = $config->get('app.token') ?? "";
    $request = $this->httpClient->request('POST', $this->apiUrl . '/cards', [
      'query' => [
        'key' => $apiKey,
        'token' => $token,
        'name' => $card['name'] ?? "",
        'desc' => $card['desc'] ?? "",
        'pos' => $card['pos'] ?? "top",
        'idList' => $idList,
        'idMembers' => $card['idMembers'] ?? "",
        'idLabels' => $card['idLabels'] ?? "",
      ],
    ]);
    return Json::decode($request->getBody()->getContents());
  }

}
