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
            $details['img'] = $img = $node->filter('.gamelist_image.back_black.shadow_box > a > img')->extract(array('src'))[0];
            $node->filter('.gamelist_details.shadow_box.back_white')->each(function ($sub_node) use (&$details){
                $details['title'] = $sub_node->filter('h3 > a')->text();
                $sub_node->filter('.back_white')->each(function ($sub_sub_node) use (&$details) {
                    //dd($sub_sub_node->text());
                    //$details[$sub_sub_node->eq(0)->text()] = $sub_sub_node->eq(1)->text();
                });
            });
        });
        dd($details);
        //return $count;


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


