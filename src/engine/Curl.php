<?php

namespace bashanov\sftp\engine;

use bashanov\sftp\SftpAbstractConnector;

/**
 * Class Curl
 * @package bashanov\sftp\engine
 */
class Curl extends SftpAbstractConnector
{
    /** @var resource */
    protected $resource;

    /**
     * @throws \Exception
     */
    protected function init()
    {
        $curl = curl_init(sprintf('sftp://%s:%d', $this->host, $this->port));
        if ($curl === false) {
            throw new \Exception("Cannot establish curl connect");
        }
        if ($this->authMethod === self::SSH_AUTH_METHOD_PASS) {
            curl_setopt_array($curl, [
                CURLOPT_USERPWD => sprintf('%s:%s', $this->username, $this->password),
                CURLOPT_SSH_AUTH_TYPES => $this->authMethod,
                CURLOPT_RETURNTRANSFER => true
            ]);
        }
        $this->resource = $curl;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getRemoteLocation($path)
    {
        $path = preg_replace('!^\/+!', '', trim($path));
        return str_replace('///', '//', sprintf("sftp://%s:%d/%s", $this->host, $this->port, $path));
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function ls($folder)
    {
        $filesList = [];
        $sftpFolder = preg_replace('!\/+$!', '', $this->getRemoteLocation($folder)) . DIRECTORY_SEPARATOR;
        $ch = curl_copy_handle($this->resource);
        curl_setopt_array($ch, [
            CURLOPT_URL => $sftpFolder,
            CURLOPT_QUOTE => ["statvfs ."]
        ]);
        $result = curl_exec($ch);
        foreach (explode(PHP_EOL, $result) as $row) {
            if ($row) {
                if (preg_match('!(\w+\s+\d+\s+[\d:]+)\s+(\S+)$!i', $row, $match)) {
                    if (!in_array($match[2], ['.', '..'])) {
                        $filesList[(new \DateTime($match[1]))->getTimestamp()] = $match[2];
                    }
                } else {
                    curl_close($ch);
                    throw new \Exception(sprintf('Unable to parse date in row: %s', $row));
                }
            }
        }
        curl_close($ch);
        krsort($filesList);
        return $filesList;
    }

    /**
     * @inheritdoc
     * @see http://manpages.ubuntu.com/manpages/bionic/man3/CURLOPT_QUOTE.3.html
     * @throws \Exception
     */
    public function mv($from, $to)
    {
        if (!$this->exist($from)) {
            throw new \Exception(sprintf('File on sftp not exist: %s', $from));
        }
        $from = preg_replace('!^\/+!', '', $from);
        $to = preg_replace('!^\/+!', '', $to);
        $ch = curl_copy_handle($this->resource);
        curl_setopt_array($ch, [
            CURLOPT_QUOTE => ["rename /$from /$to"]
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * @inheritdoc
     * @see http://manpages.ubuntu.com/manpages/bionic/man3/CURLOPT_QUOTE.3.html
     */
    public function rm($file)
    {
        $ch = curl_copy_handle($this->resource);
        curl_setopt_array($ch, [
            CURLOPT_QUOTE => ["rm /$file"]
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function exist($file)
    {
        $folder = preg_replace('![^\/]+$!i', '', $file);
        $fileName = preg_replace('!(^.+)\/!i', '', $file);
        if (!$folder) {
            $folder = '/';
        }
        $fileList = $this->ls($folder);
        return array_search($fileName, $fileList) !== false;
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
        $ch = curl_copy_handle($this->resource);
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->getRemoteLocation($sftpFile)
        ]);
        if (!file_put_contents($localFile, curl_exec($ch))) {
            curl_close($ch);
            throw new \Exception(sprintf('Unable to copy file from sftp %s to local %s', $sftpFile, $localFile));
        }
        return $localFile;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function put($localFile, $sftpFile)
    {
        $ch = curl_copy_handle($this->resource);
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->getRemoteLocation($sftpFile),
            CURLOPT_UPLOAD => true,
            CURLOPT_INFILE => fopen($localFile, 'r'),
            CURLOPT_INFILESIZE => filesize($localFile),
            CURLOPT_TIMEOUT_MS => 10000
        ]);
        if (curl_exec($ch) === false) {
            throw new \Exception(sprintf('Unable tu put local file %s on sftp %s', $localFile, $sftpFile));
        }
        return $sftpFile;
    }
}