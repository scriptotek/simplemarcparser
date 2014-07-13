<?php namespace Scriptotek\SimpleMarcParser;

use Danmichaelo\QuiteSimpleXmlElement\QuiteSimpleXmlElement;

class Parser {

    public function __construct() {

    }

    public function parse(QuiteSimpleXmlElement $record) {

        $output = array();

        $leader = $record->text('marc:leader');

        //99999 ai a22999997c 4500

        $recordType = substr($leader, 6, 1);

        switch ($recordType) {
            case 'a': // Language material
            case 'c': // Notated music
            case 'd': // Manuscript notated music
            case 'e': // Cartographic material
            case 'f': // Manuscript cartographic material
            case 'g': // Projected medium
            case 'i': // Nonmusical sound recording
            case 'j': // Musical sound recording
            case 'k': // Two-dimensional nonprojectable graphic
            case 'm': // Computer file
            case 'o': // Kit
            case 'p': // Mixed materials
            case 'r': // Three-dimensional artifact or naturally occurring object
            case 't': // Manuscript language material
                return new BibliographicRecord($record);
            case 'z':
                return new AuthorityRecord($record);
            case 'u': // Unknown 
            case 'v': // Multipart item holdings 
            case 'x': // Single-part item holdings 
            case 'y': // Serial item holdings 
                return new HoldingsRecord($record);
            default:
                throw new ParserException("Unknown record type.\n\n------------------------\n" . $record->asXML() . "\n------------------------");
        }

    }

}
