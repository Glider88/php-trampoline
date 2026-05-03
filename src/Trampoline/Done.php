<?php declare(strict_types=1);

namespace Glider88\Trampoline;

/**
 * @template A
 * @implements Trampoline<A>
 */
readonly class Done extends Trampoline
{
    public function __construct(
        /** @param A $result */
        public mixed $result,
    ) {}
}
