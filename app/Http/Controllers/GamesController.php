<?php namespace App\Http\Controllers;
/**
 * Created by PhpStorm.
 * User: Michael
 * Date: 16/04/15
 * Time: 02:00
 */

use Goutte\Client;
use Cache;
use Request;

class GamesController extends ApiController {


    public function index(){
        $client = new Client();//TODO: Turn this into a facade
        //http://howlongtobeat.com/search_main.php?page=1
        //return 'index';
        $page = (Request::input('page') ? Request::input('page') : 1 );

        /*queryString:zelda
        t:games
        sorthead:popular
        sortd:Normal Order
        plat:*/

        $crawler = $client->request('POST', 'http://howlongtobeat.com/search_main.php?'.http_build_query([ //TODO: use third parameter of function
            'page' => $page
        ]));

        $count = $crawler->filter('.search_loading.shadow_box.back_blue')->first()->text();
        $count = preg_replace('/\D/', '', $count);
        $details = [];

        $crawler->filter('.gamelist_list > li')->each(function ($node) use (&$details){
            $game['id'] = preg_replace('/\D/', '', $node->filter('.gamelist_image.back_black.shadow_box > a')->extract(array('href')))[0];
            $game['img'] = $img = $node->filter('.gamelist_image.back_black.shadow_box > a > img')->extract(array('src'))[0];
            $game['title'] = $node->filter('.gamelist_details.shadow_box.back_white h3 > a')->text();
            $game['times'] = [];
            $node->filter('.gamelist_details.shadow_box.back_white > div')->children()->each(function ($sub_node) use (&$game){
                $game['times'][$sub_node->children()->eq(0)->text()] = $sub_node->children()->eq(1)->text();
            });
            $details[] = $game;
        });
        return $details;


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
            $times = [];
            $crawler->filter('.gprofile_times > li')->each(function ($node) use (&$times){
                $times[$node->filter('h5')->text()] = $node->filter('div')->text();
            });

            return $game =  [
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


