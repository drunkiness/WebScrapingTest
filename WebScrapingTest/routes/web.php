<?php

use App\Http\Controllers\ScrapeController;
use Illuminate\Support\Facades\Route;


/*Route::get('/scrape', function () {
  return 'scrape function';
}); */


/*Route::get('/', function () {
  return view('welcome');
});*/

//Route::get('/', 'ScrapeController@scraper');



Route::get('/scraper', [ScrapeController::class, 'scraper']);
Route::get('/scrape', [ScrapeController::class, 'scrape']);
