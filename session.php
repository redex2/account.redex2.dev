<?php
	require_once(__DIR__."/../sql.php");
	class SESSION 
	{
		function is_set()
		{
			if(!isset($_COOKIE['user']))return -1;
			$uuid=$_COOKIE['user'];
			$pdo=db_connect();
			if(!$pdo) return -5;
			$uid=0;//returning falue = users_id
			$n=0;//row return counter
			try
			{
				$out = $pdo->query("SELECT users_id FROM sessions WHERE expire > NOW() AND uuid='$uuid';");
			}
			catch(PDOException $e)
			{
				error_log('SQL error: ' . $e->getMessage()); //if not duplicate log error and return
				return -2;
			}
			
			foreach($out as $row)
			{
				$n++;
				$uid=$row['users_id'];
			}
			if($n!==1)return -3;//lower not login, greater impossible... theoretically
			else 
			{
				return $uid;//correct
			}
		}
		
		function start($id=NULL, $type=1)
		{
			$interval="";
			if($type===0)$interval='1 HOUR';
			else $interval='30 DAY';
			$pdo=db_connect();
			if(!$pdo) return -5;
			$n=0;//row return counter
			try
			{
				$out = $pdo->query("INSERT INTO sessions VALUES(gen_random_uuid(), $id, NOW()+INTERVAL '$interval') RETURNING uuid;");//try create new session
			}
			catch(PDOException $e)
			{
				if($e->getCode() != 23505)//duplicate code
				{
					error_log('SQL error: ' . $e->getMessage()); //if not duplicate log error and return
					return 0;
				}
				else
				{
					return -1;//duplicate
				}
			}
				
			
			$uuid="";
			foreach($out as $row)
			{
				$n++;
				$uuid=$row['uuid'];
			}
			if($n!==1)return -2;//it's impossible... theoretically
			else 
			{
				if($type)setcookie("user", $uuid, time() + (86400 * 30), "/", get_domain(), true, true);
				else setcookie("user", $uuid, time() + 300, "/", get_domain(), true, true);
				return 1;//correct
			}
		}
		function extend($time = "30 DAY")
		{
			if(!isset($_COOKIE['user']))return -1;
			$uuid=$_COOKIE['user'];
			try
			{
				$out = $pdo->exec("UPDATE sessions SET expire = NOW()+INTERVAL '$time' WHERE uuid='$uuid';");
			}
			catch(PDOException $e)
			{
				error_log('SQL error: ' . $e->getMessage());
				return 0;
			}
			return 1;
		}
		function close()
		{
			if(!isset($_COOKIE['user']))return -1;
			$uuid=$_COOKIE['user'];
			setcookie("user", null, -1, "/", get_domain(), true, true);
			try
			{
				$out = $pdo->exec("UPDATE sessions SET expire = NOW(), users_id = NULL WHERE uuid='$uuid';"); //set expire to now and uid to null
			}
			catch(PDOException $e)
			{
				error_log('SQL error: ' . $e->getMessage());
				return 0;
			}
			return 1;
		}
		function change_uuid()
		{
			if(!isset($_COOKIE['user']))return -1;
			$o_uuid=$_COOKIE['user'];
			$pdo=db_connect();
			if(!$pdo) return -5;
			$n=0;//row return counter
			try
			{
				$out = $pdo->query("UPDATE sessions SET uuid = gen_random_uuid() WHERE uuid='$o_uuid' RETURNING uuid;");//try change uuid new session
			}
			catch(PDOException $e)
			{
				if($e->getCode() != 23505)//duplicate code
				{
					error_log('SQL error: ' . $e->getMessage()); //if not duplicate log error and return
					return 0;
				}
				else
				{
					return -1;//duplicate
				}
			}
			
			$uuid="";
			foreach($out as $row)
			{
				$n++;
				$uuid=$row['uuid'];
			}
			if($n!==1)return -2;//it's impossible... theoretically
			else 
			{
				setcookie("user", $uuid, time() + (86400 * 30), "/", get_domain(), true, true);
				return 1;//correct
			}
		}
	}
?>