<?php

abstract class C_Controller{

    protected abstract function Render();
    protected abstract function Before();

    public function Request($action,$params){
        $this->params = $params;
        $this->Before();
        $this->$action();
        $this->Render();
    }

    protected function IsGet(){
        return $_SERVER['REQUEST_METHOD'] =='GET';
    }

    protected function IsPOST(){
        return $_SERVER['REQUEST_METHOD'] =='POST';
    }

    protected function Template($file,$params = []){
        foreach ( $params as $key => $value ){
            $$key = $value;
        }

        ob_start();
        include $file;
        return ob_get_clean();
    }

    public  function __call($name,$params){
        die('Function call error:'.$name.' ');
    }
}


?>