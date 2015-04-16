<?php namespace App\Http\Controllers;
/**
 * Created by PhpStorm.
 * User: Michael
 * Date: 16/04/15
 * Time: 02:00
 */

use Goutte\Client;
use Cache;

class GamesController extends ApiController {


    public function index(){
        return 'index';
    }

    public function show($id){
        $client = new Client();//TODO: Turn this into a facade
        //http://howlongtobeat.com/game_main.php?id=21262
        //http://howlongtobeat.com/game.php?id=21262

        $minutes = 10080;//1 week
        $game = Cache::remember('game_'.$id, $minutes, function() use ($id, $client) {
            $crawler = $client->request('GET', 'http://howlongtobeat.com/game.php?'.http_build_query([ //TODO: use third parameter of function
                    'id' => $id
                ]));
            $title = trim($crawler->filter('.gprofile_header')->first()->text());

            if(!$title) return; //if title is blank assume the page doesn't exist


            $crawler = $client->request('GET', 'http://howlongtobeat.com/game_main.php?'.http_build_query([ //TODO: use third parameter of function
                    'id' => $id
                ]));

            $img = $crawler->filter('.gprofile_image')->first()->extract(array('src'))[0];

            $times = [
                'Main Story' => $crawler->filter('.gprofile_times > li > div')->eq(0)->text(),
                'Main Story + Extras' => $crawler->filter('.gprofile_times > li > div')->eq(1)->text(),
                'Completionist' => $crawler->filter('.gprofile_times > li > div')->eq(2)->text(),
                'Combined' => $crawler->filter('.gprofile_times > li > div')->eq(3)->text()
            ];

            return [
                'game' => [
                    'title' => $title,
                    'img' => $img,
                    'times' => $times
                ]
            ];
        });
        if(!$game) return $this->respondNotFound('Game Not Found');

        return $this->respond($game);

    }
}


