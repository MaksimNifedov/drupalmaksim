<?php

namespace Drupal\webform_trello;

/**
 * This service integrates with Trello api and provide what we need in handler.
 */
interface TrelloApiServiceInterface {

  /**
   * Connect to the trello api.
   *
   * @return bool
   *   The authorization is passed or not.
   */
  public function authorization($key, $token);

  /**
   * Get all the boards information.
   */
  public function getBoards();

  /**
   * Get the board information by provide board id.
   *
   * @param string $boardId
   *   The id of the board.
   */
  public function getBoard($boardId);

  /**
   * Get all the lists of the board.
   *
   * @param string $boardId
   *   The id of the board.
   */
  public function getBoardLists($boardId);

  /**
   * Get the labels information by provide board id.
   */
  public function getLables($boardId);

  /**
   * Get the members information by providing board id.
   */
  public function getMembers($boardId);

  /**
   * Helper function to get the board options.
   */
  public function getBoardOptions();

  /**
   * Helper function to get the lists fo the board options.
   *
   * @param string $boardId
   *   The id of the board.
   */
  public function getBoardListsOptions($boardId);

  /**
   * Helper function to get the labels of the board.
   *
   * @param string $boardId
   *   The id of the board.
   */
  public function getBoardLabelOptions($boardId);

  /**
   * Helper function to get the members options of the board .
   *
   * @param string $boardId
   *   The id of the board.
   */
  public function getBoardMemberOptions($boardId);

  /**
   * Create a card of the ticket.
   *
   * @param string $idList
   *   The id of the list.
   * @param array $card
   *   The card information.
   */
  public function createCard($idList, array $card);

}
