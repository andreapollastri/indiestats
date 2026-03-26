<?php

namespace Tests\Unit;

use App\Services\EventPayloadSanitizer;
use PHPUnit\Framework\TestCase;

class EventPayloadSanitizerPathTest extends TestCase
{
    public function test_normalize_stored_path_strips_query_and_fragment(): void
    {
        $this->assertSame('/pricing', EventPayloadSanitizer::normalizeStoredPath('/pricing?utm_source=x'));
        $this->assertSame('/a', EventPayloadSanitizer::normalizeStoredPath('/a?b=1#h'));
        $this->assertSame('/x', EventPayloadSanitizer::normalizeStoredPath('/x#section'));
    }

    public function test_normalize_stored_path_empty_becomes_slash(): void
    {
        $this->assertSame('/', EventPayloadSanitizer::normalizeStoredPath(''));
        $this->assertSame('/', EventPayloadSanitizer::normalizeStoredPath('   '));
    }
}
