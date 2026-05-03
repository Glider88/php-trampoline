<?php declare(strict_types=1);

namespace Glider88\Trampoline;

/** @template A */
readonly abstract class Trampoline
{
    /**
     * @template B
     * @param callable(A): B $f
     * @return Trampoline<B>
     */
    public function map(callable $f): self
    {
        return $this->flatMap(static fn($_) => done($f($_)));
    }

    /**
     * @template B
     * @param callable(A): Trampoline<B> $f
     * @return Trampoline<B>
     */
    public function flatMap(callable $f): self
    {
        return new FlatMap($this, $f);
    }
}
