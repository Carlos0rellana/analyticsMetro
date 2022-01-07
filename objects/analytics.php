<?php
    class Analytics{
        public $bdId;
        public $idArticle;
        public $site;
        public $date;
        public $pageView;
        public $user;
        public $sourceDetails = array();

        public function getBdId(){
                return $this->bdId;
        }
        public function setBdId($bdId){
            $this->bdId = $bdId;
            return $this;
        }

        public function getIdArticle(){
            return $this->idArticle;
        }
        public function setIdArticle($idArticle){
            $this->idArticle = $idArticle;
            return $this;
        }

        public function getSite(){
            return $this->site;
        }
        public function setSite($site){
            $this->site = $site;
            return $this;
        }

        public function getDate(){
            return $this->date;
        }
        public function setDate($date){
            $this->date = $date;
            return $this;
        }

        public function getPageView(){
            return $this->pageView;
        }
        public function setPageView($pageView){
            $this->pageView = $pageView;
            return $this;
        }

        public function getUser(){
            return $this->user;
        }
        public function setUser($user){
            $this->user = $user;
            return $this;
        }

        public function getSourceDetails(){
            return $this->sourceDetails;
        }
        public function setSourceDetails($sourceDetails){
            $this->sourceDetails = $sourceDetails;
            return $this;
        }
    }

?>