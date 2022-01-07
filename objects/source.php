<?php
    class Source{
        public $idSource;
        public $idAnalytics;
        public $source;
        public $pageView;
        public $user;

        public function getIdSource(){
            return $this->idSource;
        }
        public function setIdSource($idSource){
            $this->idSource = $idSource;
            return $this;
        }

        public function getIdAnalytics(){
            return $this->idAnalytics;
        }
        public function setIdAnalytics($idAnalytics){
            $this->idAnalytics = $idAnalytics;
            return $this;
        }

        public function getSource(){
            return $this->source;
        }
        public function setSource($source){
            $this->source = $source;
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
    }
?>