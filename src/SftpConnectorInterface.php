<?php

namespace bashanov\sftp;

/**
 * Interface SftpConnectorInterface
 * @package bashanov\sftp
 */
interface SftpConnectorInterface
{
    /**
     * Returns array of files in folder
     * @param string $folder
     * @return array
     */
    public function ls($folder);

    /**
     * Move file
     * @param string $from
     * @param string $to
     * @return bool
     */
    public function mv($from, $to);

    /**
     * Remove file
     * @param string $file
     * @return bool
     */
    public function rm($file);

    /**
     * Check file or directory is exist
     * @param string $file
     * @return string|false
     */
    public function exist($file);

    /**
     * @param string $sftpFile
     * @param string $localFile
     * @return string|false
     */
    public function save($sftpFile, $localFile = null);

    /**
     * @param string $localFile
     * @param string $sftpFile
     * @return string|false
     */
    public function put($localFile, $sftpFile);

    /**
     * @param string $path
     * @return string
     */
    public function getRemoteLocation($path);
}