<?php

namespace spec\carlosV2\DumbsmartRepositories\Exception;

use PhpSpec\ObjectBehavior;

class UnexpectedDocumentTypeExceptionSpec extends ObjectBehavior
{
    function it_is_an_Exception()
    {
        $this->shouldHaveType(\Exception::class);
    }
}
