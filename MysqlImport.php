<?php
/**
 * Class MysqlImport
 *
 */
class MysqlImport
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
    public $rsync;

    /**
     * @var string
     */
    public $ssh;

    /**
     * @var string
     */
    public $sevenZip;

    /**
     * @var string
     */
    public $localPath;

    /**
     * @var string
     */
    public $excludeFile;

    /**
     * @var string
     */
    public $unzipPath;

    /**
     * @var string
     */
    public $remotePath;

    /**
     * @var string
     */
    public $localDatabase;

    /**
     * @var string
     */
    public $remoteDatabase;

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
    public function generateDownload()
    {
        $localPath = '/cygdrive/' . strtr($this->localPath, array('\\' => '/', ':' => ''));
        $excludeFile = '/cygdrive/' . strtr($this->excludeFile, array('\\' => '/', ':' => ''));
        $command = $this->rsync . ' -avz --progress --rsh="' . $this->ssh . ' -p' . $this->sshPort . '" --exclude-from "' . $excludeFile . '" ';
        $command .= $this->sshUsername . '@' . $this->sshHost . ':' . strtr($this->remotePath, array('{date}' => date('Y-m-d'))) . '/' . $this->remoteDatabase . '.* ' . $localPath;
        return $command;
    }

    /**
     * Generate the commands to unzip the database files using 7zip
     */
    public function generateUnzip()
    {
        if (!file_exists($this->unzipPath)) {
            mkdir($this->unzipPath, 0777, true);
        }
        $commands = array();
        foreach (glob($this->localPath . '/*.sql.gz') as $file) {
            if (strpos($file, '-schema') !== false) {
                $unzipPath = $this->unzipPath . '/schema';
            }
            else {
                $unzipPath = $this->unzipPath . '/data';
            }
            $commands[] = '"' . $this->sevenZip . '" e -y -o"' . $unzipPath . '\" "' . str_replace('/', '\\', $file) . '"';
        }
        return implode("\n", $commands);
    }

    /**
     * Generate the commands to import the database files using MySQL
     */
    public function generateImport()
    {
        $commands = array();
        foreach (glob($this->unzipPath . '/schema/*.sql') as $schemaFile) {
            $dataFile = strtr($schemaFile, array('/schema/' => '/data/', '-schema.sql' => '.sql'));
            $table = str_replace(array($this->unzipPath . '/schema/', $this->remoteDatabase . '.', '-schema.sql'), '', $schemaFile);
            $commands[] = 'echo importing ' . $table;
            $commands[] = 'mysql --user=root --host=localhost --database=' . $this->localDatabase . ' --execute="drop table ' . $table . '"';
            $commands[] = 'mysql --user=root --host=localhost --database=' . $this->localDatabase . ' < "' . $schemaFile . '"';
            if (file_exists($dataFile)) {
                $commands[] = 'mysql --user=root --host=localhost --database=' . $this->localDatabase . ' < "' . $dataFile . '"';
            }
        }
        return implode("\n", $commands);
    }

}