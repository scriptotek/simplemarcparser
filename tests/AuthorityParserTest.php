<?php namespace Scriptotek\SimpleMarcParser;

require 'vendor/autoload.php';
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;
use Scriptotek\SimpleMarcParser\AuthorityParser;
use Carbon\Carbon;

class AuthorityParserTest extends \PHPUnit_Framework_TestCase {

    // http://sru.bibsys.no/search/authority?version=1.2&operation=searchRetrieve&startRecord=1&maximumRecords=10&query=rec.identifier%3D%22x90061718%22&recordSchema=marcxchange
    // http://sru.bibsys.no/search/authority?version=1.2&operation=searchRetrieve&startRecord=1&maximumRecords=10&query=rec.identifier%3D%22x13038487%22&recordSchema=marcxchange

    private function parseRecordData($data)
    {
        $dom = new QuiteSimpleXMLElement('<?xml version="1.0"?>
            <marc:record xmlns:marc="info:lc/xmlns/marcxchange-v1" format="MARC21" type="Authority">
                ' . $data . '
            </marc:record>');
        $dom->registerXPathNamespaces(array(
            'marc' => 'http://www.loc.gov/MARC21/slim'
        ));

        $parser = new AuthorityParser;
        return $parser->parse($dom);
    }

    public function testMarc001() {
        $out = $this->parseRecordData('
            <marc:controlfield tag="001">12149361x</marc:controlfield>
        ');

        $this->assertEquals('12149361x', $out['id']);
    }

    public function testMarc003() {
        $out = $this->parseRecordData('
           <marc:controlfield tag="003">NO-TrBIB</marc:controlfield>
        ');

        $this->assertEquals('NO-TrBIB', $out['agency']);
    }

    public function testMarc005() {
        $out = $this->parseRecordData('
           <marc:controlfield tag="005">20090407000000.0</marc:controlfield>
        ');

        $this->assertEquals(
            Carbon::create(2009, 4, 7, 0, 0, 0), 
            $out['modified']
        );
    }

    public function testMarc008() {
        $out = $this->parseRecordData('
           <marc:controlfield tag="008">090407n adznnaabn| |a|ana| </marc:controlfield>
        ');

        $this->assertEquals('Other', $out['cataloging']);
        $this->assertEquals(null, $out['vocabulary']);
    }

    // 040 - Cataloging Source (NR)
    public function testMarc040() {
        $out1 = $this->parseRecordData('
           <marc:datafield tag="040" ind1=" " ind2=" ">
              <marc:subfield code="a">NO-OsNB</marc:subfield>
              <marc:subfield code="b">nob</marc:subfield>
              <marc:subfield code="c">NO-TrBIB</marc:subfield>
              <marc:subfield code="f">noraf</marc:subfield>
           </marc:datafield>
        ');

        $this->assertEquals('NO-OsNB', $out1['catalogingAgency']);
        $this->assertEquals('nob', $out1['language']);
        $this->assertEquals('NO-TrBIB', $out1['transcribingAgency']);
        $this->assertNull($out1['modifyingAgency']);
        $this->assertEquals('noraf', $out1['vocabulary']);
    }

    // 100 - Heading-Personal Name (NR)
    public function testMarc100() {
        $out1 = $this->parseRecordData('
           <marc:datafield tag="100" ind1="1" ind2=" ">
              <marc:subfield code="a">Bakke, Dagfinn</marc:subfield>
              <marc:subfield code="d">1933-</marc:subfield>
           </marc:datafield>
        ');
        $out2 = $this->parseRecordData('
           <marc:datafield tag="100" ind1="1" ind2=" ">
              <marc:subfield code="a">Ibsen, Henrik</marc:subfield>
              <marc:subfield code="d">1828-1906</marc:subfield>
           </marc:datafield>
        ');

        $this->assertEquals('person', $out1['class']);
        $this->assertEquals(1933, $out1['birth']);
        $this->assertNull($out1['death']);
        $this->assertEquals('Dagfinn Bakke', $out1['label']);

        $this->assertEquals(1828, $out2['birth']);
        $this->assertEquals(1906, $out2['death']);
    }

    // 150 - Topical Term (NR)
    public function testMarc150() {
        $out1 = $this->parseRecordData('
           <marc:datafield tag="150" ind1=" " ind2=" ">
              <marc:subfield code="a">Fotomikrografi</marc:subfield>
              <marc:subfield code="x">naturvitenskap</marc:subfield>
           </marc:datafield>
        ');

        $this->assertEquals('topicalTerm', $out1['class']);
        $this->assertEquals('Fotomikrografi : naturvitenskap', $out1['label']);
    }

    // 375 - Gender (R)
    public function testMarc375() {
        $out1 = $this->parseRecordData('
           <marc:datafield tag="375" ind1=" " ind2=" ">
              <marc:subfield code="a">male</marc:subfield>
              <marc:subfield code="s">1926</marc:subfield>
              <marc:subfield code="e">1972?</marc:subfield>
           </marc:datafield>
           <marc:datafield tag="375" ind1=" " ind2=" ">
              <marc:subfield code="a">female</marc:subfield>
              <marc:subfield code="s">1972?</marc:subfield>
           </marc:datafield>
        ');

        $this->assertCount(2, $out1['genders']);
        $this->assertEquals('male', $out1['genders'][0]['value']);
        $this->assertEquals('1926', $out1['genders'][0]['from']);
        $this->assertEquals('1972?', $out1['genders'][0]['until']);
        $this->assertEquals('female', $out1['genders'][1]['value']);
        $this->assertEquals('1972?', $out1['genders'][1]['from']);
        $this->assertEquals('female', $out1['gender']);

        $out2 = $this->parseRecordData('');

        $this->assertCount(0, $out2['genders']);
        $this->assertNull($out2['gender']);
    }

}