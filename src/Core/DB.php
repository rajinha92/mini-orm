<?php

namespace app\Core;

class DB{

	private static $conn=null;

	private function __construct(){}

	public static function getInstance(){
		if(is_null(self::$conn))
			self::$conn = new \PDO('mysql:host='.Config::getHost().';dbname='.Config::getDatabase(),Config::getUsername(),Config::getPassword(),[]);
		return self::$conn;
	}
}
