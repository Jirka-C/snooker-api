<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Utils\Json;
use Tracy\Debugger;

final class GamesPresenter extends Nette\Application\UI\Presenter
{
    private Nette\Database\Explorer $database;
    private Nette\Http\Response $response;
    private Nette\Http\Request $request;

	public function __construct(Nette\Database\Explorer $database,  Nette\Http\Response $response, Nette\Http\Request $request)
	{
		$this->database = $database;
		$this->response = $response;
		$this->request = $request;
	}

    protected function startup()
    {
        parent::startup();

        $this->getHttpResponse()->setHeader('Access-Control-Allow-Origin', '*');
        $this->getHttpResponse()->setHeader('Access-Control-Allow-Headers', 'Content-Type');
        $this->getHttpResponse()->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    }

	public function renderDefault(): void
	{
        $query = $this->database->table('games')->order('date DESC');
        $games = [];
        $i = 0;

        foreach ($query as $gamesResult) {
            foreach ($gamesResult as $key => $value) {
                $games[$i][$key] = $value;
            }
            $i++;
        }

		$this->sendJson($games);
	}

    public function actionGame(int $id = null): void
    {
        $httpResponse = $this->getHttpRequest();

        if($httpResponse->getMethod() === "POST"){
            $game = Json::decode($httpResponse->getRawBody());

            if($id){
                $gameRow = $this->database->table('games')->get($id);
                $gameRow->update([
                    "player1" => $game->playerOne->name,
                    "score1" => $game->playerOne->score,
                    "frames1" => $game->playerOne->frames,
                    "breaks1" => Json::encode($game->playerOne->breaks),
                    "player2" => $game->playerTwo->name,
                    "score2" => $game->playerTwo->score,
                    "frames2" => $game->playerTwo->frames,
                    "breaks2" => Json::encode($game->playerTwo->breaks),
                    "date" => date("Y-m-d H:i:m")                    
                ]);

                $this->sendJson(["Message"=>"Aktualizace hry"]);
            } else{
                $this->database->table('games')->insert([
                    "player1" => $game->playerOne->name,
                    "score1" => $game->playerOne->score,
                    "frames1" => $game->playerOne->frames,
                    "breaks1" => Json::encode($game->playerOne->breaks),
                    "player2" => $game->playerTwo->name,
                    "score2" => $game->playerTwo->score,
                    "frames2" => $game->playerTwo->frames,
                    "breaks2" => Json::encode($game->playerTwo->breaks),
                    "date" => date("Y-m-d H:i:m")
                ]);

                $this->sendJson(["Message"=>"Nová hra uložena"]);
            }
        }

        $query = $this->database->table('games')->get($id);
        $game = [];

        if (!$query) {
            $this->sendJson($game);
        }
    
        foreach ($query as $key => $value) {
            if($key === 'breaks1' || $key === 'breaks2'){
                $value = json_decode($value);
            }
            $game[$key] = $value;
        }

        $this->sendJson($game);
    }
}
