<?php

/**
 * =============================================================================
 * CONSTANTS
 * =============================================================================
 */

/**
 * The APPLICATION_ENV constant determines in which environment the
 * application will be running.
 *
 *   - testing: "standalone" mode. The application is plugged to fake
 *              components that emulate the behavior of the target
 *              components. XML stub files are used in order to emulate Web
 *              services, while SQLLite databases are used to emulate SQL
 *              databases.
 *
 *   - staging: "integration" mode. The application is plugged to real
 *             components. Configuration for this components is written
 *             into `tests/config.php`.
 */
if (!defined('APPLICATION_ENV')) {
    throw new Exception('APPLICATION_ENV must be defined in PHPUnit configuration file');
}

/**
 * The PROXY_WRITE_STUBS constants determines whether XML stub files will
 * be written by the HTTP Proxy (`true`), or not (`false`). This only apply
 * in `staging` application environment mode.`
 */
if (APPLICATION_ENV === 'staging' && !defined('PROXY_WRITE_STUBS')) {
    throw new Exception('PROXY_WRITE_STUBS must be defined in PHPUnit configuration file');
}

/**
 * =============================================================================
 * AUTOLOADING
 * =============================================================================
 */

include_once __DIR__ . '/../vendor/autoload.php';

$loader = new Zend\Loader\StandardAutoloader(
    array(
        Zend\Loader\StandardAutoloader::LOAD_NS => array(
            'Test\\HttpProxy' => __DIR__ . '/HttpProxy',
            'Test\\VCloud\\Helpers' => __DIR__ . '',
        ),
    )
);
$loader->register();

/**
 * =============================================================================
 * APPLICATION INITIALIZATION
 * =============================================================================
 */

/**
 * Read configuration
 */
$config = new \Zend\Config\Config(include dirname(__FILE__) . '/config.php');

/**
 * Initialize vCloud Director web service
 */
switch (APPLICATION_ENV) {

    // In `testing` environment mode, create a HTTP Proxy that redirects to XML
    // stub files instead of the real vCloud Director API
    case 'testing':
        $client = new Test\HttpProxy\StubReader(
            $config->httpProxy->directory,
            $config->httpProxy->hosts->toArray(),
            $config->httpProxy->excludeRequestHeaders->toArray(),
            $config->httpProxy->excludeResponseHeaders->toArray()
        );
        break;

    // In `staging` environment mode:
    // - if we are generating stubs, create a HTTP Proxy to write the sub files
    // - otherwise, connect to vCloud Director directly
    case 'staging':
        $client = new Test\HttpProxy\Client();
        $client->getRequest()->attach(new Test\HttpProxy\Monitor());
        if (PROXY_WRITE_STUBS) {
            $client->getRequest()->attach(
                new Test\HttpProxy\StubWriter(
                    $config->httpProxy->directory,
                    $config->httpProxy->hosts->toArray(),
                    $config->httpProxy->excludeRequestHeaders->toArray(),
                    $config->httpProxy->excludeResponseHeaders->toArray()
                )
            );
            break;
        }
        break;

    // Other modes are forbidden
    default:
        throw new Exception('APPLICATION_ENV must be either "testing" or "staging", got "' . APPLICATION_ENV . '"');
}

/**
 * Log in to vCloud Director for each user
 */
$services = array();
foreach ($config->users->toArray() as $id => $credentials) {

    $services[$id] = \Cli\Helpers\Job::run(
        'Autenticating as ' . $credentials['username'] . '@' . $credentials['organization'],
        function ($config, $credentials, $client) {
            $service = VMware_VCloud_SDK_Service::getService($client);
            $service->login(
                $config->host,
                array(
                    'username' => $credentials['username'] . '@' . $credentials['organization'],
                    'password' => $credentials['password'],
                ),
                array(
                    'proxy_host' => null,
                    'proxy_port' => null,
                    'proxy_user' => null,
                    'proxy_password' => null,
                    'ssl_verify_peer' => false,
                    'ssl_verify_host' => false,
                    'ssl_cafile' => null,
                ),
                $config->apiVersion
            );
            return $service;
        },
        array($config, $credentials, clone $client)
    );

}


// TODO remove (legacy)
// $service = VMware_VCloud_SDK_Service::getService($client);

// $service->login(
//     $config->host,
//     array(
//         'username' => $config->users->administrator->username . '@' . $config->users->administrator->organization,
//         'password' => $config->users->administrator->password,
//     ),
//     array(
//         'proxy_host' => null,
//         'proxy_port' => null,
//         'proxy_user' => null,
//         'proxy_password' => null,
//         'ssl_verify_peer' => false,
//         'ssl_verify_host' => false,
//         'ssl_cafile' => null,
//     ),
//     $config->apiVersion
// );
