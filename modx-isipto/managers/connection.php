<?php
$user = 'u0496499_manager';
$password = '123nice123';
$db = 'u0496499_manager';
$host = 'localhost';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8";
$pdo = new PDO($dsn, $user, $password);
array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
?>