<?php

namespace Danmichaelo\SimpleMarcParser;

class HoldingsParser {

    public function __construct() {

    }

    public function parse(\Danmichaelo\QuiteSimpleXmlElement\QuiteSimpleXmlElement $record) {

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

                    if ($x = $node->text('marc:subfield[@code="x"]')) {     // R
                        $output['nonpublic_notes'][] = $x;
                    }
                    if ($x = $node->text('marc:subfield[@code="z"]')) {     // R
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

            }
        }
        return $output;
    }

}