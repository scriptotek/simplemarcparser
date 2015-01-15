<?php namespace Scriptotek\SimpleMarcParser;

require 'vendor/autoload.php';
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;
use Scriptotek\SimpleMarcParser\HoldingsRecord;
use Carbon\Carbon;

class HoldingsRecordTest extends \PHPUnit_Framework_TestCase {

    private function parseRecordData($data)
    {
        $dom = new QuiteSimpleXMLElement('<?xml version="1.0"?>
            <marc:record xmlns:marc="info:lc/xmlns/marcxchange-v1" format="MARC21" type="Holdings">
                ' . $data . '
            </marc:record>');
        $dom->registerXPathNamespaces(array(
            'marc' => 'http://www.loc.gov/MARC21/slim'
        ));

        return new HoldingsRecord($dom);
    }

    public function testMarc001() {
        $out = $this->parseRecordData('
            <marc:controlfield tag="001">12149361x</marc:controlfield>
        ');

        $this->assertEquals('12149361x', $out->id);
    }

    public function testMarc004() {
        $out1 = $this->parseRecordData('
            <marc:controlfield tag="004">841149003</marc:controlfield>
        ');
        $out2 = $this->parseRecordData('');

        $this->assertEquals('841149003', $out1->bibliographic_record);
        $this->assertNull($out2->bibliographic_record);
    }

    public function testMarc009() {
        $out = $this->parseRecordData('
            <marc:controlfield tag="009">kat</marc:controlfield>
        ');

        $this->assertEquals('kat', $out->status);
    }

    public function testMarc852full() {
        $out = $this->parseRecordData('
            <marc:datafield tag="852" ind1=" " ind2=" ">
                <marc:subfield code="a">HIT</marc:subfield>
                <marc:subfield code="b">HIT/BØ</marc:subfield>
                <marc:subfield code="c">BØ</marc:subfield>
                <marc:subfield code="h">633 A</marc:subfield>
                <marc:subfield code="x">Tidligere eier: KJEMIBIB</marc:subfield>
                <marc:subfield code="z">(tapt?)</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('HIT', $out->location);
        $this->assertEquals('HIT/BØ', $out->sublocation);
        $this->assertEquals('BØ', $out->shelvinglocation);
        $this->assertEquals('633 A', $out->callcode);
        $this->assertCount(1, $out->public_notes);
        $this->assertCount(1, $out->nonpublic_notes);
        $this->assertEquals('Tidligere eier: KJEMIBIB', $out->nonpublic_notes[0]);
        $this->assertEquals('(tapt?)', $out->public_notes[0]);
    }

    public function testMarc852minimal() {
        $out = $this->parseRecordData('
            <marc:datafield tag="852" ind1=" " ind2=" ">
                <marc:subfield code="a">HIT</marc:subfield>
                <marc:subfield code="b">HIT/BØ</marc:subfield>
                <marc:subfield code="c">BØ</marc:subfield>
                <marc:subfield code="h">633 A</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('HIT', $out->location);
        $this->assertEquals('HIT/BØ', $out->sublocation);
        $this->assertEquals('BØ', $out->shelvinglocation);
        $this->assertEquals('633 A', $out->callcode);
        $this->assertCount(0, $out->public_notes);
        $this->assertCount(0, $out->nonpublic_notes);
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

        $this->assertEquals(1, count($out->fulltext));
        $ft = $out->fulltext[0];
        $this->assertEquals('NB Digital', $ft['linktext']);
        $this->assertEquals('http://urn.nb.no/URN:NBN:no-nb_digibok_2012071308172', $ft['url']);
        $this->assertEquals('Elektronisk reproduksjon. Tilgjengelig på NBs lesesal', $ft['comment']);
    }

    public function testMarc859f() {

        $out = $this->parseRecordData('
            <marc:datafield tag="859" ind1=" " ind2=" ">
                <marc:subfield code="f">0</marc:subfield>
            </marc:datafield>
        ');
        $this->assertNull($out->use_restrictions);

        foreach (HoldingsRecord::$m859_f as $key => $value) {
            $out = $this->parseRecordData('
                <marc:datafield tag="859" ind1=" " ind2=" ">
                    <marc:subfield code="f">' . $key . '</marc:subfield>
                </marc:datafield>
            ');
            $this->assertEquals($value, $out->use_restrictions);
        }

    }

    public function testMarc859h() {

        foreach (HoldingsRecord::$m859_h as $key => $value) {
            $out = $this->parseRecordData('
                <marc:datafield tag="859" ind1=" " ind2=" ">
                    <marc:subfield code="h">' . $key . '</marc:subfield>
                </marc:datafield>
            ');
            $this->assertEquals($value, $out->circulation_status);
        }

    }

    public function testMarc866() {

        $out = $this->parseRecordData('
            <marc:datafield tag="866" ind1="3" ind2="0">
                <marc:subfield code="a">1(1969/70)-34(1997/99)</marc:subfield>
            </marc:datafield>
        ');
        $this->assertEquals('1(1969/70)-34(1997/99)', $out->holdings);

    }

    public function testMarc876() {

        $out = $this->parseRecordData('
            <marc:datafield tag="876" ind1=" " ind2=" ">
                <marc:subfield code="d">20130620</marc:subfield>
                <marc:subfield code="j">kat</marc:subfield>
                <marc:subfield code="p">050233na0</marc:subfield>
            </marc:datafield>
        ');
        $this->assertEquals(Carbon::create(2013, 6, 20, 0, 0, 0), $out->acquired);
        $this->assertEquals('050233na0', $out->barcode);
    }

    public function testJson()
    {
        $rec1 = $this->parseRecordData('
            <marc:datafield tag="866" ind1="3" ind2="0">
                <marc:subfield code="a">1(1969/70)-34(1997/99)</marc:subfield>
            </marc:datafield>
        ');

        $expected = json_encode(
          array(
            'holdings' => '1(1969/70)-34(1997/99)',
            'fulltext' => array(),
            'nonpublic_notes' => array(),
            'public_notes' => array(),
          )
        );

        $this->assertJsonStringEqualsJsonString($expected, $rec1->toJson());
    }

}