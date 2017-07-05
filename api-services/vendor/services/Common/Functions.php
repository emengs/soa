<?php

function registerServices()
{
    include_once dirname(__DIR__) . '/Config/ServiceSetting_'.ENV.'.php';
    include_once __DIR__ . '/ConsulHelper.php';
    if (!empty($AppServices))
    {
        $host = $AppServices['host'];
        $port = $AppServices['port'];
        $services = $AppServices['services'];
        if (!empty($services) && is_array($services))
        {
            foreach ($services as $items)
            {
                $serviceHost = isset($items['host']) ? $items['host'] : $host;
                $servicePort = isset($items['port']) ? $items['port'] : $port;
                $serviceId = $items['id'];
                $serviceName = $items['name'];
                $consul = new Applications\Services\Common\ConsulHelper();
                $consul->registerService($serviceId, $serviceName, $serviceHost, $servicePort, '');
            }
        }
    }
}
