<?php

$site = $_SERVER['argv'][1];
$action = $_SERVER['argv'][2];
$output = $_SERVER['argv'][3];
$config = require('config/' . $site . '.php');
$import = new Import($config);

$commands = false;
switch ($action) {
    case 'build-download':
        $commands = $import->buildDownload();
        break;
    case 'build-import':
        $commands = $import->buildImport();
        break;
}
if ($commands) {
    if (!file_exists(dirname($output))) {
        mkdir(dirname($output), 0777, true);
    }
    file_put_contents($output, $commands);
}

/**
 * Class Import
 */
class Import
{
    /**
     * @var string
     */
    public $sshHost;

    /**
     * @var string
     */
    public $sshPort;

    /**
     * @var string
     */
    public $sshUsername;

    /**
     * @var string
     */
    public $localPath;

    /**
     * @var string
     */
    public $remotePath;

    /**
     * @var string
     */
    public $excludeFile;

    /**
     * @var string
     */
    public $localHost = 'localhost';

    /**
     * @var string
     */
    public $localPort = '3306';

    /**
     * @var string
     */
    public $localUser = 'root';

    /**
     * @var string
     */
    public $localPass = 'root';

    /**
     * @var string
     *
     * Setup from command line:
     * mysql_config_editor set --login-path=import --host=localhost --user=root --password
     */
    public $localLoginPath;

    /**
     * @var string
     */
    public $localDatabase;

    /**
     * @var string
     */
    public $remoteDatabase;

    /**
     * @var string
     */
    public $remotePathDate;

    /**
     * @var bool
     */
    public $showProgress = false;

    /**
     * Construct the class
     * @param array $config the class config
     */
    public function __construct($config = array())
    {
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Generate the commands to download the database files using rsync
     */
    public function buildDownload()
    {
        $localPath = strtr($this->localPath, array('\\' => '/', ':' => ''));
        $excludeFile = strtr($this->excludeFile, array('\\' => '/', ':' => ''));
        $date = $this->remotePathDate ? date('Y-m-d', strtotime($this->remotePathDate)) : date('Y-m-d');
        $targetPath = strtr($this->remotePath, array('{date}' => $date)) . '/' . $this->remoteDatabase . '.* ';
        $command = "rsync -avz --progress {$this->sshUsername}@{$this->sshHost}:/$targetPath $localPath --exclude-from $excludeFile";
        return $command;
    }

    /**
     * @return string
     */
    public function buildImport()
    {
        $commands = array();
        foreach (glob($this->localPath . '/*.sql.gz') as $file) {
            $mysqlCommand = 'mysql ' . $this->authString() . ' --database=' . $this->localDatabase;
            $fileName = basename($file, '.sql.gz');
            $_ = explode('.', $fileName);
            $tableName = end($_);
            $tableName = str_replace('-schema', '', $tableName);

            $addLinesToPipe = <<<'PipeCommand'
sed -e '1 i SET FOREIGN_KEY_CHECKS = 0;'| sed -e '$s@$@\nSET FOREIGN_KEY_CHECKS = 1;@'
PipeCommand;
            if ($this->showProgress) {
                $command = "pv $file | gunzip | $addLinesToPipe | $mysqlCommand";
            } else {
                $command = "zcat $file | $mysqlCommand";
            }
            if (strpos($fileName, '-schema')) {
                $command = $mysqlCommand . " <<<  \"SET FOREIGN_KEY_CHECKS = 0;DROP TABLE IF EXISTS \`$tableName\`;SET FOREIGN_KEY_CHECKS = 1;\"\n" . $command;
            }
            $commands[] = "echo importing $tableName\n" . $command;
        }
        return implode("\n", $commands);
    }

    /**
     * @return string
     */
    private function authString()
    {
        if ($this->localLoginPath) {
            return '--login-path="' . $this->localLoginPath . '"';
        }
        return '--user="' . $this->localUser . '" --pass="' . $this->localPass . '" --host="' . $this->localHost . '" --port="' . $this->localPort . '"';
    }

}