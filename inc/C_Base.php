<?php

abstract class C_Base extends C_Controller{

    protected $title;
    protected $content;
    protected $params;
    
    public function Before(){
        session_start();
        $this->title = 'Blog';
        $this->content = 'MyContent';
    }

    public function Render(){
        $page = $this->Template('theme/layout.php',
            ['title' => $this->title,
                'content' => $this->content]);
        echo $page;
    }
}
?>