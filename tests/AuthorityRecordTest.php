<?php

namespace Scriptotek\SimpleMarcParser;

require 'vendor/autoload.php';
use Carbon\Carbon;
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;
use PHPUnit\Framework\TestCase;

class AuthorityRecordTest extends TestCase
{
    // http://sru.bibsys.no/search/authority?version=1.2&operation=searchRetrieve&startRecord=1&maximumRecords=10&query=rec.identifier%3D%22x90061718%22&recordSchema=marcxchange
    // http://sru.bibsys.no/search/authority?version=1.2&operation=searchRetrieve&startRecord=1&maximumRecords=10&query=rec.identifier%3D%22x13038487%22&recordSchema=marcxchange

    private function parseRecordData($data)
    {
        $dom = new QuiteSimpleXMLElement('<?xml version="1.0"?>
            <marc:record xmlns:marc="info:lc/xmlns/marcxchange-v1" format="MARC21" type="Authority">
                ' . $data . '
            </marc:record>');
        $dom->registerXPathNamespaces(array(
            'marc' => 'http://www.loc.gov/MARC21/slim',
        ));

        return new AuthorityRecord($dom);
    }

    public function testEmptyRecord()
    {
        $rec = new AuthorityRecord();
        $this->assertNull($rec->id);
    }

    public function testMarc001()
    {
        $rec = $this->parseRecordData('
            <marc:controlfield tag="001">12149361x</marc:controlfield>
        ');

        $this->assertEquals('12149361x', $rec->id);
    }

    public function testMarc003()
    {
        $rec = $this->parseRecordData('
           <marc:controlfield tag="003">NO-TrBIB</marc:controlfield>
        ');

        $this->assertEquals('NO-TrBIB', $rec->agency);
    }

    public function testMarc005()
    {
        $rec = $this->parseRecordData('
           <marc:controlfield tag="005">20090407000000.0</marc:controlfield>
        ');

        $this->assertEquals(
            Carbon::create(2009, 4, 7, 0, 0, 0),
            $rec->modified
        );
    }

    public function testMarc008()
    {
        $rec = $this->parseRecordData('
           <marc:controlfield tag="008">090407n adznnaabn| |a|ana| </marc:controlfield>
        ');

        $this->assertEquals('Other', $rec->cataloging);
        $this->assertEquals(null, $rec->vocabulary);
    }

    // 040 - Cataloging Source (NR)
    public function testMarc040()
    {
        $rec1 = $this->parseRecordData('
           <marc:datafield tag="040" ind1=" " ind2=" ">
              <marc:subfield code="a">NO-OsNB</marc:subfield>
              <marc:subfield code="b">nob</marc:subfield>
              <marc:subfield code="c">NO-TrBIB</marc:subfield>
              <marc:subfield code="f">noraf</marc:subfield>
           </marc:datafield>
        ');

        $this->assertEquals('NO-OsNB', $rec1->catalogingAgency);
        $this->assertEquals('nob', $rec1->language);
        $this->assertEquals('NO-TrBIB', $rec1->transcribingAgency);
        $this->assertNull($rec1->modifyingAgency);
        $this->assertEquals('noraf', $rec1->vocabulary);
    }

    // 100, 400 - Person
    public function testPerson()
    {
        $rec1 = $this->parseRecordData('
           <marc:datafield tag="100" ind1="1" ind2=" ">
              <marc:subfield code="a">Bakke, Dagfinn</marc:subfield>
              <marc:subfield code="d">1933-</marc:subfield>
           </marc:datafield>
          <marc:datafield tag="400" ind1="1" ind2=" ">
              <marc:subfield code="a">Rishøi, Ingvild Hedemann</marc:subfield>
          </marc:datafield>
          <marc:datafield tag="400" ind1="1" ind2=" ">
              <marc:subfield code="a">Hedemann Rishøi, Ingvild</marc:subfield>
          </marc:datafield>
        ');
        $rec2 = $this->parseRecordData('
           <marc:datafield tag="100" ind1="1" ind2=" ">
              <marc:subfield code="a">Ibsen, Henrik</marc:subfield>
              <marc:subfield code="d">1828-1906</marc:subfield>
           </marc:datafield>
        ');

        $this->assertEquals('person', $rec1->class);
        $this->assertEquals(1933, $rec1->birth);
        $this->assertNull($rec1->death);
        $this->assertEquals('Dagfinn Bakke', $rec1->label);
        $this->assertCount(2, $rec1->altLabels);
        $this->assertEquals('Rishøi, Ingvild Hedemann', $rec1->altLabels[0]);
        $this->assertEquals('Hedemann Rishøi, Ingvild', $rec1->altLabels[1]);

        $this->assertEquals(1828, $rec2->birth);
        $this->assertEquals(1906, $rec2->death);
    }

    // 110, 410 - Corporation
    public function testCorporation()
    {
        $rec1 = $this->parseRecordData('
          <marc:datafield tag="110" ind1="2" ind2=" ">
            <marc:subfield code="a">Universitetsbiblioteket i Oslo</marc:subfield>
          </marc:datafield>
          <marc:datafield tag="410" ind1=" " ind2=" ">
            <marc:subfield code="a">UBO</marc:subfield>
          </marc:datafield>
          <marc:datafield tag="410" ind1=" " ind2=" ">
            <marc:subfield code="a">Royal University Library (Oslo)</marc:subfield>
            <marc:subfield code="q">Oslo</marc:subfield>
          </marc:datafield>
          <marc:datafield tag="410" ind1=" " ind2=" ">
            <marc:subfield code="a">University of Oslo</marc:subfield>
            <marc:subfield code="b">Library</marc:subfield>
          </marc:datafield>
        ');

        $this->assertEquals('corporation', $rec1->class);
        $this->assertEquals('Universitetsbiblioteket i Oslo', $rec1->label);
        $this->assertCount(3, $rec1->altLabels);
        $this->assertContains('UBO', $rec1->altLabels);
        $this->assertContains('Royal University Library (Oslo)', $rec1->altLabels);
        $this->assertContains('University of Oslo : Library', $rec1->altLabels);
    }

    // 111, 411 - Meeting
    public function testMeeting()
    {
        $rec1 = $this->parseRecordData('
          <marc:datafield tag="111" ind1="2" ind2=" ">
            <marc:subfield code="a">VM på ski</marc:subfield>
          </marc:datafield>
          <marc:datafield tag="411" ind1=" " ind2=" ">
            <marc:subfield code="a">Ski-VM</marc:subfield>
          </marc:datafield>
          <marc:datafield tag="411" ind1=" " ind2=" ">
            <marc:subfield code="a">Verdensmesterskapet på ski</marc:subfield>
          </marc:datafield>
          <marc:datafield tag="411" ind1=" " ind2=" ">
            <marc:subfield code="a">Osl2011</marc:subfield>
          </marc:datafield>
          <marc:datafield tag="411" ind1=" " ind2=" ">
            <marc:subfield code="a">Oslo2o11</marc:subfield>
          </marc:datafield>
          <marc:datafield tag="411" ind1=" " ind2=" ">
            <marc:subfield code="a">FIS Nordic World Ski Champions</marc:subfield>
          </marc:datafield>
        ');

        $this->assertEquals('meeting', $rec1->class);
        $this->assertEquals('VM på ski', $rec1->label);
        $this->assertCount(5, $rec1->altLabels);
        $this->assertContains('Ski-VM', $rec1->altLabels);
    }

    // 150 - Topical Term (NR)
    public function testMarc150()
    {
        $rec1 = $this->parseRecordData('
           <marc:datafield tag="150" ind1=" " ind2=" ">
              <marc:subfield code="a">Fotomikrografi</marc:subfield>
              <marc:subfield code="x">naturvitenskap</marc:subfield>
           </marc:datafield>
        ');

        $this->assertEquals('topicalTerm', $rec1->class);
        $this->assertEquals('Fotomikrografi : naturvitenskap', $rec1->label);
    }

    // 375 - Gender (R)
    public function testMarc375()
    {
        $rec1 = $this->parseRecordData('
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

        $this->assertCount(2, $rec1->genders);
        $this->assertEquals('male', $rec1->genders[0]['value']);
        $this->assertEquals('1926', $rec1->genders[0]['from']);
        $this->assertEquals('1972?', $rec1->genders[0]['until']);
        $this->assertEquals('female', $rec1->genders[1]['value']);
        $this->assertEquals('1972?', $rec1->genders[1]['from']);
        $this->assertEquals('female', $rec1->gender);

        $rec2 = $this->parseRecordData('');

        $this->assertCount(0, $rec2->genders);
        $this->assertNull($rec2->gender);
    }

    public function testJson()
    {
        $rec1 = $this->parseRecordData('
          <marc:datafield tag="400" ind1="1" ind2=" ">
            <marc:subfield code="a">Rishøi, Ingvild Hedemann</marc:subfield>
          </marc:datafield>
          <marc:datafield tag="400" ind1="1" ind2=" ">
            <marc:subfield code="a">Hedemann Rishøi, Ingvild</marc:subfield>
          </marc:datafield>
        ');

        $expected = json_encode(
          array(
            'genders' => array(),
            'altLabels' => array(
              'Rishøi, Ingvild Hedemann',
              'Hedemann Rishøi, Ingvild',
            ),
          )
        );

        $this->assertJsonStringEqualsJsonString($expected, $rec1->toJson());
    }
}
