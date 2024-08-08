<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Nette\Utils\Arrays;
use KubAT\PhpSimple\HtmlDomParser;

class ScrapeController extends Controller {

  public $productList = [];

  public function scrape(Request $request) {
    $url = $request->get('url');
    //dd($url);

    echo "extracting data from $url";

    $parsedContent = $this->getParsedContent($url); // parse the html document into nodes
    //dd($parsedContent);

    $pageInfo = (object)$this->getPageInfo($parsedContent); // get page count & resource path
    //dd($pageInfo);

    echo '<div><i class="fa fa-refresh fa-spin" aria-hidden="true"></i></div>';

    for ($i=1; $i <= $pageInfo->numOfPages; $i++) {
      $endPoint = "$pageInfo->resourcePath/$i";
      //dd($endPoint);

      $parsedContent = $this->getParsedContent($endPoint);
      //dd($parsedContent);

      $products = $parsedContent->find(".product");
      //dd($products);

      /*********** start scraping here ***********/

      foreach($products as $product) {

        $productSku = $product->find("a[data-product_sku]", 0);
        $productId = $product->find("a[data-product_id]", 0);
        $productName = $product->find(".woocommerce-loop-product__title", 0);
        $productPrice = $product->find("span.price", 0);
        $productUrl = $product->find("a[href]", 0);


        $productInfo = [
          "SKU" => $productSku->getAttribute("data-product_sku"),
          "Product ID" => $productId->getAttribute("data-product_id"),
          "Name" => $productName->plaintext,
          "Price" => $productPrice->plaintext,
          "URL" => $productUrl->getAttribute("href")
        ];
        //dd($productInfo);

        $productList[] = $productInfo;
      }
      /*********** end scraping here ***********/

    }

    //dd($productList);


    $csvFilePath = "products.tsv"; // file in tsv format

    $file = fopen($csvFilePath, "w"); // open the file for writing

    fputcsv($file, array_keys($productList[0])); // write the header into the file

    foreach ($productList as $product) { // persist the products into the file
      fputcsv($file, $product);
    }

    fclose($file); // close the file

    echo "TSV file created successfully: $csvFilePath";

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



  public function getPageInfo($htmlContent) {

    $searchUrlTerm = "/class=\"page-numbers\"\s+href=\".*?\"/i"; // find all instance of '"class="page-numbers" href="https://www.scrapingcourse.com/ecommerce/page/12/"'
    preg_match_all($searchUrlTerm, $htmlContent, $matches); // store it in $matches
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


  public function scraper() {
    echo "cnbkjfdfjkghkdfghfdjk";
  }




}
