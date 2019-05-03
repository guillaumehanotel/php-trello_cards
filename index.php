#!/usr/bin/php -q
<?php
require __DIR__ . '/vendor/autoload.php';

use Trello\Api\ApiInterface;
use Trello\Client;

class TrelloAPI {

    public $client;

    public function __construct(string $API_KEY, string $TOKEN) {
        $this->client = new Client();
        $this->client->authenticate($API_KEY, $TOKEN, Client::AUTH_URL_CLIENT_ID);
    }

    /**
     * Retourne tous les boards de la personne
     * @return array
     */
    public function getBoards() {
        return $this->client->api('member')->boards()->all();
    }

    /**
     * @param $boardName
     * @return array
     */
    public function getCardListsFromBoardName($boardName) {
        $boardId = $this->getBoardByName($boardName)['id'];
        $cardLists = $this->client->boards()->lists()->all($boardId);
        return array_map(function ($cardListRef) {
            return [
                'name' => $cardListRef['name'],
                'id' => $cardListRef['id']
            ];
        }, $cardLists);
    }

    /**
     * Retourne le board en fonction du nom passé en paramètre
     * @param $name
     * @return mixed|void
     */
    public function getBoardByName($name) {
        foreach ($this->getBoards() as $board)
            if ($board['name'] == $name)
                return $board;
        return null;
    }

    /**
     * Retourne toutes les cartes d'un board donné
     * @param $boardName
     * @param $allowedCardLists
     * @return array
     */
    public function getCardsFromBoardName($boardName, $allowedCardLists) {

        $cardLists = $this->getCardListsFromBoardName($boardName);
        $filteredCardLists = array_filter($cardLists, function ($cardList) use ($allowedCardLists) {
            return in_array($cardList['name'], $allowedCardLists);
        });

        $cards = [];
        foreach ($filteredCardLists as $cardList) {
            $cardListId = $cardList['id'];
            $cardsOfList = $this->client->lists()->cards()->all($cardListId);
            $cards = array_merge($cards, $cardsOfList);
        }

        $cards = array_map(function ($card) {
            return $card['name'];
        }, $cards);

        return $cards;
    }

    /**
     * @return ApiInterface
     */
    public function getMember() {
        return $this->client->api('member');
    }

}

$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

$api = new TrelloAPI(getenv('TRELLO_API_KEY'), getenv('TRELLO_TOKEN'));
$BOARD_NAME = "To Do List";
$ALLOWED_CARD_LISTS = ['Projects Ideas', 'Projects TO DO', 'TO DO', 'DOING'];
$cards = $api->getCardsFromBoardName($BOARD_NAME, $ALLOWED_CARD_LISTS);

print_r($cards);

/*
$card = $cards[rand(0, count($cards)-1)];
exec("export DISPLAY=:0; notify-send 'TO DO' '$card' ");
*/

?>