<?php

namespace bashanov\sftp\adapter;

use bashanov\sftp\SftpAbstractConnector;
use bashanov\sftp\SftpConnectorFactory;
use bashanov\sftp\SftpConnectorInterface;

/**
 * Class AbstractAdapter
 * @package bashanov\ftp\adapter
 */
abstract class AbstractAdapter
{
    /** @var SftpConnectorInterface */
    private $engine;

    /**
     * AdepterAbstract constructor.
     * @throws \Exception
     */
    public final function __construct()
    {
        $this->engine = SftpConnectorFactory::createConnection($this->getConfig());
    }

    /**
     * @return array
     */
    abstract function getConfig();

    /**
     * @return SftpAbstractConnector
     */
    public function getEngine()
    {
        return $this->engine;
    }
}