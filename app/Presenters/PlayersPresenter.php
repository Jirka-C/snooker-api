<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Utils\Json;
use Tracy\Debugger;

final class PlayersPresenter extends Nette\Application\UI\Presenter
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
        $totalRows = $this->database->table('players')->count('*');
        $players = $this->getAllPlayers();

		$this->sendJson([
            'status' => 1,
            'totalRows' => $totalRows,
            'players' => $players
        ]);
	}

    public function actionSave(int $id = null): void
    {
        $httpResponse = $this->getHttpRequest();

        if($httpResponse->getMethod() === "POST"){
            $player = Json::decode($httpResponse->getRawBody());

            if(!strlen($player->player)){
                $this->sendJson([
                    "status" => 400,
                ]);                
            }

            if($id){
                $playerRow = $this->database->table('players')->get($id);
                $playerRow->update([
                    "player" => $player->player,
                ]);

                $this->sendJson([
                    "status" => 1,
                    "players" => $this->getAllPlayers()
                ]);
            } else{
                $row = $this->database->table('players')->insert([
                    "player" => $player->player,
                ]);

                $this->sendJson([
                    "status" => 1,
                    "players" => $this->getAllPlayers()
                ]);
            }
        }
    }

    public function actionDelete(int $id = null): void
    {
        $httpResponse = $this->getHttpRequest();

        $playerRow = $this->database->table('players')->get($id);
        $playerRow->delete();

        $this->sendJson([
            "status" => 1,
            "players" => $this->getAllPlayers()
        ]);        
    }

    private function getAllPlayers()
    {
        $query = $this->database->table('players');
        $players = [];
        $i = 0;

        foreach ($query as $playersResult) {
            foreach ($playersResult as $key => $value) {
                $players[$i][$key] = $value;
            }
            $i++;
        }

        return $players;
    }
}
