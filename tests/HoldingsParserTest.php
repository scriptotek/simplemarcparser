<?php namespace Danmichaelo\SimpleMarcParser;

require 'vendor/autoload.php';
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;
use Danmichaelo\SimpleMarcParser\HoldingsParser;

class HoldingsParserTest extends \PHPUnit_Framework_TestCase {

    private function parseRecordData($data)
    {
        $dom = new QuiteSimpleXMLElement('<?xml version="1.0"?>
            <marc:record xmlns:marc="info:lc/xmlns/marcxchange-v1" format="MARC21" type="Holdings">
                ' . $data . '
            </marc:record>');
        $dom->registerXPathNamespaces(array(
            'marc' => 'http://www.loc.gov/MARC21/slim'
        ));

        $parser = new HoldingsParser;
        return $parser->parse($dom);
    }

    public function testMarc001() {
        $out = $this->parseRecordData('
            <marc:controlfield tag="001">12149361x</marc:controlfield>
        ');

        $this->assertEquals('12149361x', $out['id']);
    }

    public function testMarc852() {
        $out = $this->parseRecordData('
            <marc:datafield tag="852" ind1=" " ind2=" ">
                <marc:subfield code="a">HIT</marc:subfield>
                <marc:subfield code="b">HIT/BØ</marc:subfield>
                <marc:subfield code="c">BØ</marc:subfield>
                <marc:subfield code="h">633 A</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('HIT', $out['location']);
        $this->assertEquals('HIT/BØ', $out['sublocation']);
        $this->assertEquals('BØ', $out['shelvinglocation']);
        $this->assertEquals('633 A', $out['callcode']);
    }

    public function testMarc856() {
        $out = $this->parseRecordData('
            <marc:datafield tag="856" ind1="4" ind2="0">
                <marc:subfield code="3">Fulltekst</marc:subfield>
                <marc:subfield code="u">http://urn.nb.no/URN:NBN:no-nb_digibok_2012071308172</marc:subfield>
                <marc:subfield code="y">NB Digital</marc:subfield>
                <marc:subfield code="z">Elektronisk reproduksjon. Tilgjengelig på NBs lesesal</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals(1, count($out['fulltext']));
        $ft = $out['fulltext'][0];
        $this->assertEquals('NB Digital', $ft['provider']);
        $this->assertEquals('http://urn.nb.no/URN:NBN:no-nb_digibok_2012071308172', $ft['url']);
        $this->assertEquals('Elektronisk reproduksjon. Tilgjengelig på NBs lesesal', $ft['comment']);
    }

}