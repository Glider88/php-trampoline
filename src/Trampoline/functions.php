<?php declare(strict_types=1);

namespace Glider88\Trampoline;

/**
 * @template A
 * @template B
 * @param Trampoline<A> $computation
 * @return B
 */
function run(Trampoline $computation): mixed
{
    while (true) {
        if ($computation instanceof Done) {
            return $computation->result;
        }

        if ($computation instanceof Suspend) {
            $computation = ($computation->resume)(...$computation->args);
            continue;
        }

        if ($computation instanceof FlatMap) {
            /** @var Trampoline $next */
            $next = $computation->fa;

            /** @var callable(A): Trampoline $f */
            $f = $computation->f;

            if ($next instanceof Done) {
                $computation = $f($next->result);
                continue;
            }

            if ($next instanceof Suspend) {
                $fa = ($next->resume)(...$next->args);
                $computation = $fa->flatMap($f);
                continue;
            }

            if ($next instanceof FlatMap) {
                $computation = $next->fa->flatMap(
                    static fn($res) => ($next->f)($res)->flatMap($f)
                );
            }
        }
    }
}

/**
 * @template A
 * @param callable(callable): Trampoline<A> $f
 * @param ?callable(list<mixed>): string $keyFn
 * @return callable(mixed ...$args): callable
 */
function memo(callable $f, ?callable $keyFn = null): callable
{
    $keyFn = $keyFn ?? static fn (...$args) => hash('sha256', serialize($args));
    return static function (...$args) use ($f, $keyFn) {
        $cache = [];
        $self = static function (...$args) use (&$self, &$cache, $f, $keyFn): Trampoline {
            $key = $keyFn($args);
            if (isset($cache[$key])) {
                return done($cache[$key]);
            }

            $fun = static function ($result) use (&$cache, $key) {
                $cache[$key] = $result;
                return done($result);
            };

            return $f($self, ...$args)->flatMap($fun);
        };

        return $self(...$args);
    };
}

/**
 * @template A
 * @param list<Trampoline<A>> $list
 * @return Trampoline<list<A>>
 */
function sequence(array $list): Trampoline
{
    /** @var Trampoline<list<A>> $result */
    $result = new Done([]);
    foreach ($list as $t) {
        /** @var Trampoline<A> $t */
        $result = $result->flatMap(
            static fn(array $xs) => $t->map(
                static function ($x) use ($xs) {
                    $xs[] = $x;

                    return $xs;
                }
            )
        );
    }

    return $result;
}

/**
 * @template A
 * @param A $result
 * @return Trampoline<A>
 */
function done(mixed $result): Trampoline
{
    return new Done($result);
}

/**
 * @template A
 * @param callable(mixed ...$args): Trampoline<A> $resume
 * @param mixed ...$args $args
 * @return Trampoline<A>
 */
function suspend(callable $resume, ...$args): Trampoline
{
    return new Suspend($resume, $args);
}

/**
 * @template A
 * @template B
 * @param Trampoline<A> $fa
 * @param callable(A): Trampoline<B> $f
 * @return Trampoline<B>
 */
function flatMap(Trampoline $fa, callable $f): Trampoline
{
    return new FlatMap($fa, $f);
}
