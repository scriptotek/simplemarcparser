<?php namespace Danmichaelo\SimpleMarcParser;

require 'vendor/autoload.php';
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;
use Danmichaelo\SimpleMarcParser\BibliographicParser;

class BibliographicParserTest extends \PHPUnit_Framework_TestCase {

    private function parseRecordData($data)
    {
        $dom = new QuiteSimpleXMLElement('<?xml version="1.0"?>
            <marc:record xmlns:marc="info:lc/xmlns/marcxchange-v1" format="MARC21" type="Bibliographic">
                ' . $data . '
            </marc:record>');
        $dom->registerXPathNamespaces(array(
            'marc' => 'http://www.loc.gov/MARC21/slim'
        ));

        $parser = new BibliographicParser;
        return $parser->parse($dom);
    }

    public function testMarc001() {
        $out = $this->parseRecordData('
            <marc:controlfield tag="001">12149361x</marc:controlfield>
        ');

        $this->assertEquals('12149361x', $out['record_id']);
    }

    public function testMarc020() {
        $out = $this->parseRecordData('
            <marc:datafield tag="020" ind1=" " ind2=" ">
                <marc:subfield code="a">9788243005129 (ib.)</marc:subfield>
                <marc:subfield code="c">Nkr 339.00</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('9788243005129', $out['isbn'][0]);
    }

    public function testMarc082() {
        $out = $this->parseRecordData('
            <marc:datafield tag="082" ind1="0" ind2="4">
                <marc:subfield code="a">576.8</marc:subfield>
                <marc:subfield code="2">23</marc:subfield>
            </marc:datafield>
        ');

        $klass = $out['klass'][0];
        $this->assertEquals('576.8', $klass['kode']);
        $this->assertEquals('dewey', $klass['system']);
    }

    public function testMarc100() {
        $out = $this->parseRecordData('
            <marc:datafield tag="245" ind1="1" ind2="0">
                <marc:subfield code="a">Evolusjon :</marc:subfield>
                <marc:subfield code="b">naturens kulturhistorie</marc:subfield>
                <marc:subfield code="c">Markus Lindholm</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('Evolusjon :', $out['title']);
        $this->assertEquals('naturens kulturhistorie', $out['subtitle']);
    }

}