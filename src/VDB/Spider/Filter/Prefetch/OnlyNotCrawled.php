<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilter;
use VDB\Spider\Uri\FilterableUri;

/**
 * @author matthijs
 */
class OnlyNotCrawled implements PreFetchFilter
{
    private $crawledFile;
    private $procesedPosts;

    /**
     * @param string[] $schemes
     */
    public function __construct($crawledFile)
    {
        $this->crawledFile = $crawledFile;
        $this->loadProcessedPost();
    }

    /**
     * @return bool
     */
    public function match(FilterableUri $uri)
    {
        $uriMd5 = md5($uri);
        if ( isset($this->procesedPosts[$uriMd5]) ) { //Only not processed posts
            $uri->setFiltered(true, 'Scheme not allowed');
            echo "Already Crawled: ".$uri."\n";
            
            return true;
        }
        echo "Not crawled: ".$uri."\n";
        $this->setProcessedPost($uriMd5);  
        $this->saveProcessedPost(); 
        return false;
    }

    private function setProcessedPost($fid) {
        $this->procesedPosts[$fid] = true;
        $this->saveProcessedPost();
    }
    private function loadProcessedPost() {

        if ( file_exists($this->crawledFile )) {
            $this->procesedPosts = json_decode( file_get_contents($this->crawledFile ), true);
        }
    }
    private function saveProcessedPost() {
        file_put_contents($this->crawledFile , json_encode($this->procesedPosts) );
    }

}
