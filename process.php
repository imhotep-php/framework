<?php

use Imhotep\Console\Process;

include('vendor/autoload.php');

$process = Process::fromCommand('ping mail.ru -t 4');
//$process = Process::fromCommand('docker info > /dev/null 2>&1');
//$process = Process::fromCommand(['ping','mail.ru','-t',4]);

//var_dump($process->getCommand());
//die();

$code = $process->run(function ($type, $data) {
    echo $data;
});

var_dump("Exitcode: ".$code);

