<?php

namespace Tests\Support;

use App\Support\Container;
use PHPUnit\Framework\TestCase;

class ContainerSetContainerTest extends TestCase
{
    public function test_set_container_replaces_instance(): void
    {
        $original = Container::getContainer();
        $fresh = new Container();
        Container::setContainer($fresh);
        $this->assertSame($fresh, Container::getContainer());

        // restore original so other tests are unaffected
        Container::setContainer($original);
    }
}
