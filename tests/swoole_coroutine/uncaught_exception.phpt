--TEST--
swoole_coroutine: uncaught exception
--SKIPIF--
<?php require __DIR__ . '/../include/skipif.inc'; ?>
--FILE--
<?php
require __DIR__ . '/../include/bootstrap.php';

use Swoole\Process;

$proc = new Process(function () {
    $statusCode = 0;
    Co\run(function () use (&$statusCode) {
        try {
            throw new \RuntimeException('GG');
        } catch (\Swoole\ExitException $e) {
            $statusCode = $e->getStatus();
        }
    });
    var_dump($statusCode);
});

$proc->start();

$result = Process::wait();
Assert::eq($result['code'], 255);

echo "DONE\n";
?>
--EXPECTF--
Fatal error: Uncaught RuntimeException: GG in %s:%d
Stack trace:
#0 {main}
  thrown in %s on line %d
DONE
