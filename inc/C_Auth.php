<?php

class C_Auth extends C_Base
{

    public function Action_Login()
    {


// Менеджеры.

        $mUsers = M_Users::Instance();// connection to DB
        // by calling M_Mysql::GetInstance in the __construct M_Users

// Очистка старых сессий.
        $mUsers->ClearSessions();

// Выход.
        $mUsers->Logout();

// Обработка отправки формы.
        if (!empty($_POST)) {

            if ($mUsers->Login($_POST['login'],
                $_POST['password'],
                isset($_POST['remember']))
            ) {

                header('Location: /article/Show_all');
                exit();

            }
        }

        $this->content = $this->Template('theme/v_login.php',
            []);
    }



}





