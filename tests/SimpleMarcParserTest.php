<?php namespace Danmichaelo\SimpleMarcParser;

require 'vendor/autoload.php';
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;
use Danmichaelo\SimpleMarcParser\SimpleMarcParser;

class SimpleMarcParserTest extends \PHPUnit_Framework_TestCase {

    private function parseRecordData($data)
    {
        $xml = '<?xml version="1.0"?>
        <srw:searchRetrieveResponse xmlns:srw="http://www.loc.gov/zing/srw/" xmlns:xcql="http://www.loc.gov/zing/cql/xcql/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:marc="info:lc/xmlns/marcxchange-v1" xmlns:marcxml="http://www.loc.gov/MARC21/slim">
            <srw:records>
                <srw:record>
                    <srw:recordData>
                        <marc:record xmlns:marc="info:lc/xmlns/marcxchange-v1" format="MARC21" type="Bibliographic">
                            ' . $data . '
                        </marc:record>
                    </srw:recordData>
                </srw:record>
            </srw:records>
        </srw:searchRetrieveResponse>
        ';

        $dom = new QuiteSimpleXMLElement($xml);
        $dom->registerXPathNamespaces(array(
            'srw' => 'http://www.loc.gov/zing/srw/',
            'marc' => 'http://www.loc.gov/MARC21/slim',
            'd' => 'http://www.loc.gov/zing/srw/diagnostic/'
        ));

        $parser = new SimpleMarcParser();
        return $parser->parse($dom->first('srw:records/srw:record/srw:recordData/marc:record'));
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


    public function testMarc856fulltext() {
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