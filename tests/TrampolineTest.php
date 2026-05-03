<?php declare(strict_types=1);

namespace Tests\Glider88;

use Glider88\Trampoline\Trampoline;
use function Glider88\Trampoline\{run, suspend, done, flatMap, sequence, memo};
use PHPUnit\Framework\TestCase;

class TrampolineTest extends TestCase
{
    public function testSummator(): void
    {
        /** @return Trampoline<int> */
        function summator(int $n): Trampoline
        {
            if ($n === 1) {
                return done(1);
            }

            return flatMap(
                fa: suspend(summator(...), $n - 1),
                f: static fn($_) => done($n + $_)
            );
        }

        $this->assertEquals(1 + 2 + 3, run(summator(3)));
        $this->assertEquals(500_500,   run(summator(1000)));
    }

    public function testEvenOdd(): void
    {
        /** @return Trampoline<bool> */
        function even(int $n): Trampoline
        {
            if ($n === 0) {
                return done(true);
            }

            return suspend(odd(...), $n - 1);
        }

        /** @return Trampoline<bool> */
        function odd(int $n): Trampoline
        {
            if ($n === 0) {
                return done(false);
            }

            return suspend(even(...), $n - 1);
        }

        $this->assertTrue(run(odd(999)));
        $this->assertTrue(run(even(1000)));
    }

    public function testFibonacci(): void
    {
        /** @return Trampoline<int> */
        function fibonacci(int $n): Trampoline
        {
            if ($n <= 1) {
                return done($n);
            }

            $first = suspend(fibonacci(...), $n - 2);
            $second =
                static fn($x)
                   => suspend(fibonacci(...), $n - 1)->map(static fn($y) => $x + $y);

            return $first->flatMap($second);
        }

        $this->assertEquals(55, run(fibonacci(10)));
        $this->assertEquals(6765, run(fibonacci(20)));
    }

    public function testFibonacciMemoized(): void
    {
        /** @var callable(int $n): Trampoline<int> $fib */
        $fib = memo(
            static function (callable $self, int $n): Trampoline {
                if ($n <= 1) {
                    return done($n);
                }

                $first = suspend($self(...), $n - 2);
                $second =
                    static fn($x)
                        => suspend($self(...), $n - 1)->map(static fn($y) => $x + $y);

                return $first->flatMap($second);
            }
        );

        $this->assertEquals(832_040, run($fib(30)));
    }

    public function testMapTree(): void
    {
        /**
         * @param Tree $tree
         * @param callable(string): string $f
         * @return Trampoline<Tree>
         */
        function map_tree(Tree $tree, callable $f): Trampoline
        {
            if ($tree instanceof Leaf) {
                return done(new Leaf($f($tree->label)));
            }

            if ($tree instanceof Node) {
                /** @var list<Trampoline<Tree>> $ltt */
                $ltt = [];
                foreach ($tree->children as $child) {
                    $ltt[] = suspend(map_tree(...), $child, $f);
                }

                /** @var Trampoline<list<Tree>> $tlt */
                $tlt = sequence($ltt);

                return $tlt->map(static fn($_) => new Node($f($tree->label), $_));
            }
        }

        /*
         * root
         *   A
         *     C
         *     D
         *       F
         *   B
         *     E
         *       G
         *       F
         */

        $tree = new Node('root', [
            new Node('A', [
                new Leaf('C'),
                new Node('D', [
                    new Leaf('F'),
                ]),
            ]),
            new Node('B', [
                new Node('E', [
                    new Leaf('G'),
                    new Leaf('F'),
                ]),
            ]),
        ]);

        /** @var Tree $mapped */
        $mapped = run(map_tree($tree, static fn(string $label) => "$label+"));

        $this->assertEquals('F+', $mapped->children[0]->children[1]->children[0]->label);
        $this->assertEquals('E+', $mapped->children[1]->children[0]->label);
    }

    public function testAckermann(): void
    {
        /** @return Trampoline<int> */
        function ack(int $m, int $n): Trampoline
        {
            if ($m === 0) {
                return done($n + 1);
            }

            if ($n === 0) {
                return suspend(ack(...), $m - 1, 1);
            }

            $secondArg = suspend(ack(...), $m, $n - 1);
            $firstArgFn = static fn($res) => suspend(ack(...), $m - 1, $res);

            return $secondArg->flatMap($firstArgFn);
        }

        $this->assertEquals(7, run(ack(2, 2)));
    }

    public function testAckermannMemoized(): void
    {
        /** @var callable(int $m, int $n): Trampoline<int> $ack */
        $ack = memo(
            static function (callable $self, int $m, int $n): Trampoline {
                if ($m === 0) {
                    return done($n + 1);
                }

                if ($n === 0) {
                    return suspend($self(...), $m - 1, 1);
                }

                $secondArg = suspend($self(...), $m, $n - 1);
                $firstArgFn = static fn($res) => suspend($self(...), $m - 1, $res);

                return $secondArg->flatMap($firstArgFn);
            }
        );

        $this->assertEquals(2045, run($ack(3, 8)));
    }
}

interface Tree {}
class Leaf implements Tree { public function __construct(public string $label) {} }
class Node implements Tree { public function __construct(public string $label, public array $children) {} }
