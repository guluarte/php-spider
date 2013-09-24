<?php

use Symfony\Component\EventDispatcher\Event;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\EventListener\PolitenessPolicyListener;
use VDB\Spider\Filter\Prefetch\AllowedHostsFilter;
use VDB\Spider\Filter\Prefetch\AllowedSchemeFilter;
use VDB\Spider\Filter\Prefetch\UriWithHashFragmentFilter;
use VDB\Spider\Filter\Prefetch\UriWithQueryStringFilter;
use VDB\Spider\Filter\Prefetch\OnlyNotCrawled;
use VDB\Spider\Spider;

require_once('bootstrap.php');

// The URI we want to start crawling with
$domain = 'videosyamor.com';
$seed = 'http://'.$domain.'/';

// We want to allow all subdomains of dmoz.org
$allowSubDomains = false;

// Create spider
$spider = new Spider($seed, $domain);

// Set some sane defaults for this example. We only visit the first level of www.dmoz.org. We stop at 10 queued resources
$spider->setMaxDepth(1000);
$spider->setMaxQueueSize(1000000);

// We add an URI discoverer. Without it, the spider wouldn't get past the seed resource.
$spider->addDiscoverer(new XPathExpressionDiscoverer("//a"));

// Let's tell the spider to save all found resources on the filesystem
$spider->setPersistenceHandler(
	new \VDB\Spider\PersistenceHandler\VideosDeTerrorJson(__DIR__ . '/results')
	);

// This time, we set the traversal algorithm to breadth-first. The default is depth-first
$spider->setTraversalAlgorithm(Spider::ALGORITHM_DEPTH_FIRST);

// Add some prefetch filters. These are executed before a resource is requested.
// The more you have of these, the less HTTP requests and work for the processors
$spider->addPreFetchFilter(new AllowedSchemeFilter(array('http')));
$spider->addPreFetchFilter(new AllowedHostsFilter(array($seed), $allowSubDomains));
$spider->addPreFetchFilter(new UriWithHashFragmentFilter());
$spider->addPreFetchFilter(new UriWithQueryStringFilter());
//$spider->addPreFetchFilter(new OnlyNotCrawled('./lolzbook-crawled.json'));

// We add an eventlistener to the crawler that implements a politeness policy. We wait 450ms between every request to the same domain
$politenessPolicyEventListener = new PolitenessPolicyListener(450);
$spider->getDispatcher()->addListener(
	SpiderEvents::SPIDER_CRAWL_PRE_REQUEST,
	array($politenessPolicyEventListener, 'onCrawlPreRequest')
	);

// Let's add a CLI progress meter for fun
echo "\nCrawling";
$spider->getDispatcher()->addListener(
	SpiderEvents::SPIDER_CRAWL_FILTER_POSTFETCH,
	function (Event $event) {
		echo '.';	
	}
);

//// Set up some caching, logging and profiling on the HTTP client of the spider
$guzzleClient = $spider->getRequestHandler()->getClient();
$guzzleClient->addSubscriber($logPlugin);
$guzzleClient->addSubscriber($timerPlugin);
$guzzleClient->addSubscriber($cachePlugin);

// Set the user agent
$guzzleClient->setUserAgent('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');

// Execute the crawl
function crawl($spider) {
	try {
		$result = $spider->crawl();
	} catch(\Exception $e) {
		crawl($spider);
	}
}
crawl($spider);

// Report
$stats = $spider->getStatsHandler();
$spiderId = $stats->getSpiderId();
$queued = $stats->getQueued();
$filtered = $stats->getFiltered();
$failed = $stats->getFailed();

echo "\n\nSPIDER ID: " . $spiderId;
echo "\n  ENQUEUED:  " . count($queued);
echo "\n  SKIPPED:   " . count($filtered);
echo "\n  FAILED:    " . count($failed);

// With the information from some of plugins and listeners, we can determine some metrics
$peakMem = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
$totalTime = round(microtime(true) - $start, 2);
$totalDelay = round($politenessPolicyEventListener->totalDelay / 1000 / 1000, 2);
echo "\n\nMETRICS:";
echo "\n  PEAK MEM USAGE:       " . $peakMem . 'MB';
echo "\n  TOTAL TIME:           " . $totalTime . 's';
echo "\n  REQUEST TIME:         " . $timerPlugin->getTotal() . 's';
echo "\n  POLITENESS WAIT TIME: " . $totalDelay . 's';
echo "\n  PROCESSING TIME:      " . ($totalTime - $timerPlugin->getTotal() - $totalDelay) . 's';

