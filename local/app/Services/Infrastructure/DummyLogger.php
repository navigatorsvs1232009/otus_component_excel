<?php

namespace Services\Infrastructure;

use Psr\Log\LoggerInterface;

class DummyLogger implements LoggerInterface
{
    /**
     * Do nothing.
     *
     * @param string $handle
     * @param string $message
     * @param string $level
     *
     * @return bool|void
     */
    public function add($handle, $message, $level = WC_Log_Levels::NOTICE)
    {
    }

    /**
     * Do nothing.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = array())
    {
    }

    /**
     * Do nothing.
     *
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = array())
    {
    }

    /**
     * Do nothing.
     *
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = array())
    {
    }

    /**
     * Do nothing.
     *
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = array())
    {
    }

    /**
     * Do nothing.
     *
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = array())
    {
    }

    /**
     * Do nothing.
     *
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = array())
    {
    }

    /**
     * Do nothing.
     *
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = array())
    {
    }

    /**
     * Do nothing.
     *
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = array())
    {
    }

    /**
     * Do nothing.
     *
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = array())
    {
    }
}