<?php namespace Scriptotek\SimpleMarcParser;

use Danmichaelo\QuiteSimpleXmlElement\QuiteSimpleXmlElement;

class HoldingsParser {

    // 859 $f: Use restrictions / Tilgjengelighet
    // Ref: http://www.bibsys.no/files/out/biblev/utlaanstatus-marc21.pdf
    static $m859_f = array(
        '1' => 'Not for loan',
        '2' => 'In-library use only',
        '3' => 'Overnight only',
        '4' => 'Use only in controlled access room',
        '5' => 'Renewals not permitted',
        '6' => 'Short loan period',
        '7' => 'Normal loan period',
        '8' => 'Long loan period',
        '9' => 'Term loan',
        '10' => 'Semester loan',
        '11' => 'Available for supply without return'
    );

    // 859 $h: Circulation status  / Utlånsstatus
    // Ref: http://www.bibsys.no/files/out/biblev/utlaanstatus-marc21.pdf
    static $m859_h = array(
        '0' => 'Available',
        '1' => 'Circulation status undefined',
        '2' => 'On order',
        '3' => 'Not available; undefined',
        '4' => 'On loan',
        '5' => 'On loan and not available for recall until earliest recall date',
        '6' => 'In process',
        '7' => 'Recalled',
        '8' => 'On hold',
        '9' => 'Waiting to be made available',
        '10' => 'In transit (between library locations)',
        '11' => 'Claimed returned or never borrowed',
        '12' => 'Lost',
        '13' => 'Missing, being traced',
        '14' => 'Supplied (i.e. return not required'
    );

    public function __construct() {

    }

    public function parse(QuiteSimpleXmlElement $record) {

        $output = array();

        $output['id'] = $record->text('marc:controlfield[@tag="001"]');  // Dokid
        $output['fulltext'] = array();
        $output['nonpublic_notes'] = array();
        $output['public_notes'] = array();

        foreach ($record->xpath('marc:datafield') as $node) {
            $marcfield = intval($node->attributes()->tag);
            switch ($marcfield) {

                case 852:
                    // http://www.loc.gov/marc/holdings/concise/hd852.html
                    $output['location'] = $node->text('marc:subfield[@code="a"]');          // NR
                    $output['sublocation'] = $node->text('marc:subfield[@code="b"]');       // R  (i praksis??)
                    $output['shelvinglocation'] = $node->text('marc:subfield[@code="c"]');  // R  (i praksis??)
                    $output['callcode'] = $node->text('marc:subfield[@code="h"]');          // NR

                    if (($x = $node->text('marc:subfield[@code="x"]')) !== '') {     // R
                        $output['nonpublic_notes'][] = $x;
                    }
                    if (($x = $node->text('marc:subfield[@code="z"]')) !== '') {     // R
                        $output['public_notes'][] = $x;
                    }

                    break;

                case 856:
                    $description = $node->text('marc:subfield[@code="3"]');
                    if (in_array($description, array('Fulltekst','Fulltext'))) {
                        $output['fulltext'][] = array(
                            'url' => $node->text('marc:subfield[@code="u"]'),
                            'provider' => $node->text('marc:subfield[@code="y"]'),
                            'comment' => $node->text('marc:subfield[@code="z"]')
                        );
                    }
                    break;

                case 859:
                    // 859: Forslag til norsk tillegg til MARC 21 for utlånsstatus
                    // http://www.bibsys.no/files/out/biblev/utlaanstatus-marc21.pdf
                    // 859 $f: Use restrictions / Tilgjengelighet
                    $x = $node->text('marc:subfield[@code="f"]');
                    if ($x !== '') {
                        if (isset(HoldingsParser::$m859_f[$x])) {
                            $output['use_restrictions'] = HoldingsParser::$m859_f[$x];
                        }
                    }

                    $x = $node->text('marc:subfield[@code="h"]');
                    if ($x !== '') {
                        if (isset(HoldingsParser::$m859_h[$x])) {
                            $output['circulation_status'] = HoldingsParser::$m859_h[$x];
                        }
                    }

                    break;
            }
        }
        return $output;
    }

}