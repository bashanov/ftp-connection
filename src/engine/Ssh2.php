<?php

namespace bashanov\sftp\engine;

use bashanov\sftp\SftpAbstractConnector;

/**
 * Class Ssh2
 * @package bashanov\sftp\engine
 */
class Ssh2 extends SftpAbstractConnector
{
    /** @var resource */
    protected $resource;

    /**
     * @throws \Exception
     */
    protected function init()
    {
        $session = ssh2_connect($this->host, $this->port);
        if ($session === false) {
            throw new \Exception("Cannot establish ssh2 connect");
        }
        if ($this->authMethod === static::SSH_AUTH_METHOD_PASS) {
            ssh2_auth_password($session, $this->username, $this->password);
        } elseif ($this->authMethod === self::SSH_AUTH_METHOD_NONE) {
            ssh2_auth_none($session, $this->username);
        }
        $ssh2SftpResource = ssh2_sftp($session);
        if ($session === false) {
            throw new \Exception("Cannot establish ssh2sftp session");
        }
        $this->resource = $ssh2SftpResource;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getRemoteLocation($path)
    {
        $path = preg_replace('!^\/+!', '', trim($path));
        return str_replace('///', '//', sprintf("ssh2.sftp://%s/%s", $this->resource, $path));
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function ls($folder)
    {
        $filesList = [];
        $sftpFolder = $this->getRemoteLocation($folder);
        $dirDescriptor = opendir($sftpFolder);
        if ($dirDescriptor === false) {
            throw new \Exception(sprintf("Unable to open ssh2 folder: %s", $sftpFolder));
        }
        $sftpFolder = preg_replace('!\/+$!', '', $sftpFolder);
        while ($f = readdir($dirDescriptor)) {
            if (!in_array($f, ['.', '..'])) {
                $mtime = filemtime($sftpFolder . DIRECTORY_SEPARATOR . $f);
                if ($mtime) {
                    $filesList[$mtime] = $f;
                }
            }
        }
        closedir($dirDescriptor);
        krsort($filesList);
        return $filesList;
    }

    /**
     * @inheritdoc
     */
    public function mv($from, $to)
    {
        return ssh2_sftp_rename($this->resource, $from, $to);
    }

    /**
     * @inheritdoc
     */
    public function rm($file)
    {
        return ssh2_sftp_unlink($this->resource, $file);
    }

    /**
     * @inheritdoc
     */
    public function exist($file)
    {
        return file_exists($this->getRemoteLocation($file));
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function save($sftpFile, $localFile = null)
    {
        if (is_null($localFile)) {
            if (preg_match('![^\/]+$!i', $sftpFile, $match)) {
                $localFileName = array_shift($match);
            } else {
                $localFileName = crc32($sftpFile);
            }
            $localFile = $this->getTmpFile($localFileName);
        }
        if (!$this->exist($sftpFile)) {
            throw new \Exception(sprintf('File on sftp server not exist: %s', $sftpFile));
        }
        if (copy($this->getRemoteLocation($sftpFile), $localFile) === false) {
            throw new \Exception(sprintf('Unable to copy file from sftp %s to local %s', $sftpFile, $localFile));
        }
        return $localFile;
    }

    /**
     * @param string $localFile
     * @param string $sftpFile
     * @return string
     * @throws \Exception
     */
    public function put($localFile, $sftpFile)
    {
        $sftpRemoteLocation = $this->getRemoteLocation($sftpFile);
        $fp = fopen($sftpRemoteLocation, 'w');
        $writed = fwrite($fp, file_get_contents($localFile));
        if ($writed !== false) {
            unlink($localFile);
        } else {
            throw new \Exception(sprintf("Unable to put local file %s to sftp %s", $localFile, $sftpFile));
        }
        fclose($fp);
        return $sftpRemoteLocation;
    }
}