<?php declare(strict_types=1);

namespace Glider88\Trampoline;

/**
 * @template A
 * @template B
 * @implements Trampoline<B>
 */
readonly class FlatMap extends Trampoline
{
    public function __construct(
        /** @param Trampoline<A> $fa */
        public Trampoline $fa,

        /** @param callable(A): Trampoline<B> $f */
        public mixed $f,
    ) {}
}
