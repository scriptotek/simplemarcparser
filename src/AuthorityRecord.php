<?php namespace Scriptotek\SimpleMarcParser;

use Danmichaelo\QuiteSimpleXmlElement\QuiteSimpleXmlElement;
use Carbon\Carbon;

class AuthorityRecord extends Record {

    // http://www.loc.gov/marc/authority/ad008.html
    public static $cat_rules = array(
        'a' => 'Earlier rules',
        'b' => 'AACR 1',
        'c' => 'AACR 2',
        'd' => 'AACR 2 compatible',
        'z' => 'Other',
    );

    public static $vocabularies = array(
        'a' => 'lcsh',
        'b' => 'lccsh', // LC subject headings for children's literature
        'c' => 'mesh', // Medical Subject Headings
        'd' => 'atg', // National Agricultural Library subject authority file (?)
        'k' => 'cash', // Canadian Subject Headings
        'r' => 'aat', // Art and Architecture Thesaurus 
        's' => 'sears', // Sears List of Subject Heading 
        'v' => 'rvm', // Répertoire de vedettes-matière
    );

    public function __construct(QuiteSimpleXmlElement $data) {

        $output = array();
        $output['class'] = null;

        // 001: Control number
        $output['id'] = $data->text('marc:controlfield[@tag="001"]');

        // 003: MARC code for the agency whose system control number is 
        // contained in field 001 (Control Number)
        // See http://www.loc.gov/marc/authority/ecadorg.html
        $output['agency'] = $data->text('marc:controlfield[@tag="003"]');

        // 005: Modified
        $x = $data->text('marc:controlfield[@tag="005"]');
        if (strlen($x) == 8) $output['modified'] = Carbon::createFromFormat('Ymd', $x);
        if (strlen($x) == 16) $output['modified'] = Carbon::createFromFormat('YmdHis', substr($x,0,14)); // skip decimal fraction

        // 008: Extract *some* information
        $f008 = $data->text('marc:controlfield[@tag="008"]');
        $r = substr($f008, 10, 1);
        $output['cataloging'] = isset(self::$cat_rules[$r]) ? self::$cat_rules[$r] : null;
        $r = substr($f008, 11, 1);
        $output['vocabulary'] = isset(self::$vocabularies[$r]) ? self::$vocabularies[$r] : null;

        // 040: 
        $source = $data->first('marc:datafield[@tag="040"]');
        if ($source) {
            $output['catalogingAgency'] = $source->text('marc:subfield[@code="a"]') ?: null;
            $output['language'] = $source->text('marc:subfield[@code="b"]') ?: null;
            $output['transcribingAgency'] = $source->text('marc:subfield[@code="c"]') ?: null;
            $output['modifyingAgency'] = $source->text('marc:subfield[@code="d"]') ?: null;
            $output['vocabulary'] = $source->text('marc:subfield[@code="f"]') ?: $output['vocabulary'];            
        }

        // 100: Personal name (NR)
        foreach ($data->xpath('marc:datafield[@tag="100"]') as $field) {
            $output['class'] = 'person';
            $output['name'] = $field->text('marc:subfield[@code="a"]');
            $spl = explode(', ', $output['name']);
            if (count($spl) == 2) {
                $output['label'] = $spl[1] . ' ' . $spl[0];
            } else {
                $output['label'] = $output['name'];
            }
            $bd = $field->text('marc:subfield[@code="d"]');
            $bd = explode('-', $bd);
            $output['birth'] = $bd[0] ?: null;
            $output['death'] = (count($bd) > 1 && $bd[1]) ? $bd[1] : null;
        }

        // 110: Corporate Name (NR)
        foreach ($data->xpath('marc:datafield[@tag="110"]') as $field) {
            $output['class'] = 'corporate';
            // TODO: ...
        }

        // 111: Meeting Name (NR)
        foreach ($data->xpath('marc:datafield[@tag="111"]') as $field) {
            $output['class'] = 'meeting';
            // TODO: ...
        }

        // 130: Uniform title: Not interested for now

        // 150: Topical Term (NR)
        foreach ($data->xpath('marc:datafield[@tag="150"]') as $field) {
            $output['class'] = 'topicalTerm';
            $output['term'] = $field->text('marc:subfield[@code="a"]');
            $label = $field->text('marc:subfield[@code="a"]');
            foreach ($field->xpath('marc:subfield[@code="x"]') as $s) { 
                $label .= ' : ' . $s;
            }
            foreach ($field->xpath('marc:subfield[@code="v"]') as $s) {
                $label .= ' : ' . $s;
            }
            foreach ($field->xpath('marc:subfield[@code="y"]') as $s) {
                $label .= ' : ' . $s;
            }
            foreach ($field->xpath('marc:subfield[@code="z"]') as $s) {
                $label .= ' : ' . $s;
            }
            $output['label'] = $label;
            // TODO: ...
        }

        // 151: Geographic Term (NR)
        // 155: Genre/form Term (NR)

        // 375: Gender (R)
        $output['genders'] = array();
        foreach ($data->xpath('marc:datafield[@tag="375"]') as $field) {
            $gender = $field->text('marc:subfield[@code="a"]');
            $start = $field->text('marc:subfield[@code="s"]');
            $end = $field->text('marc:subfield[@code="e"]');
            $output['genders'][] = array(
                'value' => $gender,
                'from' => $start,
                'until' => $end,
            );
        }
        // Alias gender to the last value to make utilizing easier
        $output['gender'] = (count($output['genders']) > 0)
            ? $output['genders'][count($output['genders']) - 1]['value']  // assume sane ordering for now
            : null;

        // 400: See From Tracing-Personal Name (R)
        $output['nameVariants'] = array();
        foreach ($data->xpath('marc:datafield[@tag="400"]') as $field) {
            $output['nameVariants'][] = $field->text('marc:subfield[@code="a"]');
        }

        // TODO: rest

        $this->data = $output;
    }

}

/*
<?xml version="1.0"?>
<recordData>
    <marc:record xmlns:marc="info:lc/xmlns/marcxchange-v1" format="MARC21" type="Authority">
    <marc:leader>99999nz a2299999n 4500</marc:leader>
    <marc:controlfield tag="001">x90531735</marc:controlfield>
    <marc:controlfield tag="003">NO-TrBIB</marc:controlfield>
    <marc:controlfield tag="005">20090407000000.0</marc:controlfield>
    <marc:controlfield tag="008">090407n adznnaabn| |a|ana| </marc:controlfield>
    <marc:datafield tag="016" ind1="7" ind2=" ">
        <marc:subfield code="a">x90531735</marc:subfield>
        <marc:subfield code="2">NO-TrBIB</marc:subfield>
    </marc:datafield>
    <marc:datafield tag="040" ind1=" " ind2=" ">
        <marc:subfield code="a">NO-OsNB</marc:subfield>
        <marc:subfield code="b">nob</marc:subfield>
        <marc:subfield code="c">NO-TrBIB</marc:subfield>
        <marc:subfield code="f">noraf</marc:subfield>
    </marc:datafield>
    <marc:datafield tag="100" ind1="1" ind2=" ">
        <marc:subfield code="a">Bakke, Dagfinn</marc:subfield>
        <marc:subfield code="d">1933-</marc:subfield>
    </marc:datafield>
    <marc:datafield tag="375" ind1=" " ind2=" ">
        <marc:subfield code="a">male</marc:subfield>
    </marc:datafield>
    <marc:datafield tag="400" ind1="0" ind2=" ">
        <marc:subfield code="a">DAN</marc:subfield>
    </marc:datafield>
</marc:record>
</recordData>
*/