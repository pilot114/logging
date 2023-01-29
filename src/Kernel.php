<?php

namespace App;

use Psr\Log\LoggerInterface;

class Kernel
{
    public function __construct(
        protected LoggerInterface $log
    ){}

    public function run(): void
    {
        for($i = 0; $i < 500; $i++) {
            if ($this->probability(5)) $this->error();
            if ($this->probability(20)) $this->warning();
            if ($this->probability(50)) $this->debug();
            usleep(rand(1000, 10000));
        }
    }

    protected function debug(): void
    {
        $message = [
            'login',
            'logout',
            'call service',
        ];
        $this->log->debug(
            $message[rand(0, count($message) - 1)],
        );
    }

    protected function warning(): void
    {
        $message = [
            'deprecated API',
            'poor use',
            'undesirable thing',
        ];
        $this->log->warning(
            $message[rand(0, count($message) - 1)],
        );
    }

    protected function error(): void
    {
        $message = [
            'Code broken: {code}',
            'Service unavailable: {code}',
            'payment fail: {code}',
        ];
        $this->log->error(
            $message[rand(0, count($message) - 1)],
            ['code' => rand(100,200)]
        );
    }

    protected function probability(int $percent): bool
    {
        if ($percent <= 0) {
            return false;
        }
        if ($percent >= 100) {
            return true;
        }
        return rand(0, 100) <= $percent;
    }
}