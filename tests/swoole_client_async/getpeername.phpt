--TEST--
swoole_client_async: getsockpeername
--SKIPIF--
<?php require __DIR__ . '/../include/skipif.inc'; ?>
--FILE--
<?php
require __DIR__ . '/../include/bootstrap.php';

$pm = new ProcessManager;
$pm->parentFunc = function ($pid) {
    $cli = new \Swoole\Async\Client(SWOOLE_SOCK_UDP);

    $cli->on("connect", function (\Swoole\Async\Client $cli) {
        Assert::true($cli->isConnected());
        $cli->send("test");
    });

    $cli->on("receive", function (\Swoole\Async\Client $cli, $data) {
        $i = $cli->getpeername();
        Assert::assert($i !== false);
        $cli->send('shutdown');
        $cli->close();
    });

    $cli->on("close", function (\Swoole\Async\Client $cli) {
        echo "SUCCESS\n";
    });

    $r = $cli->connect(UDP_SERVER_HOST, UDP_SERVER_PORT, 1);
    Assert::assert($r);
    Swoole\Event::wait();
};

$pm->childFunc = function () use ($pm) {
    $serv = new \Swoole\Server(UDP_SERVER_HOST, UDP_SERVER_PORT, SWOOLE_BASE, SWOOLE_SOCK_UDP);
    $serv->set(["worker_num" => 1, 'log_file' => '/dev/null']);
    $serv->on("WorkerStart", function (\Swoole\Server $serv) use ($pm) {
        $pm->wakeup();
    });
    $serv->on("Packet", function (\Swoole\Server $serv, $data, $clientInfo) {
        if (trim($data) == 'shutdown') {
            $serv->shutdown();
            return;
        }
        $serv->sendto($clientInfo['address'], $clientInfo['port'], $data);
    });
    $serv->start();
};

$pm->childFirst();
$pm->run();
?>
--EXPECT--
SUCCESS
