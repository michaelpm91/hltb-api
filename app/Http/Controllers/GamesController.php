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
        $page = (Request::input('page') ? Request::input('page') : 1 );

        $query = Request::input('query');
        //$type = Request::input('t');
        $sort_by = Request::input('sorthead');
        $order = Request::input('sortd');
        $platform = Request::input('plat');
        $detail = Request::input('detail');

        $minutes = 10080;//1 week
        $cache_id = 'game_search_query'.($query ? '_'.$query : '').($sort_by ? '_'.$sort_by : '').($order ? '_'.$order : '').($platform ? '_'.$platform : '').($detail ? '_'.$detail : '');
        $details = Cache::remember($cache_id, $minutes, function() use ($client, $page, $query, $sort_by, $order, $platform, $detail) {
            $crawler = $client->request('POST', 'http://howlongtobeat.com/search_main.php?' . http_build_query([ //TODO: use third parameter of function
                    'page' => $page
                ]), [
                    'queryString' => $query,
                    //'t' => '',
                    'sorthead' => $sort_by,
                    'sortd' => $order,
                    'plat' => $platform,
                    'detail' => ''
                ]);

            $count = $crawler->filter('.search_loading')->first()->text();
            $count = preg_replace('/\D/', '', $count);
            if (!$count) return $this->respondNotFound('No Games Found');
            $details['results'] = $count;
            $details['page'] = $page;

            $crawler->filter('.gamelist_list > li')->each(function ($node) use (&$details) {
                $game['id'] = preg_replace('/\D/', '', $node->filter('.gamelist_image.back_black.shadow_box > a')->extract(array('href')))[0];
                $game['img'] = $img = $node->filter('.gamelist_image.back_black.shadow_box > a > img')->extract(array('src'))[0];
                $game['title'] = $node->filter('.gamelist_details > h3 > a')->text();
                $game['times'] = [];
                $node->filter('.gamelist_details > div')->children()->each(function ($sub_node) use (&$game) {
                    $game['times'][$sub_node->children()->eq(0)->text()] = $sub_node->children()->eq(1)->text();
                });
                //TODO: Check if extra details were request
                $details['games'][] = $game;
            });
            return $details;
        });
        return $this->respond($details);


    }

    public function show($id){
        $client = new Client();//TODO: Turn this into a facade

        $minutes = 10080;//1 week
        $cache_id = 'game_'.$id;
        $game = Cache::remember($cache_id, $minutes, function() use ($id, $client) {
            $crawler = $client->request('GET', 'http://howlongtobeat.com/game.php?'.http_build_query([ //TODO: use third parameter of function
                'id' => $id
            ]));
            $title = trim($crawler->filter('.gprofile_header')->first()->text());

            if(!$title) return $this->respondNotFound('This ID Does Not Exist.'); //if title is blank assume the page doesn't exist

            $summary = trim(strip_tags($crawler->filter('.gprofile_summary')->first()->text()));


            $crawler = $client->request('GET', 'http://howlongtobeat.com/game_main.php?'.http_build_query([ //TODO: use third parameter of function
                'id' => $id
            ]));

            $img = $crawler->filter('.gprofile_image')->first()->extract(array('src'))[0];
            $times = [];
            $crawler->filter('.gprofile_times > li')->each(function ($node) use (&$times){
                $times[$node->filter('h5')->text()] = $node->filter('div')->text();
            });
            //TODO: Scrape Game Details
            //TODO: Scrape Platform Details
            //TODO: Scrape Play type (ie. single/multi/co-op) statistics

            return $game =  [
                'game' => [
                    'title' => $title,
                    'img' => $img,
                    'times' => $times,
                    'summary' => $summary,
                    'details' => '',
                    'platform statistics' => '',

                ]
            ];
        });
        if(!$game) return $this->respondNotFound('Game Not Found');
        return $this->respond($game);

    }
}


