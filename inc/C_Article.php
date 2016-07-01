<?php

class C_Article extends C_Base {

 
// Articles list show 
    protected function Action_Show_all()
    {

        $this->title .= "::Show_all";

// selecting all records from the data base
        $articles = M_Data::articles_all();
        $mUsers = M_Users::Instance();// connection to DB
        // by calling M_Mysql::GetInstance in the __construct M_Users

        $user = $mUsers->Get();

        $this->content = $this->Template('theme/view_all.php',
            ['articles' => $articles,'login'=>$user['login']]);

    }

// article View
    protected  function Action_look (){
        $this->title .= "::Look";

        if ( isset($_GET['id']) ) $id_article = $_GET['id'];
         else $id_article = $this->params['id'];
// selecting one record from the data base
        $article = M_Data::articles_get($id_article);
        $article[0]['views']++;
        M_Data::articles_edit($article[0]['id_article'], $article[0]['title'],
         $article[0]['content'],$article[0]['views']);
        $this->content = $this->Template('theme/view.php',
          ['title'=>$article[0]['title'],'content'=> $article[0]['content']]);
    }

// article Edit 
    protected  function Action_edit (){
        $this->title .= "::Edit";
        $mUsers = M_Users::Instance();// connection to DB
        // by calling M_Mysql::GetInstance in the __construct M_Users

        $user = $mUsers->Get();
        
// Is it allowed to user to edit articles?
        if ( $user == null || !$mUsers->Can('EDIT_ARTICLE',$user['id_role']))
             {
// Access to service isn't allowed!
            header('Location: /article/Show_all/Edit');

            }
        $id_article = $this->params['id'];
// selecting one record from the data base for updating
        $article = M_Data::articles_get($id_article);

        if( $this->IsPOST() ) {
// data from POST array receiving
            $title = $_POST['title'];
            $content = $_POST['content'];
// updating one record in the data base
            M_Data::articles_edit($id_article, $title, $content, 
                $article[0]['views']);
// direction to index.php
            header('Location: /article/Show_all');
            exit();
       }
// form output to user
        $this->content = $this->Template('theme/edit.php',
            ['title'=> $article[0]['title'],
            'content'=> $article[0]['content']]);
    }

// article Delete 
    protected  function Action_delete (){
        $this->title .= "::Delete";
         $mUsers = M_Users::Instance();// connection to DB
        // by calling M_Mysql::GetInstance in the __construct M_Users


        $user = $mUsers->Get();
        if ( $user == null ) header('Location: /article/Show_all/Delete');
// Is it allowed to user to delete articles?
        if ( $user == null || !$mUsers->Can('DELETE_ARTICLE',$user['id_role']) )
            {
// Access to service isn't allowed!
            header('Location: /article/Show_all/Delete');
            exit();
            }

        $id_article = $this->params['id'];
// deleting one record from the data base
        M_Data::articles_delete($id_article);
        header('Location: /article/Show_all');
        exit();

    }

// article Add
    protected  function Action_add (){
        $this->title .= "::Add";
        $mUsers = M_Users::Instance();// connection to DB
        // by calling M_Mysql::GetInstance in the __construct M_Users

        $user = $mUsers->Get();
         if ( $user == null ) header('Location: /article/Show_all/Add');
        // Может ли пользователь добавлять статьи?
        if ( $user == null || !$mUsers->Can('ADD_ARTICLE',$user['id_role']))
        {
// Access to service isn't allowed!
            header('Location: /article/Show_all/Add');

        }
// Access to service is allowed!
        if( $this->IsPOST() ) {

            $title = $_POST['title'];
            $content = $_POST['content'];
// add one record to the data base
            M_Data::articles_new($title, $content);

            header('Location: /article/Show_all');
            exit();
        }

        $this->content = $this->Template('theme/edit.php',
            []);
    }

// article Comment
    protected  function Action_Comment (){
        $this->title .= "::Comment";
        $mUsers = M_Users::Instance();// connection to DB
        // by calling M_Mysql::GetInstance in the __construct M_Users

        $user = $mUsers->Get();
        $id_article = $this->params['id'];

        $article = M_Data::articles_get($id_article);

        if( $this->IsPOST() ) {

            $name = $user['login'];
            if ( !$name == '' ) 
            {
                $comment = $_POST['comment'];
               
                $comment = $name . ' : ' . $comment;
                if ( !$article[0]['comment'] == '' ) $comment .= "<br>" . $article[0]['comment'];
    // add one comment to the record of the data base           
                M_Data::articles_comment($id_article, $comment);
            }
            header('Location: /article/Show_all');
            exit();
       }

        $this->content = $this->Template('theme/comment.php',
            ['login'=> $user['login']]);
    }

}