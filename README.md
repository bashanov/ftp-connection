# sftp-connector
Library helps to establish SFTP connection and work with files and folders via curl or ssh2.

## Install
Use composer to install
```
composer require bashanov/sftp-connector
```

## Use
There are 2 ways to create sftp connection.
1. Create Factory instance with connection configuration. Example:
```php
/** Creating new connection and getting information about files and folders in current directory */
$sftp = SftpConnectorFactory::createConnection([
            'host' => 'test.website.com',
            'username' => 'login',
            'password' => 'password'
        ]);
print_r($sftp->ls('.'));  
```
2. Create `adapter` extends `AbstractAdapter` class and override `getConfig` method. You may find the example file in repository, `src/adapter/TestAdapter`.


## Notes
Supports only `auth_none` and `auth_password` methods.
