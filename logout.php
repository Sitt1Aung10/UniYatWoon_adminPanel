<?php
session_start();

$_SESSION = array();

session_destroy();

if (isset($_COOKIE['Username'])) {
    setcookie('Username', '' , time() - 3600 , '/')
}

if(isset($_COOKIE['Email'])) {
    setcookie('Email' , '' , time() - 3600 , '/')
}