<?php

namespace Convenia\Pigeon\Tests\Unit;

use Convenia\Pigeon\Facade\Pigeon;
use Convenia\Pigeon\Tests\TestCase;

class RpcTest extends TestCase
{
    public function test_testee()
    {
        Pigeon::fake();

        $responseQueue = Pigeon::routing('rpc.')->rpc(['ping']);
        Pigeon::queue($responseQueue)->callback(function ($message) {
        })->consume(5, false);

        $responseQueue2 = Pigeon::routing('rpc2.')
            ->bind('teste2')
            ->rpc(['ping2']);

        Pigeon::queue($responseQueue2)->callback(function ($message) {
        })->consume(5, false);

        Pigeon::assertRpc('rpc.', ['ping'], ['pong']);
        Pigeon::assertRpc('rpc2.', ['ping2'], ['pong2']);
    }
}
