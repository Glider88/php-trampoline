# Trampoline for PHP

Stack save recursion with trampoline.

Installation:
```shell
composer require glider88/php-trampoline
```

Start:
```shell
bin/re  # first run
```
```shell
bin/up  # start app
```
```shell
bin/unit # run tests
```

Can do this:
```php
/** @return Trampoline<int> */
function ackermann(int $n, int $m): Trampoline
{
    if ($n === 0) {
        return done($m + 1);
    }

    if ($m === 0) {
        return suspend(static fn() => ackermann($n - 1, 1));
    }

    return
        suspend(static fn() => ackermann($n, $m - 1))
            ->flatMap(
                static fn($res) => suspend(static fn() => ackermann($n - 1, $res))
            );
}

$this->assertEquals(7, run(ack(2, 2)));

```

> more examples in **test/TrampolineTest.php**
