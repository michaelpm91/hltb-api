<?php namespace App\Http\Controllers;
/**
 * Created by PhpStorm.
 * User: Michael
 * Date: 16/04/15
 * Time: 02:00
 */

use Goutte\Client;

class GamesController extends ApiController {


    public function index(){
        return 'index';
    }

    public function show($id){
        //http://howlongtobeat.com/game_main.php?id=21262
        $client = new Client();//TODO: Turn this into a facade
        $crawler = $client->request('GET', 'http://howlongtobeat.com/game_main.php?/' . $id);
        dd($crawler);

    }
}


