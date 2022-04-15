<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Utils\Json;
use Nette\Security\Passwords;
use Tracy\Debugger;

final class GamesPresenter extends Nette\Application\UI\Presenter
{
    const GAMES_OVERVIEW_LIMIT = 20;
    private Nette\Database\Explorer $database;
    private Nette\Http\Response $response;
    private Nette\Http\Request $request;
    private Nette\Security\User $user;

	/** @var Passwords */
	private $passwords;    

	public function __construct(Nette\Database\Explorer $database,  Nette\Http\Response $response, Nette\Http\Request $request, Nette\Security\User $user, Passwords $passwords)
	{
		$this->database = $database;
		$this->response = $response;
		$this->request = $request;
        $this->user = $user;
        $this->passwords = $passwords;
	}

    protected function startup()
    {
        parent::startup();

        $this->getHttpResponse()->setHeader('Access-Control-Allow-Origin', 'http://snooker.cechmanek.com');
        $this->getHttpResponse()->setHeader('Access-Control-Allow-Headers', 'Content-Type');
        $this->getHttpResponse()->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    }

	public function renderDefault(): void
	{
		$this->sendJson(["message"=> "Snooker is the game"]);
	}

	public function actionOverview(int $id = 0): void
	{
        $totalRows = $this->database->table('games')->count('*');
        $query = $this->database->table('games')->limit(self::GAMES_OVERVIEW_LIMIT, self::GAMES_OVERVIEW_LIMIT * $id)->order('date DESC');
        $games = [];
        $i = 0;

        foreach ($query as $gamesResult) {
            foreach ($gamesResult as $key => $value) {
                $games[$i][$key] = $value;
            }
            $i++;
        }

		$this->sendJson([
            'status' => 1,
            'totalRows' => $totalRows,
            'games' => $games
        ]);
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

                $this->sendJson([
                    "status" => 1,
                    "newId" => null
                ]);
            } else{
                $row = $this->database->table('games')->insert([
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

                $this->sendJson([
                    "status" => 1,
                    "newId" => $row->id
                ]);
            }
        }

        $query = $this->database->table('games')->get($id);
        $game = null;

        if (!$query) {
            $this->sendJson([
                'status' => 404,
                'game' => null
            ]);
        }
    
        foreach ($query as $key => $value) {
            if($key === 'breaks1' || $key === 'breaks2'){
                $value = json_decode($value);
            }
            $game[$key] = $value;
        }

        $this->sendJson([
            'status' => 1,
            'game' => $game
        ]);
    }

    public function actionLogin(){
        $this->user->login("admin", "1234");
        //$this->user->logout();
        dump ("neco");
    }
}
