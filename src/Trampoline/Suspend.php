<?php declare(strict_types=1);

namespace Glider88\Trampoline;

/**
 * @template A
 * @implements Trampoline<A>
 */
readonly class Suspend extends Trampoline
{
    public function __construct(
        /** @var callable(mixed ...$args): Trampoline<A> */
        public mixed $resume,

        /** @var list<mixed> */
        public array $args,
    ) {}
}
