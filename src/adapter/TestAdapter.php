<?php

namespace bashanov\sftp\adapter;

/**
 * Class TestAdapter
 * @package bashanov\sftp\adapter
 */
class TestAdapter extends AbstractAdapter
{
    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'host' => 'test.website.com',
            'username' => 'username',
            'password' => 'password'
        ];
    }
}