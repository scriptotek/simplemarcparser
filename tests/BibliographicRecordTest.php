<?php namespace Scriptotek\SimpleMarcParser;

require 'vendor/autoload.php';
use Danmichaelo\QuiteSimpleXMLElement\QuiteSimpleXMLElement;
use Carbon\Carbon;

class BibliographicRecordTest extends \PHPUnit_Framework_TestCase {

    private function parseRecordData($data)
    {
        $dom = new QuiteSimpleXMLElement('<?xml version="1.0"?>
            <marc:record xmlns:marc="info:lc/xmlns/marcxchange-v1" format="MARC21" type="Bibliographic">
                ' . $data . '
            </marc:record>');
        $dom->registerXPathNamespaces(array(
            'marc' => 'http://www.loc.gov/MARC21/slim'
        ));

        return new BibliographicRecord($dom);
    }

    public function testMaterialIsPrintedBook() {
        $out = $this->parseRecordData('
            <marc:leader>99999 am a2299999 c 4500</marc:leader>
            <marc:controlfield tag="001">131381679</marc:controlfield>
            <marc:controlfield tag="007">ta</marc:controlfield>
            <marc:controlfield tag="008">130916s2011                  000 u|eng d</marc:controlfield>
        ');

        $this->assertEquals('Book', $out->material);
        $this->assertFalse($out->electronic);
    }

    public function testMaterialIsElectronicBook() {
        $out = $this->parseRecordData('
            <marc:leader>99999 am a22999997c 4500</marc:leader>
            <marc:controlfield tag="001">133788229</marc:controlfield>
            <marc:controlfield tag="007">cr |||||||||||</marc:controlfield>
            <marc:controlfield tag="008">140313s2011            o     000 u|eng d</marc:controlfield>
        ');

        $this->assertEquals('Book', $out->material);
        $this->assertTrue($out->electronic);
    }

    public function testMaterialIsThesis() {
        $out = $this->parseRecordData('
            <marc:leader>99999cam a22999997c 4500</marc:leader>
            <marc:controlfield tag="001">980016495</marc:controlfield>
            <marc:controlfield tag="007">ta</marc:controlfield>
            <marc:controlfield tag="008">980326s1984    xx#||||||m   |000|u|eng|d</marc:controlfield>
        ');

        $this->assertEquals('Thesis', $out->material);
        $this->assertFalse($out->electronic);
    }

    public function testMaterialIsMusicCD() {
        // LDR/06="j" : Musical sound recording
        // LDR/07="a" : Monographic component part
        // 007/0="s" : Sound recording
        // 007/1="d" : Sound disc
        $out = $this->parseRecordData('
            <marc:leader>99999cja a2299999 c 4500</marc:leader>
            <marc:controlfield tag="001">040262626</marc:controlfield>
            <marc:controlfield tag="007">sd f||||||||||</marc:controlfield>
            <marc:controlfield tag="008">040209s20uu    xx#|||| |    |||||||und|d</marc:controlfield>
        ');

        $this->assertEquals('Music CD track', $out->material);
        $this->assertFalse($out->electronic);
    }

    public function testMaterialIsSoundCassette() {
        // LDR/06="j" : Musical sound recording
        // LDR/07="m" : Monograph/Item
        // 007/0="s" : Sound recording
        // 007/1="s" : Sound cassette
        $out = $this->parseRecordData('
            <marc:leader>99999cjm a2299999 c 4500</marc:leader>
            <marc:controlfield tag="001">943188679</marc:controlfield>
            <marc:controlfield tag="007">ss |||||||||||</marc:controlfield>
            <marc:controlfield tag="008">091211s1993    xx#|||| |    |||||||nno| </marc:controlfield>
        ');

        $this->assertEquals('Sound cassette', $out->material);
    }

    public function testMaterialIsSoundCassetteTrack() {
        // LDR/06="j" : Musical sound recording
        // LDR/07="a" : Monographic component part
        // 007/0="s" : Sound recording
        // 007/1="s" : Sound cassette
        $out = $this->parseRecordData('
            <marc:leader>99999cja a2299999 c 4500</marc:leader>
            <marc:controlfield tag="001">030323959</marc:controlfield>
            <marc:controlfield tag="007">ss |||||||||||</marc:controlfield>
            <marc:controlfield tag="008">030214s1998    xx#|||| |    |||||||nno|d</marc:controlfield>
        ');

        $this->assertEquals('Sound cassette track', $out->material);
    }

    public function testMaterialIsPrintedPeriodical() {
        $out = $this->parseRecordData('
            <marc:leader>99999 as a2299999 c 4500</marc:leader>
            <marc:controlfield tag="001">981315402</marc:controlfield>
            <marc:controlfield tag="007">ta</marc:controlfield>
            <marc:controlfield tag="008">130625uuuuuuuuu      p       |    0eng d</marc:controlfield>
        ');

        $this->assertEquals('Periodical', $out->material);
        $this->assertFalse($out->electronic);
    }

    public function testMaterialIsElectronicPeriodical() {
        $out = $this->parseRecordData('
            <marc:leader>99999 as a22999997c 4500</marc:leader>
            <marc:controlfield tag="001">080880762</marc:controlfield>
            <marc:controlfield tag="007">cr |||||||||||</marc:controlfield>
            <marc:controlfield tag="008">110404uuuuuuuuu      p o     |    0eng d</marc:controlfield>
        ');

        $this->assertEquals('Periodical', $out->material);
        $this->assertTrue($out->electronic);
    }

    public function testMaterialIsPrintedSeries() {
        $out = $this->parseRecordData('
            <marc:leader>99999 as a2299999 c 4500</marc:leader>
            <marc:controlfield tag="001">801065968</marc:controlfield>
            <marc:controlfield tag="007">ta</marc:controlfield>
            <marc:controlfield tag="008">051128uuuuuuuuu      m       |    0mul d</marc:controlfield>
        ');

        $this->assertEquals('Series', $out->material);
        $this->assertFalse($out->electronic);
    }

    public function testMaterialIsSheetMusic() {
        $out = $this->parseRecordData('
            <marc:leader>99999 cm a2299999 c 4500</marc:leader>
            <marc:controlfield tag="001">890302278</marc:controlfield>
            <marc:controlfield tag="007">ta</marc:controlfield>
            <marc:controlfield tag="008">140625s1955    |||||a|||    |||||||und|d</marc:controlfield>
        ');

        $this->assertEquals('Sheet music', $out->material);
        $this->assertFalse($out->electronic);
    }

    public function testMaterialIsKit() {
        $out = $this->parseRecordData('
            <marc:leader>99999 oa a2299999 c 4500</marc:leader>
            <marc:controlfield tag="001">020299729</marc:controlfield>
            <marc:controlfield tag="007">o|</marc:controlfield>
            <marc:controlfield tag="008">020205s1986    ||||||| |    |||||b|eng|d</marc:controlfield>
        ');

        $this->assertEquals('Kit', $out->material);
        $this->assertFalse($out->electronic);
    }

    public function testMaterialIsNewspaper() {
        // 007/1-2=ta : regular print, 008/21=n : newspaper
        $out = $this->parseRecordData('
            <marc:leader>99999cas a2299999 c 4500</marc:leader>
            <marc:controlfield tag="001">930112849</marc:controlfield>
            <marc:controlfield tag="007">ta</marc:controlfield>
            <marc:controlfield tag="008">141210c18689999xx#d||n||    ||||||0nob|d</marc:controlfield>
        ');

        $this->assertEquals('Newspaper', $out->material);
    }

    public function testMaterialIsNewspaperOnMicroform() {
        // 007/0=h : microform, 008/21=n : newspaper
        $out = $this->parseRecordData('
            <marc:leader>99999cas a2299999 c 4500</marc:leader>
            <marc:controlfield tag="001">022446451</marc:controlfield>
            <marc:controlfield tag="007">hu ||||||||||</marc:controlfield>
            <marc:controlfield tag="008">081218uuuuuuuuuxx#|||n||    ||||||0rus|d</marc:controlfield>
        ');

        $this->assertEquals('Newspaper on microform', $out->material);
    }

    public function testMarc001() {
        $out = $this->parseRecordData('
            <marc:controlfield tag="001">12149361x</marc:controlfield>
        ');

        $this->assertEquals('12149361x', $out->id);
    }

    public function testModified() {
        $out1 = $this->parseRecordData('
            <marc:controlfield tag="005">19970411</marc:controlfield>
        ');
        $out2 = $this->parseRecordData('
            <marc:controlfield tag="005">19940223151047.0</marc:controlfield>
        ');

        $this->assertEquals(Carbon::create(1997, 4, 11, 0, 0, 0), $out1->modified);
        $this->assertEquals(Carbon::create(1994, 2, 23, 15, 10, 47), $out2->modified);
    }

    public function testCreated() {
        $out1 = $this->parseRecordData('
            <marc:controlfield tag="008">970411s1996 000 u|eng d</marc:controlfield>
        ');
        $out2 = $this->parseRecordData('
            <marc:controlfield tag="008">700101s1996 000 u|eng d</marc:controlfield>
        ');
        $out3 = $this->parseRecordData('
            <marc:controlfield tag="008">690101s1996 000 u|eng d</marc:controlfield>
        ');

        $this->assertEquals(Carbon::create(1997, 4, 11, 0, 0, 0), $out1->created);
        $this->assertEquals(Carbon::create(1970, 1, 1, 0, 0, 0), $out2->created);
        $this->assertEquals(Carbon::create(2069, 1, 1, 0, 0, 0), $out3->created);
    }

    public function testMarc010() {
        $out = $this->parseRecordData('
            <marc:datafield tag="010" ind1=" " ind2=" ">
                <marc:subfield code="a">  2012011618</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('2012011618', $out->lccn);
    }

    public function testIsbn() {
        // Should strip off comments, but leave hyphens
        $out1 = $this->parseRecordData('
            <marc:datafield tag="020" ind1=" " ind2=" ">
                <marc:subfield code="a">978-8243005129 (ib.)</marc:subfield>
                <marc:subfield code="c">Nkr 339.00</marc:subfield>
            </marc:datafield>
        ');
        $out2 = $this->parseRecordData('');

        $this->assertCount(1, $out1->isbns);
        $this->assertEquals('978-8243005129', $out1->isbns[0]);

        $this->assertCount(0, $out2->isbns);
    }

    public function testIssn() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="022" ind1=" " ind2=" ">
                <marc:subfield code="a">1089-7690</marc:subfield>
            </marc:datafield>
        ');
        $out2 = $this->parseRecordData('');

        $this->assertCount(1, $out1->issns);
        $this->assertEquals('1089-7690', $out1->issns[0]);

        $this->assertCount(0, $out2->issns);
    }

    public function testCanceledIsbn() {
        // 020 $z : Cancelled/invalid ISBN
        $out = $this->parseRecordData('
            <marc:datafield tag="020" ind1=" " ind2=" ">
                <marc:subfield code="z">9788243005129 (ib.)</marc:subfield>
                <marc:subfield code="c">Nkr 339.00</marc:subfield>
            </marc:datafield>
        ');

        $this->assertCount(0, $out->isbns);
    }

    public function testIsbnWithX() {
        // Test that X-s are preserved
        $out = $this->parseRecordData('
            <marc:datafield tag="020" ind1=" " ind2=" ">
                <marc:subfield code="a">1-85723-457-X (h.)</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('1-85723-457-X', $out->isbns[0]);
    }

    public function testMarc040() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="040" ind1=" " ind2=" ">
                <marc:subfield code="e">katreg</marc:subfield>
            </marc:datafield>
        ');
        $out2 = $this->parseRecordData('
            <marc:datafield tag="040" ind1=" " ind2=" ">
                <marc:subfield code="e">rda</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('katreg', $out1->catalogingRules);
        $this->assertEquals('rda', $out2->catalogingRules);
    }

    public function testNLMClassification() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="060" ind1="1" ind2="4">
                <marc:subfield code="a">QH 305.M26</marc:subfield>
            </marc:datafield>
        ');

        $this->assertCount(1, $out1->classifications);
        $klass = $out1->classifications[0];
        $this->assertEquals('nlm', $klass['system']);
        $this->assertEquals('QH 305.M26', $klass['number']);
        $this->assertNull($klass['edition']);
        $this->assertNull($klass['assigner']);
    }

    public function testUDCClassification() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="080" ind1=" " ind2=" ">
                <marc:subfield code="a">551.2:51(02)</marc:subfield>
            </marc:datafield>
        ');
        $out2 = $this->parseRecordData('');

        $this->assertCount(1, $out1->classifications);
        $klass = $out1->classifications[0];
        $this->assertEquals('udc', $klass['system']);
        $this->assertEquals('551.2:51(02)', $klass['number']);
        $this->assertNull($klass['edition']);
        $this->assertNull($klass['assigner']);
    }

    public function testDDCClassificationWithoutAssigner() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="082" ind1="0" ind2="4">
                <marc:subfield code="a">333.914/02[U]</marc:subfield>
                <marc:subfield code="2">23</marc:subfield>
            </marc:datafield>
        ');
        $out2 = $this->parseRecordData('');

        $this->assertCount(1, $out1->classifications);
        $klass = $out1->classifications[0];
        $this->assertEquals('ddc', $klass['system']);
        $this->assertEquals('333.91402', $klass['number']);
        $this->assertEquals('23', $klass['edition']);
        $this->assertNull($klass['assigner']);

        $this->assertCount(0, $out2->classifications);
    }

    public function testDDCClassificationWithAssigner() {
        $out = $this->parseRecordData('
            <marc:datafield tag="082" ind1="7" ind2="4">
                <marc:subfield code="a">639.3</marc:subfield>
                <marc:subfield code="q">NO-OsNB</marc:subfield>
                <marc:subfield code="2">5/nor</marc:subfield>
            </marc:datafield>
        ');

        $klass = $out->classifications[0];
        $this->assertEquals('ddc', $klass['system']);
        $this->assertEquals('639.3', $klass['number']);
        $this->assertEquals('5/nor', $klass['edition']);
        $this->assertEquals('NO-OsNB', $klass['assigner']);
    }

    public function testInvalidDdcvalue() {
        $out = $this->parseRecordData('
            <marc:datafield tag="082" ind1="0" ind2="4">
                <marc:subfield code="a">0-86217-075-3</marc:subfield>
            </marc:datafield>
        ');

        $this->assertCount(0, $out->classifications);
    }

    public function testOtherClassifications() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="084" ind1=" " ind2=" ">
                <marc:subfield code="a">62.70</marc:subfield>
                <marc:subfield code="2">msc</marc:subfield>
            </marc:datafield>
            <marc:datafield tag="084" ind1=" " ind2=" ">
                <marc:subfield code="a">62G15</marc:subfield>
                <marc:subfield code="2">msc</marc:subfield>
            </marc:datafield>
            <marc:datafield tag="084" ind1=" " ind2=" ">
                <marc:subfield code="a">62G05</marc:subfield>
                <marc:subfield code="2">msc</marc:subfield>
            </marc:datafield>
            <marc:datafield tag="084" ind1=" " ind2=" ">
                <marc:subfield code="a">62G10</marc:subfield>
                <marc:subfield code="2">msc</marc:subfield>
            </marc:datafield>
            <marc:datafield tag="084" ind1=" " ind2=" ">
                <marc:subfield code="a">62.70</marc:subfield>
            </marc:datafield>
        ');

        $this->assertCount(4, $out1->classifications); // Not 5 since entries without $2 are ignored!

        $this->assertEquals('msc', $out1->classifications[0]['system']);
        $this->assertEquals('62.70', $out1->classifications[0]['number']);
        $this->assertNull($out1->classifications[0]['edition']);
        $this->assertNull($out1->classifications[0]['assigner']);

        $this->assertEquals('msc', $out1->classifications[1]['system']);
        $this->assertEquals('62G15', $out1->classifications[1]['number']);
        $this->assertNull($out1->classifications[1]['edition']);
        $this->assertNull($out1->classifications[1]['assigner']);
    }

    public function testPersonalNameHeadingWithAuthority() {
        $out = $this->parseRecordData('
            <marc:datafield tag="100" ind1="1" ind2=" ">
                <marc:subfield code="a">Bjerkestrand, Bernt</marc:subfield>
                <marc:subfield code="d">1950-</marc:subfield>
                <marc:subfield code="0">(NO-TrBIB)x12001130</marc:subfield>
            </marc:datafield>
        ');

        $out2 = $this->parseRecordData('
            <marc:datafield tag="100" ind1="1" ind2=" ">
                <marc:subfield code="a">Halldór Kiljan Laxness</marc:subfield>
            </marc:datafield>
        ');

        $this->assertCount(1, $out->creators);
        $this->assertEquals('Bernt Bjerkestrand', $out->creators[0]['name']);
        $this->assertEquals('Bjerkestrand, Bernt', $out->creators[0]['normalizedName']);
        $this->assertEquals('x12001130', $out->creators[0]['id']);
        $this->assertEquals('NO-TrBIB', $out->creators[0]['vocabulary']);

        $this->assertEquals('Halldór Kiljan Laxness', $out2->creators[0]['name']);
        $this->assertEquals('Halldór Kiljan Laxness', $out2->creators[0]['normalizedName']);
        $this->assertArrayNotHasKey('authority', $out2->creators[0]);
    }

    public function testPersonalNameHeadingWithRelatorCode()
    {
        $out = $this->parseRecordData('
            <marc:datafield tag="100" ind1="1" ind2=" ">
                <marc:subfield code="a">Cangelosi, Angelo</marc:subfield>
                <marc:subfield code="4">aut</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('Angelo Cangelosi', $out->creators[0]['name']);
        $this->assertEquals('aut', $out->creators[0]['role']);
    }

    public function testMarc110() {
        $out = $this->parseRecordData('
           <marc:datafield tag="110" ind1="2" ind2=" ">
                <marc:subfield code="a">Norge</marc:subfield>
                <marc:subfield code="b">Miljøverndepartementet</marc:subfield>
                <marc:subfield code="0">(NO-TrBIB)x90051067</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('Norge' . Record::$subfieldSeparator . 'Miljøverndepartementet', $out->creators[0]['name']);
        $this->assertEquals('x90051067', $out->creators[0]['id']);
    }

    public function testMarc111() {

        // Example from 863012868
        $out = $this->parseRecordData('
            <marc:datafield tag="111" ind1="2" ind2=" ">
                <marc:subfield code="a">Ecology of coastal vegetation (Haamstede : 1983)</marc:subfield>
                <marc:subfield code="0">(NO-TrBIB)x90051629</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('Ecology of coastal vegetation (Haamstede : 1983)', $out->meetings[0]['name']);
        $this->assertEquals('x90051629', $out->meetings[0]['id']);
    }

    public function testMarc245() {
        // Colon should be trimmed off title
        $out = $this->parseRecordData('
            <marc:datafield tag="245" ind1="1" ind2="0">
                <marc:subfield code="a">Evolusjon :</marc:subfield>
                <marc:subfield code="b">naturens kulturhistorie</marc:subfield>
                <marc:subfield code="c">Markus Lindholm</marc:subfield>
                <marc:subfield code="h">[videoopptak]</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('Evolusjon : naturens kulturhistorie', $out->title);
        $this->assertEquals('[videoopptak]', $out->medium);
    }

    public function testMarc245part() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="245" ind1="0" ind2="0">
                <marc:subfield code="a">No ordinary genius</marc:subfield>
            </marc:datafield>
        ');
        $out2 = $this->parseRecordData('
            <marc:datafield tag="245" ind1="0" ind2="0">
                <marc:subfield code="a">No ordinary genius</marc:subfield>
                <marc:subfield code="n">Part one</marc:subfield>
            </marc:datafield>
        ');
        $out3 = $this->parseRecordData('
            <marc:datafield tag="245" ind1="1" ind2="0">
                <marc:subfield code="a">Verehrte An- und Abwesende!</marc:subfield>
                <marc:subfield code="b">Originaltonaufnahmen 1921-1951</marc:subfield>
                <marc:subfield code="n">CD1</marc:subfield>
                <marc:subfield code="p">[1921-1941]</marc:subfield>
                <marc:subfield code="h">[lydopptak]</marc:subfield>
            </marc:datafield>
        ');

        $this->assertNull($out1->part_no);
        $this->assertNull($out1->part_name);
        $this->assertNull($out2->part_name);

        $this->assertEquals('Part one', $out2->part_no);
        $this->assertEquals('CD1', $out3->part_no);
        $this->assertEquals('[1921-1941]', $out3->part_name);
        $this->assertEquals('[lydopptak]', $out3->medium);
    }

    public function testMarc250() {
        $out = $this->parseRecordData('
           <marc:datafield tag="250" ind1=" " ind2=" ">
                <marc:subfield code="a">2. utg.</marc:subfield>
            </marc:datafield>
        ');

        // TODO
    }

    public function testMarc260c() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="260" ind1=" " ind2=" ">
                <marc:subfield code="c">c2013</marc:subfield>
            </marc:datafield>
        ');
        $out2 = $this->parseRecordData('
            <marc:datafield tag="260" ind1=" " ind2=" ">
                <marc:subfield code="c">2009 [i.e. 2008]</marc:subfield>
            </marc:datafield>
        ');
        $out3 = $this->parseRecordData('
            <marc:datafield tag="260" ind1=" " ind2=" ">
            </marc:datafield>
        ');

        $this->assertEquals(2013, $out1->year);
        $this->assertEquals(2009, $out2->year);
        $this->assertNull($out3->year);
    }

    public function testMarc300() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="300" ind1=" " ind2=" ">
                <marc:subfield code="a">353 s.</marc:subfield>
                <marc:subfield code="b">ill.</marc:subfield>
                <marc:subfield code="c">27 cm</marc:subfield>
            </marc:datafield>
        ');

        $out2 = $this->parseRecordData('
            <marc:datafield tag="300" ind1=" " ind2=" ">
                <marc:subfield code="a">1 videoplate (DVD-video) (1 t 36 min)</marc:subfield>
                <marc:subfield code="b">lyd, kol.</marc:subfield>
            </marc:datafield>
        ');

        $out3 = $this->parseRecordData('
            <marc:datafield tag="300" ind1=" " ind2=" ">
                <marc:subfield code="a">xxi, s. 958-1831</marc:subfield>
            </marc:datafield>
        ');

        $out4 = $this->parseRecordData('
            <marc:datafield tag="300" ind1=" " ind2=" ">
                <marc:subfield code="a">xxi, 48 [i.e. 96] s.</marc:subfield>
            </marc:datafield>
        ');

        $out5 = $this->parseRecordData('
            <marc:datafield tag="300" ind1=" " ind2=" ">
                <marc:subfield code="a">[104] s.</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('353 s.', $out1->extent);
        $this->assertEquals(353, $out1->pages);

        $this->assertEquals('1 videoplate (DVD-video) (1 t 36 min)', $out2->extent);
        $this->assertNull($out2->pages);

        $this->assertEquals(1831 - 958 + 1, $out3->pages);

        $this->assertEquals(96, $out4->pages);

        $this->assertEquals(104, $out5->pages);
    }

    public function testMarc500() {
        $out = $this->parseRecordData('
            <marc:datafield tag="500" ind1=" " ind2=" ">
                <marc:subfield code="a">Forts.som: Acoustical imaging. 8(1980)</marc:subfield>
            </marc:datafield>
        ');

        $this->assertCount(1, $out->notes);
        $this->assertEquals('Forts.som: Acoustical imaging. 8(1980)', $out->notes[0]);
    }

    public function testMarc502() {
        $out = $this->parseRecordData('
            <marc:datafield tag="502" ind1=" " ind2=" ">
                <marc:subfield code="a">Avhandling (Doktorgrad) - Aachen Technische Hochschule.</marc:subfield>
            </marc:datafield>
        ');

        $this->assertCount(1, $out->notes);
        $this->assertEquals('Avhandling (Doktorgrad) - Aachen Technische Hochschule.', $out->notes[0]);
    }

    public function testMarc600() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="600" ind1="1" ind2="4">
                <marc:subfield code="a">Støre, Jonas Gahr</marc:subfield>
                <marc:subfield code="d">1960-</marc:subfield>
                <marc:subfield code="0">(NO-TrBIB)x02121602</marc:subfield>
            </marc:datafield>
        ');

        $out2 = $this->parseRecordData('
            <marc:datafield tag="600" ind1="0" ind2="0">
                <marc:subfield code="a">Zacchaeus</marc:subfield>
                <marc:subfield code="c">(Biblical character)</marc:subfield>
            </marc:datafield>
        ');

        $out3 = $this->parseRecordData('
            <marc:datafield tag="600" ind1="1" ind2="0">
                <marc:subfield code="a">Pushkin, Aleksandr Sergeevich</marc:subfield>
                <marc:subfield code="d">1799-1837</marc:subfield>
                <marc:subfield code="x">Museums</marc:subfield>
                <marc:subfield code="z">Russia (Federation)</marc:subfield>
                <marc:subfield code="z">Moscow</marc:subfield>
                <marc:subfield code="v">Maps.</marc:subfield>
            </marc:datafield>
        ');

        $out4 = $this->parseRecordData('
            <marc:datafield tag="600" ind1="1" ind2="4">
                <marc:subfield code="a">Walpole, Robert</marc:subfield>
                <marc:subfield code="d">1676-1745</marc:subfield>
                <marc:subfield code="c">Earl of Orford</marc:subfield>
            </marc:datafield>
        ');

        $this->assertCount(1, $out1->subjects);
        $this->assertEquals('person', $out1->subjects[0]['type']);
        $this->assertEquals('Støre, Jonas Gahr (1960-)', $out1->subjects[0]['term']);
        $this->assertEquals('NO-TrBIB', $out1->subjects[0]['vocabulary']);
        $this->assertEquals('x02121602', $out1->subjects[0]['id']);

        $this->assertEquals('Zacchaeus (Biblical character)', $out2->subjects[0]['term']);
        $this->assertNull($out2->subjects[0]['id']);
        $this->assertEquals('lcsh', $out2->subjects[0]['vocabulary']);

        $this->assertEquals('Pushkin, Aleksandr Sergeevich (1799-1837)' . Record::$subfieldSeparator . 'Museums' . Record::$subfieldSeparator . 'Russia (Federation)' . Record::$subfieldSeparator . 'Moscow' . Record::$subfieldSeparator . 'Maps', $out3->subjects[0]['term']);
        $this->assertNull($out3->subjects[0]['id']);
        $this->assertEquals('lcsh', $out3->subjects[0]['vocabulary']);

        $this->assertEquals('Walpole, Robert (Earl of Orford, 1676-1745)', $out4->subjects[0]['term']);
        $this->assertNull($out4->subjects[0]['id']);
        $this->assertNull($out4->subjects[0]['vocabulary']);
    }

    public function testMarc610() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="610" ind1="1" ind2="0">
                <marc:subfield code="a">United States.</marc:subfield>
                <marc:subfield code="b">Army.</marc:subfield>
                <marc:subfield code="b">Cavalry, 7th.</marc:subfield>
                <marc:subfield code="b">Company E,</marc:subfield>
                <marc:subfield code="e">depicted.</marc:subfield>
            </marc:datafield>
        ');

        $out2 = $this->parseRecordData('
            <marc:datafield tag="610" ind1="2" ind2="4">
                <marc:subfield code="a">Tidens tegn (avis)</marc:subfield>
                <marc:subfield code="0">(NO-TrBIB)x90071929</marc:subfield>
            </marc:datafield>
            <marc:datafield tag="610" ind1="2" ind2="4">
                <marc:subfield code="a">Aftenposten</marc:subfield>
                <marc:subfield code="0">(NO-TrBIB)x90052061</marc:subfield>
            </marc:datafield>
        ');

        $this->assertCount(1, $out1->subjects);
        // Problem: How to normalize punctuation? We loose the dot in '7th.' if we strip off dots..
        $this->assertEquals('corporation', $out1->subjects[0]['type']);
        $this->assertEquals('United States.' . Record::$subfieldSeparator . 'Army.' . Record::$subfieldSeparator . 'Cavalry, 7th.' . Record::$subfieldSeparator . 'Company E', $out1->subjects[0]['term']);

        $this->assertCount(2, $out2->subjects);
        $this->assertEquals('Tidens tegn (avis)', $out2->subjects[0]['term']);
        $this->assertEquals('x90071929', $out2->subjects[0]['id']);
        $this->assertEquals('NO-TrBIB', $out2->subjects[0]['vocabulary']);
    }

    public function testMarc611() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="611" ind1="2" ind2="0">
                <marc:subfield code="a">International Congress of Writers for the Defense of Culture
</marc:subfield>
                <marc:subfield code="n">(1st :</marc:subfield>
                <marc:subfield code="d">1935 :</marc:subfield>
                <marc:subfield code="c">Paris, France)</marc:subfield>
                <marc:subfield code="v">Fiction.</marc:subfield>
            </marc:datafield>
        ');

        $this->assertCount(1, $out1->subjects);
        // Problem: How to normalize punctuation? We loose the dot in '7th.' if we strip off dots..
        $this->assertEquals('meeting', $out1->subjects[0]['type']);
        $this->assertEquals('International Congress of Writers for the Defense of Culture' . Record::$subfieldSeparator . 'Fiction', $out1->subjects[0]['term']);
        $this->assertEquals('1st', $out1->subjects[0]['number']);
        $this->assertEquals('1935', $out1->subjects[0]['time']);
        $this->assertEquals('Paris, France', $out1->subjects[0]['place']);
    }

    public function testMarc650() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="650" ind1=" " ind2="7">
                <marc:subfield code="a">Sjømat</marc:subfield>
                <marc:subfield code="z">Norge</marc:subfield>
                <marc:subfield code="2">tekord</marc:subfield>
                <marc:subfield code="0">NTUB12641</marc:subfield>
            </marc:datafield>
        ');
        $out2 = $this->parseRecordData('
            <marc:datafield tag="650" ind1=" " ind2="0">
                <marc:subfield code="a">Optoelectronics industry</marc:subfield>
                <marc:subfield code="x">Directories.</marc:subfield>
            </marc:datafield>
        ');
        $out3 = $this->parseRecordData('
            <marc:datafield tag="650" ind1=" " ind2="7">
                <marc:subfield code="a">Musikk</marc:subfield>
                <marc:subfield code="v">Historie</marc:subfield>
                <marc:subfield code="y">1900-    .</marc:subfield>
                <marc:subfield code="z">Tyskland</marc:subfield>
                <marc:subfield code="2">tekord</marc:subfield>
            </marc:datafield>
        ');
        $out4 = $this->parseRecordData('');

        $this->assertCount(1, $out1->subjects);
        $this->assertEquals('topical', $out1->subjects[0]['type']);
        $this->assertEquals('tekord', $out1->subjects[0]['vocabulary']);
        $this->assertEquals('NTUB12641', $out1->subjects[0]['id']);
        $this->assertEquals('Sjømat' . Record::$subfieldSeparator . 'Norge', $out1->subjects[0]['term']);
        $this->assertEquals('Norge', $out1->subjects[0]['parts'][0]['value']);
        $this->assertEquals('geographic', $out1->subjects[0]['parts'][0]['type']);

        $this->assertEquals('lcsh', $out2->subjects[0]['vocabulary']);
        $this->assertEquals('Optoelectronics industry' . Record::$subfieldSeparator . 'Directories', $out2->subjects[0]['term']);
        $this->assertEquals('Directories', $out2->subjects[0]['parts'][0]['value']);
        $this->assertEquals('general', $out2->subjects[0]['parts'][0]['type']);

        $this->assertEquals('tekord', $out3->subjects[0]['vocabulary']);
        $this->assertEquals('Musikk' . Record::$subfieldSeparator . 'Historie' . Record::$subfieldSeparator . '1900-    ' . Record::$subfieldSeparator . 'Tyskland', $out3->subjects[0]['term']);
        $this->assertEquals('Historie', $out3->subjects[0]['parts'][0]['value']);
        $this->assertEquals('form', $out3->subjects[0]['parts'][0]['type']);
        $this->assertEquals('1900-    ', $out3->subjects[0]['parts'][1]['value']);
        $this->assertEquals('chronological', $out3->subjects[0]['parts'][1]['type']);
        $this->assertEquals('Tyskland', $out3->subjects[0]['parts'][2]['value']);
        $this->assertEquals('geographic', $out3->subjects[0]['parts'][2]['type']);

        $this->assertCount(0, $out4->subjects);
    }

    public function testUncontrolledSubjects() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="653" ind1="1" ind2=" ">
                <marc:subfield code="a">fuel cells</marc:subfield>
                <marc:subfield code="a">molten carbonate</marc:subfield>
                <marc:subfield code="a">power generation</marc:subfield>
            </marc:datafield>
        ');

        $this->assertCount(3, $out1->subjects);
        $this->assertEquals('fuel cells', $out1->subjects[0]['term']);
        $this->assertEquals('molten carbonate', $out1->subjects[1]['term']);
        $this->assertEquals('power generation', $out1->subjects[2]['term']);
    }

    public function testUncontrolledGenre() {
        $out2 = $this->parseRecordData('
            <marc:datafield tag="653" ind1="1" ind2="6">
                <marc:subfield code="a">comics</marc:subfield>
            </marc:datafield>
        ');

        $this->assertCount(0, $out2->subjects);
        $this->assertCount(1, $out2->genres);
        $this->assertEquals('comics', $out2->genres[0]['term']);
        $this->assertNull($out2->genres[0]['vocabulary']);
    }

    // Example: 133027287
    public function testControlledGenre() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="655" ind1=" " ind2="7">
                <marc:subfield code="a">Populærvitenskap</marc:subfield>
                <marc:subfield code="2">no-ubo-mn</marc:subfield>
                <marc:subfield code="0">REAL14834</marc:subfield>
            </marc:datafield>
        ');

        $this->assertCount(1, $out1->genres);
        $this->assertEquals('no-ubo-mn', $out1->genres[0]['vocabulary']);
        $this->assertEquals('REAL14834', $out1->genres[0]['id']);
        $this->assertEquals('Populærvitenskap', $out1->genres[0]['term']);
    }

    public function testMarc700() {
        $out1 = $this->parseRecordData('
            <marc:datafield tag="700" ind1="1" ind2=" ">
                <marc:subfield code="a">Almås, Karl Andreas</marc:subfield>
                <marc:subfield code="d">1952-</marc:subfield>
                <marc:subfield code="e">red.</marc:subfield>
                <marc:subfield code="0">(NO-TrBIB)x90235102</marc:subfield>
            </marc:datafield>
        ');

        $this->assertCount(1, $out1->creators);
        $this->assertEquals('Karl Andreas Almås', $out1->creators[0]['name']);
        $this->assertEquals('red.', $out1->creators[0]['role']);
        $this->assertEquals('1952-', $out1->creators[0]['dates']);
        $this->assertEquals('x90235102', $out1->creators[0]['id']);
        $this->assertEquals('NO-TrBIB', $out1->creators[0]['vocabulary']);
    }

    public function testMarc710() {
        // Here, 'dgg' is  'Degree granting institution' used 
        // http://www.loc.gov/marc/relators/relacode.html
        $out = $this->parseRecordData('
            <marc:datafield tag="710" ind1="2" ind2=" ">
                <marc:subfield code="a">Universitetet i Oslo</marc:subfield>
                <marc:subfield code="b">Det utdanningsvitenskapelige fakultet</marc:subfield>
                <marc:subfield code="4">dgg</marc:subfield>
                <marc:subfield code="0">(NO-TrBIB)x90921833</marc:subfield>
            </marc:datafield>
        ');

        // TODO
    }

    public function testPartOf() {
        $out = $this->parseRecordData('
            <marc:datafield tag="245" ind1="0" ind2="0">
                <marc:subfield code="a">Scattering</marc:subfield>
                <marc:subfield code="b">scattering and inverse scattering in pure and applied science</marc:subfield>
                <marc:subfield code="n">Vol. 1</marc:subfield>
            </marc:datafield>
            <marc:datafield tag="773" ind1="0" ind2="8">
                <marc:subfield code="i">Inkludert i</marc:subfield>
                <marc:subfield code="t">Scattering</marc:subfield>
                <marc:subfield code="d">San Diego, Calif. : Academic Press, c2002</marc:subfield>
                <marc:subfield code="z">0126137609</marc:subfield>
                <marc:subfield code="w">(NO-TrBIB)042457270</marc:subfield>
            </marc:datafield>
        ');
        $out2 = $this->parseRecordData('');

        $this->assertEquals('Vol. 1', $out->part_no);
        $this->assertEquals('042457270', $out->part_of['id']);
        $this->assertEquals('NO-TrBIB', $out->part_of['vocabulary']);
        $this->assertEquals('0126137609', $out->part_of['isbn']);
        $this->assertNull($out->part_of['issn']);
        $this->assertEquals('Scattering', $out->part_of['title']);
        $this->assertEquals('Inkludert i', $out->part_of['relationship']);

        $this->assertNull($out2->part_of);
    }

    public function testMarc776() {
        $out = $this->parseRecordData('
            <marc:datafield tag="776" ind1="0" ind2=" ">
                <marc:subfield code="w">(NO-TrBIB)022991026</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('022991026', $out->other_form['id']);
    }

    public function testPreceding() {
        $out = $this->parseRecordData('
            <marc:datafield tag="780" ind1="0" ind2="0">
                <marc:subfield code="w">(NO-TrBIB)920713874</marc:subfield>
                <marc:subfield code="g">nr 80(1961)</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('Continues', $out->preceding['relationship_type']);
        $this->assertCount(1, $out->preceding['items']);
        $this->assertEquals('920713874', $out->preceding['items'][0]['id']);
        $this->assertEquals('nr 80(1961)', $out->preceding['items'][0]['parts']);
    }

    /*
     * Expect simplification of 'Merged with'
     */
    public function testSucceedingMergedWith() {
        $out = $this->parseRecordData('
            <marc:datafield tag="580" ind1=" " ind2=" ">
                <marc:subfield code="a">
                    Slått sammen med: Comments on astrophysics : a journal of critical discussion of the current literature, 18(1995/96) og: Comments on condensed matter physics : a journal of critical discussion of the current literature, 18(1998) og: Comments on nuclear and particle physics : a journal of critical discussion of the current literature, 22(1998) og: Comments on plasma physics and controlled fusion : a journal of critical discussion of the current literature, 18(1999) til: Comments on modern physics (trykt utg.), 1(1999)
                </marc:subfield>
            </marc:datafield>
            <marc:datafield tag="785" ind1="1" ind2="7">
                <marc:subfield code="w">(NO-TrBIB)841195196</marc:subfield>
                <marc:subfield code="g">18(1995/96)</marc:subfield>
            </marc:datafield>
            <marc:datafield tag="785" ind1="1" ind2="7">
                <marc:subfield code="w">(NO-TrBIB)864105495</marc:subfield>
                <marc:subfield code="g">18(1998)</marc:subfield>
            </marc:datafield>
            <marc:datafield tag="785" ind1="1" ind2="7">
                <marc:subfield code="w">(NO-TrBIB)852150393</marc:subfield>
                <marc:subfield code="g">22(1998)</marc:subfield>
            </marc:datafield>
            <marc:datafield tag="785" ind1="1" ind2="7">
                <marc:subfield code="w">(NO-TrBIB)812012682</marc:subfield>
                <marc:subfield code="g">18(1999)</marc:subfield>
            </marc:datafield>
            <marc:datafield tag="785" ind1="1" ind2="7">
                <marc:subfield code="w">(NO-TrBIB)990840832</marc:subfield>
                <marc:subfield code="g">1(1999)</marc:subfield>
            </marc:datafield>
        ');

        $this->assertEquals('Continued by', $out->succeeding['relationship_type']);
        $this->assertEquals(
            'Slått sammen med: Comments on astrophysics : a journal of critical discussion of the current literature, 18(1995/96) og: Comments on condensed matter physics : a journal of critical discussion of the current literature, 18(1998) og: Comments on nuclear and particle physics : a journal of critical discussion of the current literature, 22(1998) og: Comments on plasma physics and controlled fusion : a journal of critical discussion of the current literature, 18(1999) til: Comments on modern physics (trykt utg.), 1(1999)',
            $out->succeeding['note']
        );
        $this->assertCount(1, $out->succeeding['items']);
        $this->assertEquals('990840832', $out->succeeding['items'][0]['id']);
        $this->assertEquals('1(1999)', $out->succeeding['items'][0]['parts']);
    }

    public function testMarc830() {
        $out = $this->parseRecordData('
            <marc:datafield tag="830" ind1=" " ind2=" ">
                <marc:subfield code="a">Physica mathematica Universitatis Osloensis</marc:subfield>
                <marc:subfield code="v">32</marc:subfield>
                <marc:subfield code="w">(NO-TrBIB)922367817</marc:subfield>
            </marc:datafield>
            <marc:datafield tag="830" ind1=" " ind2="0">
                <marc:subfield code="a">
                Report series (Universitetet i Oslo. Fysisk institutt) (trykt utg.)
                </marc:subfield>
                <marc:subfield code="v">94-13</marc:subfield>
                <marc:subfield code="w">(NO-TrBIB)812037006</marc:subfield>
            </marc:datafield>
            <marc:datafield tag="830" ind1=" " ind2="0">
                <marc:subfield code="a">Muirhead library of philosophy</marc:subfield>
            </marc:datafield>
        ');

        $this->assertCount(3, $out->series);
        $this->assertEquals('Physica mathematica Universitatis Osloensis', $out->series[0]['title']);
        $this->assertEquals('32', $out->series[0]['volume']);
        $this->assertEquals('922367817', $out->series[0]['id']);

        $this->assertEquals('Report series (Universitetet i Oslo. Fysisk institutt) (trykt utg.)', $out->series[1]['title']);
        $this->assertEquals('94-13', $out->series[1]['volume']);
        $this->assertEquals('812037006', $out->series[1]['id']);

        $this->assertEquals('Muirhead library of philosophy', $out->series[2]['title']);
        $this->assertNull($out->series[2]['volume']);
        $this->assertNull($out->series[2]['id']);
    }

    public function testMarc956() {
        $out = $this->parseRecordData('
            <marc:datafield tag="956" ind1="4" ind2="2">
                <marc:subfield code="3">Omslagsbilde</marc:subfield>
                <marc:subfield code="u">http://innhold.bibsys.no/bilde/forside/?size=mini&amp;id=LITE_150154636.jpg</marc:subfield>
                <marc:subfield code="q">image/jpeg</marc:subfield>
            </marc:datafield>
        ');

        // TODO
    }

    public function testMarc991() {
        $out1 = $this->parseRecordData('');

        $out2 = $this->parseRecordData('
            <marc:datafield tag="991" ind1=" " ind2=" ">
                <marc:subfield code="a">parts</marc:subfield>
            </marc:datafield>
        ');

        $out3 = $this->parseRecordData('
            <marc:datafield tag="991" ind1=" " ind2=" ">
                <marc:subfield code="a">volumes</marc:subfield>
            </marc:datafield>
        ');

        $this->assertFalse($out1->is_series);
        $this->assertFalse($out1->is_multivolume);

        $this->assertTrue($out2->is_series);
        $this->assertFalse($out2->is_multivolume);

        $this->assertFalse($out3->is_series);
        $this->assertTrue($out3->is_multivolume);

    }

    public function testJson()
    {
        $rec1 = $this->parseRecordData('
            <marc:controlfield tag="001">12149361x</marc:controlfield>
        ');

        $expected = json_encode(
          array(
            'id' => '12149361x',
            'is_series' => false,
            'is_multivolume' => false,
            'isbns' => array(),
            'issns' => array(),
            'series' => array(),
            'creators' => array(),
            'meetings' => array(),
            'subjects' => array(),
            'genres' => array(),
            'classifications' => array(),
            'notes' => array(),
            'material' => 'Unknown',
            'electronic' => false,
          )
        );

        $this->assertJsonStringEqualsJsonString($expected, $rec1->toJson());
    }

}
