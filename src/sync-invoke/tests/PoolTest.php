<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PoolTest extends TestCase
{

    public function test(): void
    {
        $_this = $this;
        $func  = function () use ($_this) {
            $server = new \Mix\SyncInvoke\Server(9505, true);
            xgo(function () use ($server) {
                $server->start();
            });

            $dialer = new \Mix\SyncInvoke\Client\Dialer();
            $client = $dialer->dial(9505);
            $max    = $client->maxActive * 2;
            $time   = time();
            $chan   = new \Swoole\Coroutine\Channel();
            for ($i = 0; $i < $max; $i++) {
                go(function () use ($client, $chan) {
                    $client->invoke(function () {
                        sleep(2);
                        return [1, 2, 3];
                    });
                    $chan->push(true);
                });
            }
            for ($i = 0; $i < $max; $i++) {
                $chan->pop();
            }
            $duration = time() - $time;
            $_this->assertTrue($duration - 4 < 1 && $duration - 4 >= 0);
            $server->shutdown();
        };
        run($func);
    }

}
