<?php

namespace Scriptotek\SimpleMarcParser;

use PHPUnit\Framework\TestCase;

class RecordTest extends TestCase
{
    public function testIsset()
    {
        $rec = new Record();
        $rec->key = 'value';

        $this->assertFalse(isset($rec->someRandomStuff));
        $this->assertTrue(isset($rec->key));
        $this->assertEquals('value', $rec->key);
    }

    public function testSerializations()
    {
        $rec = new Record();
        $rec->key = 'value';

        $this->assertEquals(array('key' => 'value'), $rec->toArray());
        $this->assertJsonStringEqualsJsonString(json_encode(array('key' => 'value')), $rec->toJson());
    }

    public function testMagicMethods()
    {
        $rec = new Record();
        $rec->lalala = 'humdidum';

        $this->assertTrue(isset($rec->lalala));
        $this->assertFalse(isset($rec->humdidum));

        unset($rec->lalala);
        $this->assertFalse(isset($rec->lalala));
    }
}
