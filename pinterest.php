<?php
require_once('bootstrap.php');


use Symfony\Component\DomCrawler\Crawler;

$fileUrlsToCrawl = "./data/pinterest-halloweendecorations27092013.csv";
$jsonFile = "./data/pinterest-halloweendecorations27092013.json";
$downloadDir = "./data/pinterest-halloweendecorations27092013/";
@mkdir($downloadDir);

$fp = fopen($fileUrlsToCrawl, 'r');
$fpDestination = fopen($jsonFile, 'a+');

while (!feof($fp)) {
	$url = trim( fgets($fp) );
	echo $url.PHP_EOL;
	if ($url != "") {
		try {
			$array = getMeta($url, $downloadDir);
		} catch(\Exception $e) {
			echo $e->getMessage().PHP_EOL;
		}
		
	}	
	$jsonArray = json_encode($array). PHP_EOL;
	fwrite($fpDestination, $jsonArray);
}
fclose($fp);
fclose($fpDestination);

function getMeta($url, $downloadDir) {

	$html = getHeadHtml($url);


	if ($html) {
		$crawler = new Crawler($html);

		try {
			$Pagetitle =trim( $crawler->filterXpath('//title')->text());
		} catch(\Exception $e) {
			echo $e->getMessage().PHP_EOL;
		}



		try {
			$pinner = $crawler->filter('meta[property="pinterestapp:pinner"]')->attr('content');
		} catch(\Exception $e) {
			echo $e->getMessage().PHP_EOL;
		}

		try {
			$description = $crawler->filter('meta[property="og:description"]')->attr('content');
		} catch(\Exception $e) {
			echo $e->getMessage().PHP_EOL;
		}

		try {
			$seeAlso = $crawler->filter('meta[property="og:see_also"]')->attr('content');
		} catch(\Exception $e) {
			echo $e->getMessage().PHP_EOL;
		}

		try {
			$repins = $crawler->filter('meta[property="pinterestapp:repins"]')->attr('content');
		} catch(\Exception $e) {
			echo $e->getMessage().PHP_EOL;
		}

		try {
			$title = $crawler->filter('meta[property="og:title"]')->attr('content');
		} catch(\Exception $e) {
			echo $e->getMessage().PHP_EOL;
		}
		
		try {
			$likes = $crawler->filter('meta[property="pinterestapp:likes"]')->attr('content');
		} catch(\Exception $e) {
			echo $e->getMessage().PHP_EOL;
		}

		try {
			$pinboard = $crawler->filter('meta[property="pinterestapp:pinboard"]')->attr('content');
		} catch(\Exception $e) {
			echo $e->getMessage().PHP_EOL;
		}

		try {
			$source = $crawler->filter('meta[property="pinterestapp:source"]')->attr('content');
		} catch(\Exception $e) {
			echo $e->getMessage().PHP_EOL;
		}		

		#craw source pic to get more data about the picture
		$nodeValues = array();
		/*
		$nodeValues = array();
		if ($source != "") {
			$sourceHtml = getHeadHtml($source);
			$crawlerSource = new Crawler($sourceHtml);
			$nodeValues = $crawlerSource->filter('title,h1,h2,h3,h4,h5,p')->each(function (Crawler $node, $i) {
				return trim($node->text());
			});
			
		} 
		*/
		try {
			$image = $crawler->filter('meta[property="og:image"]')->attr('content');

			if ($image && ($repins > 10 || $likes > 10) ) {
				echo "Downloading images Repins:[".$repins."] Likes[".$likes."]";
				$imageBin = getHeadHtml($image);
				$imagePath = basename( parse_url($image, PHP_URL_PATH) );
				$imageExtension = pathinfo($imagePath, PATHINFO_EXTENSION);
				
				$pinPath = str_replace("/", null, parse_url($url, PHP_URL_PATH));

				$imageName = $pinPath . "." .$imageExtension;

				file_put_contents($downloadDir . $imageName, $imageBin);
			} else {
				$imageName = $image;
			}
			
		} catch(\Exception $e) {
			echo $e->getMessage().PHP_EOL;
		}
		#scrap the pinner
		$pinner_data = array();
		if ($pinner) {
			$sourceHtml = getHeadHtml($pinner);

			$crawlerSource = new Crawler($sourceHtml);
			
			$pinner_data['followers'] = $crawlerSource->filter('meta[property="pinterestapp:followers"]')->attr('content');
			$pinner_data['following'] = $crawlerSource->filter('meta[property="pinterestapp:following"]')->attr('content');
			$pinner_data['boards'] = $crawlerSource->filter('meta[property="pinterestapp:boards"]')->attr('content');
			$pinner_data['pins'] = $crawlerSource->filter('meta[property="pinterestapp:pins"]')->attr('content');

			$pinner_data['title'] = $crawlerSource->filter('meta[property="og:title"]')->attr('content');
			$pinner_data['description'] = $crawlerSource->filter('meta[property="og:description"]')->attr('content');
		}
		

		

		return array(
			'page_title' => $Pagetitle,
			'image' => $image,
			'local_image' => $imageName,
			'url' => $url,
			'pinner' => $pinner,
			'pinner_data' => $pinner_data,
			'description' => $description,
			'see_also' => $seeAlso,
			'repins' => $repins,
			'title' => $title,
			'likes' => $likes,
			'pinboard' => $pinboard,
			'source' => $source,
			'source_text' => $nodeValues,
			);
	}
}

function getHeadHtml($url) {
	echo "Downloading [".$url."]".PHP_EOL;
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $url); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	$head = curl_exec($ch); 
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
	curl_close($ch);
	if(!$head) { 
		return FALSE; 
	} 

	if($httpCode < 400) { 
		echo "OK".PHP_EOL;
		return $head; 
	} 
	return false;
}

/*
        <title>Selita Ebanks Short Hair Style | Loving the hair I&#39;m In !</title>

        <meta property="og:image" name="og:image" content="http://media-cache-ak0.pinimg.com/736x/02/be/3f/02be3fe5d4ee93a3cc6b88001c41cdc4.jpg" data-app>
        <meta property="og:url" name="og:url" content="http://www.pinterest.com/pin/340936634262084745/" data-app>
        <meta property="og:type" name="og:type" content="pinterestapp:pin" data-app>
        <meta property="pinterestapp:pinner" name="pinterestapp:pinner" content="http://www.pinterest.com/keirrn410/" data-app>
        <meta property="description" name="description" content="Keir Reid-Young is using Pinterest, an online pinboard to collect and share what inspires you." data-app>
        <meta property="og:see_also" name="og:see_also" content="http://www.haircutshairstyles.com/selita-ebanks-hairstyles.shtml" data-app>
        <meta property="pinterestapp:repins" name="pinterestapp:repins" content="2" data-app>
        <meta property="og:description" name="og:description" content="Selita Ebanks Short Hair Style" data-app>
        <meta property="og:title" name="og:title" content="Loving the hair I&#39;m In !" data-app>
        <meta property="pinterestapp:likes" name="pinterestapp:likes" content="0" data-app>
        <meta property="pinterestapp:pinboard" name="pinterestapp:pinboard" content="http://www.pinterest.com/keirrn410/loving-the-hair-im-in/" data-app>
        <meta property="pinterestapp:source" name="pinterestapp:source" content="http://www.haircutshairstyles.com/selita-ebanks-hairstyles.shtml" data-app>

        
 */