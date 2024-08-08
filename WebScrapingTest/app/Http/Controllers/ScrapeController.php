<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Nette\Utils\Arrays;
use KubAT\PhpSimple\HtmlDomParser;

class ScrapeController extends Controller {

  public function scraper() {
    echo "cnbkjfdfjkghkdfghfdjk";
  }


  public function scrape(Request $request) {
    $url = $request->get('url');
    //dd($url);

    $parsedContent = $this->getParsedContent($url);
    //dd($parsedContent);

    $pageInfo = (object)$this->getPageInfo($parsedContent);
    //dd($pageInfo);

    $productList = array();

    for ($i=1; $i <= $pageInfo->numOfPages; $i++) {
      $endPoint = "$pageInfo->resourcePath/$i";
      //dd($endPoint);

      $parsedContent = $this->getParsedContent($endPoint);
      //dd($parsedContent);

      $products = $parsedContent->find(".product");
      //dd($products);

      //$productList = array();

      foreach($products as $product) {
        $productName = $product->find(".woocommerce-loop-product__title", 0);
        $productPrice = $product->find("span.price", 0);

        $productInfo = [
          "Name" => $productName->plaintext,
          "Price" => $productPrice->plaintext
        ];
        //dd($productInfo);

        $productList[] = $productInfo;
      }
      /*********** end scraping here ***********/


      dd($productList);



    }

    // define the path to the CSV file
    $csvFilePath = "products.csv";

    // open the CSV file for writing
    $file = fopen($csvFilePath, "w");

    // write the header row to the CSV file
    fputcsv($file, array_keys($productList[0]));

    // write each product's data to the CSV file
    foreach ($productList as $product) {
      fputcsv($file, $product);
    }

    fclose($file); // close the CSV file
    echo "CSV file created successfully: $csvFilePath";

    $parsedContent->clear(); // clean up resources



  }




  public function getParsedContent($url) {
    $curl = curl_init(); // initialize a cURL session

    curl_setopt_array($curl, array(
      CURLOPT_URL => "$url",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_SSL_VERIFYPEER => false
    ));

    $htmlContent = curl_exec($curl); // execute the cURL session


    if ($htmlContent === false) { // check if response is 200
      $error = curl_error($curl);
      echo "cURL error: " . $error;
      exit;
    }

    curl_close($curl); // close cURL session

    return HtmlDomParser::str_get_html($htmlContent); // parse the html document into nodes
  }




  function getPageInfo($htmlContent) {
    //$searchPageTerm = "/class=\"page-numbers\"/i";
    //$numOfPages = preg_match_all($searchPageTerm, $htmlContent);

    $searchUrlTerm = "/class=\"page-numbers\"\s+href=\".*?\"/i"; // find all instance of '"class="page-numbers" href="https://www.scrapingcourse.com/ecommerce/page/12/"'
    preg_match_all($searchUrlTerm, $htmlContent, $matches); // store it in $matched
    $matches = $matches[0]; // convert $matches into array from associative

    $rscPathPattern = "/https:.*?page\/\d+/i"; // find all instance of 'https://www.scrapingcourse.com/ecommerce/page/<n>/'
    preg_match_all($rscPathPattern, end($matches), $resourcePath);
    $resourcePath = $resourcePath[0];

    //print_r($matches);
    //print_r(end($matches));
    //exit;

    return [
      "numOfPages" => basename($resourcePath[0]),
      "resourcePath" => dirname($resourcePath[0])
    ];

  }







}
