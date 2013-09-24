<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */

namespace VDB\Spider\PersistenceHandler;

use Symfony\Component\Finder\Finder;
use VDB\Spider\Resource;
use Guzzle\Http\Client;
use Guzzle\Http\Url;
use Guzzle\Http\StaticClient;

class VideosChistososJson implements PersistenceHandler, \Iterator
{
    /**
     * @var string the path where all spider results should be persisted.
     *             The results will be grouped in a directory by spider ID.
     */
    private $path = '';

    private $postNum = 0;

    private $spiderId = '';

    private $totalSizePersisted = 0;

    /** @var \Iterator */
    private $iterator;

    /**
     * @param string $path the path where all spider results should be persisted.
     *        The results will be grouped in a directory by spider ID.
     */
    public function __construct($path)
    {
      $this->path = $path;
    }

    public function setSpiderId($spiderId)
    {
      $this->spiderId = $spiderId;

        // create the path

      @mkdir($this->getResultPath(), 0700, true);
      @mkdir($this->getResultPath()."_posts", 0700, true);
      @mkdir($this->getResultPath()."images", 0700, true);
      
    }

    private function getResultPath()
    {
      return $this->path . DIRECTORY_SEPARATOR . $this->spiderId . DIRECTORY_SEPARATOR;
    }

    public function persist(Resource $resource)
    {
      $this->saveJsonInfo($resource);
      return;
      $fileName = urlencode($resource->getUri()->toString());
      $file = new \SplFileObject($this->getResultPath() . $fileName, 'w');
      $rawResponse = $resource->getResponse()->__toString();
      $this->totalSizePersisted += $file->fwrite($rawResponse);
    }
    private function getTags($crawler) {
      $tags = array();
      foreach ($crawler as $node) {
        $tags[] = json_encode($node);
      }
      return $tags;
    }
    private function saveJsonInfo(Resource $resource) {


      $fileName = $this->getResultPath() . "results.json";
      $data = array();
      $categories = array();
      try {
        $title = trim( $resource->getCrawler()->filterXpath('//title')->text());
        $descripcion = trim( $resource->getCrawler()->filterXpath("//*[@id=\"izquierda\"]/div//article/div/p")->text() );
        $tags = $resource->getCrawler()
        ->filterXpath("//*[@id=\"izquierda\"]/div//article/div/div//ul/li/a")
        ->each(function ($node, $i) {
          return $node->text();
        });
        $youtube = $resource->getCrawler()
        ->filterXpath("//*[@id=\"izquierda\"]/div//iframe")
        ->attr('src');

      } catch(\Exception $e) {

      }

      if ( $title != "" && $descripcion != "" && $youtube != "") {
        echo "DOCUMENT: ".$resource->getUri()->toString()."\n";

        $documentHost = parse_url($documentUrl, PHP_URL_HOST);

         $this->postNum++;
         $slug = date("Y-m-d")."-".base_convert( mt_rand(0,100), 10, 32)."-".preg_replace('/[^A-Za-z0-9]/', '-', strtolower($title) );
         $slug = preg_replace("/-+/", "-", $slug); 
         $slug = substr($slug, 0, 50);
         $slug = rtrim($slug, '-');
         $postNum = str_pad($this->postNum, 6, "0", STR_PAD_LEFT);
         $short = base_convert(microtime(), 10, 32);

         $data = array(
          'title' => $title,
          'youtube_url' =>  $youtube,
          'categories' => $tags,
          'postslug' => $slug,
          );


         file_put_contents($fileName, json_encode($data)."\n", FILE_APPEND | LOCK_EX);     

     }

   }
   private function getIterator()
   {
    if (!$this->iterator instanceof \Iterator) {
      $finder = Finder::create()->files()->in($this->getResultPath());
      $this->iterator = $finder->getIterator();
    }
    return $this->iterator;
  }

    /**
     * @return Resource
     */
    public function current()
    {
      return $this->getIterator()->current()->getContents();
    }

    /**
     * @return void
     */
    public function next()
    {
      $this->getIterator()->next();
    }

    /**
     * @return int
     */
    public function key()
    {
      return $this->getIterator()->key();
    }

    /**
     * @return boolean
     */
    public function valid()
    {
      return $this->getIterator()->valid();
    }

    /**
     * @return void
     */
    public function rewind()
    {
      $this->getIterator()->rewind();
    }
  }
