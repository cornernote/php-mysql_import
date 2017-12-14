<?php
require_once('MysqlImport.php');
$site = $_SERVER['argv'][1];
$action = $_SERVER['argv'][2];
$config = require('config/' . $site . '.php');
$mysqlImport = new MysqlImport($config);

if (!file_exists('runtime/' . $site)) {
    mkdir('runtime/' . $site, 0777, true);
}

switch ($action) {

    case 'download':
        $commands = $mysqlImport->generateDownload();
        file_put_contents('runtime/' . $site . '/download.bat', $commands);
        break;

    case 'unzip':
        $commands = $mysqlImport->generateUnzip();
        file_put_contents('runtime/' . $site . '/unzip.bat', $commands);
        break;

    case 'import':
        $commands = $mysqlImport->generateImport();
        file_put_contents('runtime/' . $site . '/import.bat', $commands);
        break;

    case 'unzipAndImport':
        $commands = $mysqlImport->generateUnzipAndImportList();
        file_put_contents('runtime/' . $site . '/deflate_and_import.bash', $commands);
        break;
}
