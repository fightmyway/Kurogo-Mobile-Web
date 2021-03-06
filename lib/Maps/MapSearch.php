<?php

class MapSearch {

    protected $searchResults;
    protected $resultCount;
    protected $feeds;
    protected $feedGroup;
    
    public function __construct($feeds) {
        $this->setFeedData($feeds);
    }
    
    public function setFeedData($feeds) {
        $this->feeds = $feeds;
    }
    
    public function setFeedGroup($feedGroup) {
        $this->feedGroup = $feedGroup;
    }

    public function getSearchResults() {
        return $this->searchResults;
    }
    
    public function getResultCount() {
        return $this->resultCount;
    }

    // tolerance specified in meters
    public function searchByProximity($center, $tolerance=1000, $maxItems=0) {
        $this->searchResults = array();

        $resultsByDistance = array();
        foreach ($this->feeds as $categoryID => $feedData) {
            $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $controller->setCategory($categoryID);
            if ($controller->canSearch()) { // respect config settings
                try {
                    $results = $controller->searchByProximity($center, $tolerance, $maxItems);
                    // can't use array_merge because the keys are numeric
                    // so we do a manual array_merge
                    foreach($results as $distance => $mapFeature) {
                        // avoid distance collisions
                        while(isset($resultsByDistance[$distance])) {
                            $distance++;
                        }
                        $resultsByDistance[$distance] = $mapFeature;
                    }
                } catch (DataServerException $e) {
                    error_log('encountered DataServerException for feed config:');
                    error_log(print_r($feedData, true));
                    error_log('message: '.$e->getMessage());
                }
            }
        }

        ksort($resultsByDistance);

        if ($maxItems && count($resultsByDistance) > $maxItems) {
            array_splice($resultsByDistance, $maxItems);
        }

        $this->searchResults = array_values($resultsByDistance);
        return $this->searchResults;
    }

    public function searchCampusMap($query) {
        $this->searchResults = array();
    
    	foreach ($this->feeds as $id => $feedData) {
            $controller = MapDataController::factory($feedData['CONTROLLER_CLASS'], $feedData);
            $controller->setCategory($id);
            
            if ($controller->canSearch()) {
                try {
                    $results = $controller->search($query);
                    $this->resultCount += count($results);
                    $this->searchResults = array_merge($this->searchResults, $results);
                } catch (DataServerException $e) {
                    error_log('encountered DataServerException for feed config:');
                    error_log(print_r($feedData, true));
                    error_log('message: '.$e->getMessage());
                }
            }
    	}
    	
    	return $this->searchResults;
    }
}



