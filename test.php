<?php
$link = new PDO('mysql:host=localhost;dbname=hrwx', 'root', 'root');
$query = $link->prepare("show databases");
$res = $query->execute();
$data = $res->fetchAll();
var_dump( $data ) ;
