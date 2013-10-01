<?php
require_once('bootstrap.php');
require 'lib/Readability.inc.php';
use Symfony\Component\DomCrawler\Crawler;



if (!isset($argv[1])) {
	die("Use php5 pinterest.php file" .PHP_EOL);
}

$fileSource = $argv[1];


$fileUrlsToCrawl = "./data/".$fileSource;
echo $fileUrlsToCrawl.PHP_EOL;

$jsonFile = "./data/".$fileSource.".json";
$downloadDir = "./data/". str_replace(".", null, $fileSource)."/";

@mkdir($downloadDir);

$fp = fopen($fileUrlsToCrawl, 'r');
$fpDestination = fopen($jsonFile, 'a+');

while (!feof($fp)) {
	$url = trim( fgets($fp) );
	echo $url.PHP_EOL;
	if ($url != "") {
		try {
			$array = getMeta($url, $downloadDir);
			var_dump($array);
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
		
		$nodeValues = array();
		$sourceHtml = "";
		$ReadabilityData = "";
		if ($source != "" && ($repins > 10 || $likes > 10) ) {
			$sourceHtml = getHeadHtml($source);
			$crawlerSource = new Crawler($sourceHtml);
			$nodeValues = $crawlerSource->filter('title,h1,h2,h3,h4,h5')->each(function (Crawler $node, $i) {
				return trim($node->text());
			});			
			$Readability     = new Readability($sourceHtml); // default charset is utf-8
			$ReadabilityData = $Readability->getContent();
		} 
		
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
		$sourceHtml = "";
		if ($pinner) {

			$sourceHtml = getHeadHtml($pinner);

			$crawlerSource = new Crawler($sourceHtml);
			try {
				$pinner_data['followers'] = $crawlerSource->filter('meta[property="pinterestapp:followers"]')->attr('content');

			}  catch(\Exception $e) {
				echo $e->getMessage().PHP_EOL;
			}
			try {
				$pinner_data['following'] = $crawlerSource->filter('meta[property="pinterestapp:following"]')->attr('content');

			}  catch(\Exception $e) {
				echo $e->getMessage().PHP_EOL;
			}
			try {
				$pinner_data['boards'] = $crawlerSource->filter('meta[property="pinterestapp:boards"]')->attr('content');

			}  catch(\Exception $e) {
				echo $e->getMessage().PHP_EOL;
			}
			try {
				$pinner_data['pins'] = $crawlerSource->filter('meta[property="pinterestapp:pins"]')->attr('content');

			}  catch(\Exception $e) {
				echo $e->getMessage().PHP_EOL;
			}
			try {
				$pinner_data['title'] = $crawlerSource->filter('meta[property="og:title"]')->attr('content');

			}  catch(\Exception $e) {
				echo $e->getMessage().PHP_EOL;
			}
			try {
				$pinner_data['description'] = $crawlerSource->filter('meta[property="og:description"]')->attr('content');
			}  catch(\Exception $e) {
				echo $e->getMessage().PHP_EOL;
			}
			

			$pinner_data['multiBoards'] = array();
			$multiBoards = array();
			try {
				$multiboardsCrawler = $crawlerSource->filter('.item')->each(function (Crawler $node, $i) use (&$multiBoards) {
					$html = $node->html();
					if (strstr($html, "collaborativeIcon")) {
						
						try {
		
							$link = $node->filter('.boardLinkWrapper')->attr('href');
							$multiBoards[] = 'http://www.pinterest.com'.$link;
						}  catch(\Exception $e) {

						}
						

					}
				});
				$pinner_data['multiBoards'] = $multiBoards;
				
			} catch(\Exception $e) {
	
			}


		}
		if ($pinboard) {
/*
            <meta property="og:url" name="og:url" content="http://www.pinterest.com/nsfdf/halloween/" data-app>
            <meta property="pinterestapp:pinner" name="pinterestapp:pinner" content="http://www.pinterest.com/nsfdf/" data-app>
            <meta property="description" name="description" content="Christy Tusing- Borgeld is using Pinterest, an online pinboard to collect and share what inspires you." data-app>
            <meta property="pinterestapp:pins" name="pinterestapp:pins" content="276" data-app>
            <meta property="og:type" name="og:type" content="pinterestapp:pinboard" data-app>
            <meta property="og:description" name="og:description" content="" data-app>
            <meta property="pinterestapp:category" name="pinterestapp:category" content="holidays_events" data-app>
            <meta property="followers" name="followers" content="902" data-app>
            <meta property="og:title" name="og:title" content="Halloween" data-app>
 */
            $sourceHtml = getHeadHtml($pinboard);
            $crawlerSource = new Crawler($sourceHtml);
            $board_data = array();
            try {
            	$board_data['followers'] = $crawlerSource->filter('meta[property="followers"]')->attr('content');
            	var_dump($board_data);
            } catch(\Exception $e) {
            	echo $e->getMessage().PHP_EOL;
            }
            try {
            	$board_data['owner'] = $crawlerSource->filter('meta[property="pinterestapp:pinner"]')->attr('content');
            	var_dump($board_data);
            } catch(\Exception $e) {
            	echo $e->getMessage().PHP_EOL;
            }
            try {
            	$board_data['pins'] = $crawlerSource->filter('meta[property="pinterestapp:pins"]')->attr('content');

            } catch(\Exception $e) {
            	echo $e->getMessage().PHP_EOL;
            }
            try {
            	$board_data['category'] = $crawlerSource->filter('meta[property="pinterestapp:category"]')->attr('content');

            } catch(\Exception $e) {
            	echo $e->getMessage().PHP_EOL;
            }
            try {
            	$board_data['title'] = $crawlerSource->filter('meta[property="og:title"]')->attr('content');

            } catch(\Exception $e) {
            	echo $e->getMessage().PHP_EOL;
            }
            try {
            	$board_data['description'] = $crawlerSource->filter('meta[property="og:description"]')->attr('content');
            } catch(\Exception $e) {
            	echo $e->getMessage().PHP_EOL;
            }

            try {
            	$board_data['num_pinners'] = $crawlerSource->filter('.moreUserCollaborators span')->text();
            	$board_data['num_pinners']  = str_replace("+", null, $board_data['num_pinners']);
            	$board_data['num_pinners']  = trim($board_data['num_pinners']);
            	$board_data['num_pinners']  = (int)$board_data['num_pinners'];   
            } catch(\Exception $e) {
            	echo $e->getMessage().PHP_EOL;
            	$board_data['num_pinners'] = 1;
            } 
            echo "Board with [".$board_data['num_pinners']."] admins and [".$board_data['followers']."] followers". PHP_EOL;     	


        }



        return array(
        	'page_title' => $Pagetitle,
        	'image' => $image,
        	'local_image' => $imageName,
        	'url' => $url,
        	'pinner' => $pinner,
        	'pinner_data' => $pinner_data,
        	'description' => $description,
        	'board_data' => $board_data,
        	'see_also' => $seeAlso,
        	'repins' => $repins,
        	'title' => $title,
        	'likes' => $likes,
        	'pinboard' => $pinboard,
        	'source' => $source,
        	'source_text' => $nodeValues,
        	'source_content' => $ReadabilityData,
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