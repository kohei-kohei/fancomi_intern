<?php
$res = file_get_contents('php://input');
$bidResponse = json_decode($res, true);
echo $bidResponse['bidfloor']."\n";

echo "HTTP/1.1 200 OK";
