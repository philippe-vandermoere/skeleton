<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Logger;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SymfonyConsole implements LoggerInterface
{
    protected SymfonyStyle $symfonyStyle;

    public function __construct(SymfonyStyle $symfonyStyle)
    {
        $this->symfonyStyle = $symfonyStyle;
    }

    public function emergency($message, array $context = [])
    {
        $this->log(__METHOD__, $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log(__METHOD__, $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log(__METHOD__, $message, $context);
    }

    public function error($message, array $context = [])
    {
       $this->log(__METHOD__, $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log(__METHOD__, $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log(__METHOD__, $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log(__METHOD__, $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log(__METHOD__, $message, $context);
    }

    public function log($level, $message, array $context = [])
    {
        switch ($level) {
            case 'emergency':
            case 'alert':
            case 'critical':
            case 'error':
            case 'warning':
                $this->symfonyStyle->error($message);
                break;
            case 'notice':
                $this->symfonyStyle->comment($message);
                break;
            case 'info':
                $this->symfonyStyle->success($message);
                break;
            case 'debug':
            default:
                $this->symfonyStyle->write($message);
        }
    }
}
