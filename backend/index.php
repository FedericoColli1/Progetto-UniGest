<?php

spl_autoload_register(
    function ($class){
        require __DIR__ . DIRECTORY_SEPARATOR . "$class.php";
    }
);


$api = str_replace("index.html","",$_SERVER['SCRIPT_NAME']);
$controller = new Controller;
$controller->set_api($api);
$controller->handle_request();

?>