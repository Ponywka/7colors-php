<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use App\Rules\is_odd;
use App\Models\Game;

class GameController extends Controller
{
    private $colors = ["blue", "green", "cyan", "red", "magenta", "yellow", "white"];

    // Converting from values with which you can work to values for database
    private function fieldToString($field){
        $string = "";
        foreach ($field as $y){
            foreach ($y as $x){
                $color_id = array_search($x["color"], $this->colors) % 10;
                $string .= $color_id . $x["playerId"] % 10;
            }
        }
        return $string;
    }

    // Converting from values for database to values with which you can work
    private function stringToField($string, $width){
        $x = -1;
        $y = 0;

        $field = [];
        foreach (str_split($string) as $pos => $value){
            $value = intval($value);
            $var = $pos%2;
            if($var == 0){
                $x++;
                if(($y%2 == 0) ? ($x >= $width) : ($x >= ($width-1))){
                    $x = 0;
                    $y++;
                }
            }

            switch ($var){
                case 0:
                    if(!array_key_exists($y, $field)){
                        $field[$y] = [];
                    }

                    $field[$y][$x] = array(
                        "color" => $this->colors[$value],
                        "playerId" => null,
                    );
                    break;
                case 1:
                    $field[$y][$x]["playerId"] = $value;
                    break;
                default:
                    break;
            }
        }

        return $field;
    }

    // Converting from values with which you can work to values needed to satisfy the olympiad rules
    private function fieldToOutput($field){
        $outputField = [];
        foreach ($field as $y){
            foreach ($y as $x){
                array_push($outputField, $x);
            }
        }
        return $outputField;
    }

    // Doing some action (like assign cell to player) like filling in MS Paint
    private function recursiveWork($field, $x, $y, $worker, $data = array()){
        $checked = [];
        $toCheck = [];
        array_push($toCheck, [$x, $y]);

        while($work = array_pop($toCheck)){
            $x = $work[0];
            $y = $work[1];

            if(!(array_key_exists($y, $checked) && array_key_exists($x, $checked[$y]))) {
                if (array_key_exists($y, $field) && array_key_exists($x, $field[$y])) {
                    $newData = $this->$worker($field, $x, $y, $data);
                    $field = $newData[0];
                    $closedContinue = $newData[1];

                    if(!array_key_exists($y, $checked)) $checked[$y] = [];
                    $checked[$y][$x] = true;

                    if($closedContinue) {
                        if ($y % 2 != 0) {
                            array_push($toCheck, [$x    , $y - 1]);
                            array_push($toCheck, [$x + 1, $y - 1]);
                            array_push($toCheck, [$x    , $y + 1]);
                            array_push($toCheck, [$x + 1, $y + 1]);
                        } else {
                            array_push($toCheck, [$x    , $y - 1]);
                            array_push($toCheck, [$x - 1, $y - 1]);
                            array_push($toCheck, [$x    , $y + 1]);
                            array_push($toCheck, [$x - 1, $y + 1]);
                        }
                    }
                }
            }
        }

        return $field;
    }

    private function assingColorsPlayerFunction($field, $x, $y, $data){
        $closedContinue = false;
        if($field[$y][$x]["color"] == $data["color"]){
            if($field[$y][$x]["playerId"] == 0) $field[$y][$x]["playerId"] = $data["player"];
            $closedContinue = true;
        }
        return [$field, $closedContinue];
    }

    private function assingColorsPlayer($field, $x, $y, $player){
        $color = $field[$y][$x]["color"];
        $field = $this->recursiveWork($field, $x, $y, "assingColorsPlayerFunction", array(
            "color" => $color,
            "player" => $player
        ));
        return $field;
    }

    private function fillFunction($field, $x, $y, $data){
        $closedContinue = false;
        if($field[$y][$x]["color"] == $data["old_color"]){
            $field[$y][$x]["color"] = $data["new_color"];
            $closedContinue = true;
        }
        return [$field, $closedContinue];
    }

    private function fill($field, $x, $y, $new_color){
        $old_color = $field[$y][$x]["color"];
        $field = $this->recursiveWork($field, $x, $y, "fillFunction", array(
            "old_color" => $old_color,
            "new_color" => $new_color
        ));
        return $field;
    }

    private function getWinner($field){
        $linearField = $this->fieldToOutput($field);
        $all = count($linearField);
        $p1 = 0;
        $p2 = 0;
        foreach ($this->fieldToOutput($field) as $cell){
            switch($cell["playerId"]){
                case 1:
                    $p1++;
                    break;
                case 2:
                    $p2++;
                    break;
                default:
                    break;
            }
        }
        if(($p1 / $all) > 0.5) return 1;
        if(($p2 / $all) > 0.5) return 2;
        return 0;
    }

    public function createGame(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'width' => ['required', 'integer', 'between:5,99', new is_odd],
            'height' => ['required', 'integer', 'between:5,99', new is_odd]
        ]);

        if ($validator->fails()) {
            return response(null, 400);
        }

        $width = $request->input("width");
        $height = $request->input("height");
        $uuid = Str::uuid();

        $game = new Game;
        $game->uuid = $uuid;
        $game->width = $width;
        $game->height = $height;

        // Generate random field
        $field = [];
        for($y = 0; $y < ($height * 2 - 1); $y++){
            $field[$y] = [];
            for($x = 0; $x < ($y%2 == 0 ? $width : ($width - 1)); $x++){
                $field[$y][$x] = array(
                    "color" => $this->colors[rand(0, count($this->colors) - 1)],
                    "playerId" => 0
                );
            }
        }

        // Fill and assign first colors
        $field = $this->fill($field, 0, $height*2-2, $this->colors[0]);
        $field = $this->assingColorsPlayer($field, 0, $height*2-2, 1);

        $field = $this->fill($field, $width-1, 0, $this->colors[1]);
        $field = $this->assingColorsPlayer($field, $width-1, 0, 2);

        $game->data = $this->fieldToString($field);

        $game->save();

        return response()->json(['game' => $game->uuid], 201);
    }

    public function getGame($gameID){
        if(!Str::isUuid($gameID)){
            return response(null, 400);
        }

        $game = Game::where('uuid', $gameID)->first();
        if(!$game){
            return response(null, 404);
        }

        $field = $this->stringToField($game->data, $game->width);
        $outputField = $this->fieldToOutput($field);

        $out = array(
            "id" => $game->uuid,
            "field" => array(
                "width" => $game->width,
                "height" => $game->height,
                "cells" => $outputField,
                "players" => [
                    array(
                        "color" => $field[$game->height*2-2][0]["color"],
                        "id" => 1
                    ),
                    array(
                        "color" => $field[0][$game->width-1]["color"],
                        "id" => 2
                    )
                ]
            ),
            "currentPlayerId" => $game->current_player,
            "winnerPlayerId" => $game->winner
        );

        return response()->json($out);
    }

    public function stepGame($gameID, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'playerId' => ['required', 'integer', 'between:1,2'],
            'color' => ['required']
        ]);

        if ($validator->fails()) {
            return response(null, 400);
        }

        $playerId = $request->input("playerId");
        $color = $request->input("color");

        if(!in_array($color, $this->colors)){
            return response(null, 400);
        }

        if(!Str::isUuid($gameID)){
            return response(null, 400);
        }

        $game = Game::where('uuid', $gameID)->first();
        if(!$game){
            return response(null, 404);
        }

        if($game->current_player != $playerId){
            return response(null, 403);
        }

        if($game->winner != 0){
            return response(null, 403);
        }

        $field = $this->stringToField($game->data, $game->width);
        $lockedColors = [$field[$game->height*2-2][0]["color"], $field[0][$game->width-1]["color"]];
        if(in_array($color, $lockedColors)){
            return response(null, 409);
        }

        if($playerId == 1){
            $field = $this->fill($field, 0, $game->height*2-2, $color);
            $field = $this->assingColorsPlayer($field, 0, $game->height*2-2, 1);
        }else{
            $field = $this->fill($field, $game->width-1, 0, $color);
            $field = $this->assingColorsPlayer($field, $game->width-1, 0, 2);
        }

        $game->data = $this->fieldToString($field);
        $game->winner = $this->getWinner($field);
        $game->current_player = ($game->winner == 0) ? ($playerId%2 + 1) : 0;
        $game->save();

        return response(null, 201);
    }
}
