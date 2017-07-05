<?php

namespace Applications\ApiGateway\Common;

/**
 * RabbitMQ操作类
 * @author SunnyZeng
 *        
 */
class RabbitMQHelper
{
    protected static $_instance = null;
    private static $rabbitClient = null;
    private $exchangeName = '';
    private $queueName = '';
    private $routeKey = '';
    private $connected = false;

    /**
     * 
     * @param  string $exchange  [队列池]
     * @param  string $queueName [队列名]
     * @param  string $routeKey  [路由key]
     */
    private function __construct($exchange, $queueName, $routeKey)
    {
        $this->exchangeName = $exchange;
        $this->queueName = $queueName;
        $this->routeKey = $routeKey;

        if (empty(self::$rabbitClient))
        {
            $this->connect();
        }
    }
    /*     * 初始化链接
     * [getInstance description]
     * @param  string $exchange  [队列池]
     * @param  string $queueName [队列名]
     * @param  string $routeKey  [路由key]
     * @return recourse            [description]
     */

    public static function getInstance($exchange, $queueName, $routeKey)
    {
        if (empty(self::$_instance))
        {
            self::$_instance = new self($exchange, $queueName, $routeKey);
        }
        return self::$_instance;
    }

    /**
     * 连接方法
     * [connect description]
     * @return [type] [description]
     */
    private function connect()
    {
        $AmqCongif = require_once dirname(__DIR__) . '/Config/AmqpConfig.php';
        try
        {
            if (empty($AmqCongif))
            {
                throw new Exception("The rabbit server config is not set,please check it!!", 1);
            }

            $config = $AmqCongif;

            if (!isset($config['HOST']) || !isset($config['PORT']) || !isset($config['VHOST']))
            {
                return false;
            }
            self::$rabbitClient = new \AMQPConnection(array('host' => $config['HOST'],
              'port' => $config['PORT'],
              'vhost' => $config['VHOST'],
              'login' => $config['User'],
              'password' => $config['Password']));

            $result = self::$rabbitClient->connect();

            if ($result)
            {
                $this->connected = true;
            }
            return $result ? true : false;
        }
        catch (\Exception $e)
        {
            return false;
        }
    }

    /**
     *  发送消息
     * @return [type] [description]
     */
    public function sendMessage($message)
    {
        if (self::$rabbitClient == null)
        {
            $this->connect();
        }
        if ($this->connected == false)
        {
            self::$rabbitClient->connect();
        }
        else if (self::$rabbitClient->isConnected() == false)
        {
            $this->connected = self::$rabbitClient->reconnect();
        }

        try
        {
            $result = false;
            if ($this->connected)
            {
                $channel = new \AMQPChannel(self::$rabbitClient);
                $exchange = new \AMQPExchange($channel);
                $exchange->setName($this->exchangeName);
                $queue = new \AMQPQueue($channel);
                $queue->setName($this->queueName);
                $result = $exchange->publish($message, $this->routeKey);
                echo 'sendMessage Channel:', $this->exchangeName, ' queue:', $this->queueName, ' routeKey:', $this->routeKey, PHP_EOL;
            }
            return $result ? true : false;
        }
        catch (\Exception $e)
        {
            echo 'rabbitmq send message fail:', $e->getMessage(), PHP_EOL;
            return false;
        }
    }

    /**
     * 接收消息 demo
     * @param funtion $callback 消息回调方法
     * @return [type] [description]
     */
    public function receiveMessage($callback)
    {
        if (self::$rabbitClient == null)
        {
            $this->connect();
        }
        if ($this->connected == false)
        {
            self::$rabbitClient->connect();
        }
        else if (self::$rabbitClient->isConnected() == false)
        {
            $this->connected = self::$rabbitClient->reconnect();
        }

        try
        {
            $result = false;
            if ($this->connected)
            {
                $channel = new \AMQPChannel(self::$rabbitClient);
                $exchange = new \AMQPExchange($channel);
                $exchange->setName($this->exchangeName);
                $exchange->setType(AMQP_EX_TYPE_DIRECT);
                $exchange->declareExchange();
                $queue = new \AMQPQueue($channel);
                $queue->setName($this->queueName);
                $queue->declareQueue();
                $result = $queue->bind($this->exchangeName, $this->routeKey);
                echo 'receiveMessage Channel:', $this->exchangeName, ' queue:', $this->queueName, ' routeKey:', $this->routeKey, PHP_EOL;
                while (TRUE)
                {
                    $queue->consume(function($envelope, $queue)
                    {
                        echo 'recive:', var_dump($envelope), PHP_EOL;
                        $this->process($envelope, $queue, $callback);
                    });
                }
            }
            return $result ? true : false;
        }
        catch (\Exception $e)
        {
            echo 'rabbitmq send message fail:', $e->getMessage(), PHP_EOL;
            return false;
        }
    }

    /**
     * [callback 回调方法]
     * @param  object   $envelope [description]
     * @param  object   $queue    [description]
     * @return function           [description]
     */
    public function process($envelope, $queue, $callback)
    {
        $msg = $envelope->getBody();
        if (!empty($callback) && is_callable($callback))
        {
            $result = call_user_func($callback, $msg);
            if ($result)
            {
                $queue->ack($envelope->getDeliveryTag());
            }
        }
    }

    public function __destruct()
    {
        if (self::$rabbitClient)
        {
            self::$rabbitClient->disconnect();
        }
    }
}
$mq = new RabbitMQ();
