<?php

namespace app\Core;

class Config
{

	private static $database='test';
	private static $host='localhost';
	private static $username='root';
	private static $password='123';
	private static $charset='utf8';

	public static function getDatabase(){
		return self::$database;
	}

	public static function getHost(){
		return self::$host;
	}

	public static function getUsername(){
		return self::$username;
	}

	public static function getPassword(){
		return self::$password;
	}

	public static function getCharset(){
		return self::$charset;
	}

}
