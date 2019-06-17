<?php

namespace bashanov\sftp;

/**
 * Class SftpAbstractConnector
 * @package bashanov\sftp
 */
abstract class SftpAbstractConnector implements SftpConnectorInterface
{
    const SSH_AUTH_METHOD_NONE = 0;
    const SSH_AUTH_METHOD_PASS = 2;

    protected $host;
    protected $port = 22;
    protected $username;
    protected $password;
    protected $authMethod = 2;
    protected $tmpFolder;

    /** @var \ReflectionObject */
    private $reflection;

    public function __construct($config)
    {
        $this->reflection = new \ReflectionObject($this);
        $this->applyConfiguration($config);
        $this->init();
        $this->getTmpFolder();
    }

    /**
     * @return mixed
     */
    abstract protected function init();

    /**
     * Set protected properties
     * @param array $config
     */
    private function applyConfiguration($config)
    {
        if (!empty($config) && is_array($config)) {
            foreach ($this->reflection->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
                $pName = $property->getName();
                if (isset($config[$pName])) {
                    $this->$pName = $config[$pName];
                }
            }
        }
    }

    /**
     * @return bool|string
     */
    public function getTmpFolder()
    {
        if (is_null($this->tmpFolder)) {
            $this->tmpFolder = sprintf('/tmp/sftp/%s/%d/', $this->reflection->getShortName(), crc32(time()));
            if (!is_dir($this->tmpFolder)) {
                mkdir($this->tmpFolder, 0777, true);
            }
        }
        return $this->tmpFolder;
    }

    /**
     * @param string $file
     * @return string
     */
    public function getTmpFile($file)
    {
        $file = preg_replace("!\/!", '', trim($file));
        return $this->getTmpFolder() . $file;
    }

    /**
     * Removing all files that has been saved by worker int tmp folder
     * @return void
     */
    public function __destruct()
    {
        $this->cleanTmpFolder();
    }

    /**
     * @return void
     */
    private function cleanTmpFolder()
    {
        $list = scandir($this->getTmpFolder());
        if (is_array($list)) {
            foreach ($list as $f) {
                if (!in_array($f, ['.', '..'])) {
                    unlink($this->getTmpFile($f));
                }
            }
        }
        rmdir($this->getTmpFolder());
    }
}