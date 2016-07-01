<?php
//
// Менеджер пользователей
//
class M_Users
{	
	private static $instance;	// экземпляр класса
	private $msql;				// драйвер БД
	private $sid;				// идентификатор текущей сессии
	private $uid;				// идентификатор текущего пользователя
	
	//
	// Получение экземпляра класса
	// результат	- экземпляр класса MSQL
	//
	public static function Instance()
	{
		if ( self::$instance == null )
			self::$instance = new M_Users();
		return self::$instance;
	}

	//
	// Конструктор
	//
	public function __construct()
	{
		$this->msql = M_Mysql::GetInstance();
		$this->sid = null;
		$this->uid = null;
	}
	
	//
	// Очистка неиспользуемых сессий
	// 
	public function ClearSessions()
	{
		$min = date('Y-m-d H:i:s', time() - 60 * 20); 			
		$t = "time_last < '%s'";
		$where = sprintf($t, $min);
		$this->msql->Delete('sessions', $where);
	}

	//
	// Авторизация
	// $login 		- логин
	// $password 	- пароль
	// $remember 	- нужно ли запомнить в куках
	// результат	- true или false
	//

	public function Login($login, $password, $remember = true)
	{
		// вытаскиваем пользователя из БД 
		
		file_put_contents('prot.txt', '');
		$s = file_get_contents('prot.txt');
			file_put_contents('prot.txt', $s . 
				date('Y-m-d H:i:s').'#' . 
				$login . '- Login 1;');
		$user = $this->GetByLogin($login);

		if ($user == null){

            // А теперь просто записываем нового пользователя в users.

            $result = $this->msql->Insert('users',['login'=>$login,
                'password'=>md5($password)]);
            $user['id_user'] =  $result;
            $user['login'] = $login;
            $user['password'] = md5($password);

        }

		
		$id_user = $user['id_user'];
		
		
		// проверяем пароль
		if ($user['password'] != md5($password))
			return false;
				
		// запоминаем имя и md5(пароль)
		if ($remember)
		{
			$expire = time() + 3600 * 24 * 100;
			setcookie('login', $login, $expire);
			setcookie('password', md5($password), $expire);
		}		
				
		// открываем сессию и запоминаем SID
		$this->sid = $this->OpenSession($id_user);
		//$s = file_get_contents('prot.txt');
		//file_put_contents('prot.txt', $s .date('Y-m-d H:i:s') .'#' . 
		//	$this->sid . '- Login 2;');
		return true;
	}
	
	//
	// Выход
	//
	public function Logout()
	{
		setcookie('login', '', time() - 1);
		setcookie('password', '', time() - 1);
		unset($_COOKIE['login']);
		unset($_COOKIE['password']);
		unset($_SESSION['sid']);		
		$this->sid = null;
		$this->uid = null;
	}
						
	//
	// Получение пользователя
	// $id_user		- если не указан, брать текущего
	// результат	- объект пользователя
	//
	public function Get($id_user = null)
	{	
		// Если id_user не указан, берем его по текущей сессии.
		if ($id_user == null)
			{$id_user = $this->GetUid();
			//$s = file_get_contents('prot.txt');
			//file_put_contents('prot.txt', $s . 
			//	date('Y-m-d H:i:s').'#' . 
			//	$id_user . '- Get -'); 
			}
			
		if ($id_user == null)
			return null;
			
		// А теперь просто возвращаем пользователя по id_user.
		$t = "SELECT * FROM users WHERE id_user = '%d'";
		$query = sprintf($t, $id_user);
		$result = $this->msql->Select($query);
		return $result[0];		
	}
	
	//
	// Получает пользователя по логину
	//
	public function GetByLogin($login)
	{	
		$t = "SELECT * FROM users WHERE login = '%s'";
		$query = sprintf($t, $this->msql->Msqli_real_esc_str($login));
		$result = $this->msql->Select($query);
		//$s = file_get_contents('prot.txt');
		//			file_put_contents('prot.txt', $s . 
		//				date('Y-m-d H:i:s') .'#'. 
		//				$result[0]['id_user'] . '- GetByLogin -'); 	
		return $result[0];
	}
			
	//
	// Проверка наличия привилегии
	// $priv 		- имя привилегии
	// $id_role
	// результат	- true или false
	//
	public function Can($priv, $id_role = null)
	{

        $query = "SELECT name FROM privs2roles AS t1
           LEFT JOIN privs AS t2 ON t1.id_priv = t2.id_priv WHERE id_role = $id_role
           AND name = " . "'". $priv . "'";
        if ( count($this->msql->Select($query)) > 0 ) return true;
		else return false;
	}

	//
	// Проверка активности пользователя
	// $id_user		- идентификатор
	// результат	- true если online
	//
	public function IsOnline($id_user)
	{
        if ( $this->GetUid() == $id_user )

            return true;

            else return false;

	}
	
	//
	// Получение id текущего пользователя
	// результат	- UID
	//
	public function GetUid()
	{	
		// Проверка кеша.
		if ($this->uid != null)
			return $this->uid;	

		// Берем по текущей сессии.
		$sid = $this->GetSid();
		//$s = file_get_contents('prot.txt');
		//			file_put_contents('prot.txt', $s . 
		//				date('Y-m-d H:i:s') .'#'. 
		//				$sid. '- GetUid -'); 		
		if ($sid == null)
			return null;
			
		$t = "SELECT id_user FROM sessions WHERE sid = '%s'";
		$query = sprintf($t, $this->msql->Msqli_real_esc_str($sid));
		$result = $this->msql->Select($query);
				
		// Если сессию не нашли - значит пользователь не авторизован.
		if (count($result) == 0)
			return null;
			
		// Если нашли - запоминм ее.
		$this->uid = $result[0]['id_user'];
		return $this->uid;
	}

	//
	// Функция возвращает идентификатор текущей сессии
	// результат	- SID
	//
	private function GetSid()
	{
		// Проверка кеша.
		if ($this->sid != null)
			return $this->sid;
	
		// Ищем SID в сессии.
		$sid = $_SESSION['sid'];
		$s = file_get_contents('prot.txt');
		file_put_contents('prot.txt', $s
			 . '#$_SESSION:' . $_SESSION['sid']
			. date('Y-m-d H:i:s') . '#' . session_id()
			. '- GetSid 0;');
		//$s = file_get_contents('prot.txt');
		//			file_put_contents('prot.txt', $s . date('Y-m-d H:i:s') .
		//			 $sid . '- GetSid 1 -');						
		// Если нашли, попробуем обновить time_last в базе. 
		// Заодно и проверим, есть ли сессия там.
		if ($sid != null)
		{
			$session = array();
			$session['time_last'] = date('Y-m-d H:i:s'); 			
			$t = "sid = '%s'";
			$where = sprintf($t, $this->msql->Msqli_real_esc_str($sid));
			$affected_rows = $this->msql->Update('sessions', $session, $where);

			if ($affected_rows == 0)
			{
				$t = "SELECT count(*) FROM sessions WHERE sid = '%s'";		
				$query = sprintf($t, $this->msql->Msqli_real_esc_str($sid));
				$result = $this->msql->Select($query);
				
				if ($result[0]['count(*)'] == 0)
					$sid = null;			
			}			
		}		
		
		// Нет сессии? Ищем логин и md5(пароль) в куках.
		// Т.е. пробуем переподключиться.
		if ($sid == null && isset($_COOKIE['login']))
		{
			$user = $this->GetByLogin($_COOKIE['login']);
			
			if ($user != null && $user['password'] == $_COOKIE['password'])

				{	//$s = file_get_contents('prot.txt');
					//file_put_contents('prot.txt', $s . date('Y-m-d H:i:s') .
					///	$user['login'] . '- GetSid 2 -'); 
					$sid = $this->OpenSession($user['id_user']);}
		}
		
		// Запоминаем в кеш.
		if ($sid != null)
			$this->sid = $sid;
		
		// Возвращаем, наконец, SID.
		return $sid;		
	}
	
	//
	// Открытие новой сессии
	// результат	- SID
	//
	private function OpenSession($id_user)
	{
		// генерируем SID
		$sid = $this->GenerateStr(10);
				
		// вставляем SID в БД
		$now = date('Y-m-d H:i:s'); 
		$session = array();
		$session['id_user'] = $id_user;
		$session['sid'] = $sid;
		$session['time_start'] = $now;
		$session['time_last'] = $now;				
		$this->msql->Insert('sessions', $session); 
		$s = file_get_contents('prot.txt');
		file_put_contents('prot.txt', $s . 
			date('Y-m-d H:i:s') . '#' . $sid 
			. '- OpenSession 1;');
		// регистрируем сессию в PHP сессии
		$_SESSION['sid'] = $sid;				
		$s = file_get_contents('prot.txt');
		file_put_contents('prot.txt', $s . 
			date('Y-m-d H:i:s') . '#' . $_SESSION['sid']
			. '- OpenSession 2;');
		// возвращаем SID
		return $sid;	
	}

	//
	// Генерация случайной последовательности
	// $length 		- ее длина
	// результат	- случайная строка
	//
	private function GenerateStr($length = 10) 
	{
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRQSTUVWXYZ0123456789";
		$code = "";
		$clen = strlen($chars) - 1;  

		while (strlen($code) < $length) 
            $code .= $chars[mt_rand(0, $clen)];  

		return $code;
	}
}
