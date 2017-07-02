<?php

namespace Scriptotek\SimpleMarcParser;

require 'vendor/autoload.php';
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    // http://sru.bibsys.no/search/authority?version=1.2&operation=searchRetrieve&startRecord=1&maximumRecords=10&query=rec.identifier%3D%22x90061718%22&recordSchema=marcxchange
    // http://sru.bibsys.no/search/authority?version=1.2&operation=searchRetrieve&startRecord=1&maximumRecords=10&query=rec.identifier%3D%22x13038487%22&recordSchema=marcxchange

    private function parseRecordData($data)
    {
        $dom = new QuiteSimpleXMLElement('<?xml version="1.0"?>
            <marc:record xmlns:marc="info:lc/xmlns/marcxchange-v1" format="MARC21">
                ' . $data . '
            </marc:record>');
        $dom->registerXPathNamespaces(array(
            'marc' => 'http://www.loc.gov/MARC21/slim',
        ));

        $parser = new Parser();

        return $parser->parse($dom);
    }

    public function testBibliographicRecord()
    {
        $out = $this->parseRecordData('
            <marc:leader>99999 ai a22999997c 4500</marc:leader>
        ');

        $this->assertInstanceOf('Scriptotek\SimpleMarcParser\BibliographicRecord', $out);
    }

    public function testAuthorityRecord()
    {
        $out = $this->parseRecordData('
            <marc:leader>99999 zi a22999997c 4500</marc:leader>
        ');

        $this->assertInstanceOf('Scriptotek\SimpleMarcParser\AuthorityRecord', $out);
    }

    public function testHoldingsRecord()
    {
        $out = $this->parseRecordData('
            <marc:leader>99999 xi a22999997c 4500</marc:leader>
        ');

        $this->assertInstanceOf('Scriptotek\SimpleMarcParser\HoldingsRecord', $out);
    }

    /**
     * @expectedException Scriptotek\SimpleMarcParser\ParserException
     */
    public function testInvalidRecordType()
    {
        $out = $this->parseRecordData('
            <marc:leader>99999 qi a22999997c 4500</marc:leader>
        ');
    }

    public function testSimpleXmlElement()
    {
        $dom = new \SimpleXMLElement('<?xml version="1.0"?>
            <record xmlns="http://www.loc.gov/MARC21/slim">
              <leader>99999 ai a22999997c 4500</leader>
            </record>');
        $dom->registerXPathNamespace('marc', 'http://www.loc.gov/MARC21/slim');

        $parser = new Parser();
        $out = $parser->parse($dom);
        $this->assertInstanceOf('Scriptotek\SimpleMarcParser\BibliographicRecord', $out);
    }
}
