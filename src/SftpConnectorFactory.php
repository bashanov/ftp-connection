<?php

namespace bashanov\sftp;

use bashanov\sftp\engine\Curl;
use bashanov\sftp\engine\Ssh2;

/**
 * Class SftpConnectorFactory
 * @package bashanov\sftp
 */
class SftpConnectorFactory
{
    /**
     * @param array $config
     * @return SftpAbstractConnector
     * @throws \Exception
     */
    public static function createConnection($config)
    {
        if (function_exists('ssh2_connect')) {
            return new Ssh2($config);
        } elseif (function_exists('curl_init')) {
            return new Curl($config);
        }
        throw new \Exception("No one engine is suitable for the environment");
    }
}