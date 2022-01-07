<?php
    class Article{
        public $id;
        public $site;
        private $title;
        public $url;
        private $mainCategory;
        private $author;
        private $primaySite;
        private $datePublish;
        private $type;
        public $searchStatus=false;

        public function getId(){
            return $this->id;
        }
        public function setId($id){
            $this->id = $id;
            return $this;
        }

        public function getSite(){
            return $this->site;
        }
        public function setSite($site){
            $this->site = $site;
            return $this;
        }

        public function getTitle(){
            return $this->title;
        }
        public function setTitle($title){
            $this->title = $title;
            return $this;
        }

        public function getUrl(){
            return $this->url;
        }
        public function setUrl($url){
            $this->url = $url;
            return $this;
        }

        public function getMainCategory(){
            return $this->mainCategory;
        }
        public function setMainCategory($mainCategory){
            $this->mainCategory = $mainCategory;
            return $this;
        }

        public function getAuthor(){
            return $this->author;
        }
        public function setAuthor($author){
            $this->author = $author;
            return $this;
        }

        public function getPrimaySite(){
            return $this->primaySite;
        }
        public function setPrimaySite($primaySite){
            $this->primaySite = $primaySite;
            return $this;
        }

        public function getDatePublish(){
            return $this->datePublish;
        }
        public function setDatePublish($datePublish){
            $this->datePublish = $datePublish;
            return $this;
        }

        public function getType(){
            return $this->type;
        }
        public function setType($type){
            $this->type = $type;
            return $this;
        }

        public function getSearchStatus(){
             return $this->searchStatus;
        }
        public function setSearchStatus($searchStatus){
            $this->searchStatus = $searchStatus;
            return $this;
        }
    }
?>