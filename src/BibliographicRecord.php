<?php namespace Scriptotek\SimpleMarcParser;

use Illuminate\Support\Contracts\JsonableInterface;
use Danmichaelo\QuiteSimpleXmlElement\QuiteSimpleXmlElement;

class BibliographicRecord extends Record implements JsonableInterface {


    public function getMaterialSubtypeFrom007($x1, $x2, $default = 'Unknown')
    {
         $f007values = array(
            'a' => array(
                'd' => 'Atlas',
                'g' => 'Diagram',
                'j' => 'Map',
                'k' => 'Profile',
                'q' => 'Model',
                'r' => 'Remote-sensing image',
                '_' => 'Map',
            ),
            'c' => array(
                'a' => 'Tape cartridge',
                'b' => 'Chip cartridge',
                'c' => 'Computer optical disc cartridge',
                'd' => 'Computer disc, type unspecified',
                'e' => 'Computer disc cartridge, type unspecified',
                'f' => 'Tape cassette',
                'h' => 'Tape reel',
                'j' => 'Magnetic disk',
                'k' => 'Computer card',
                'm' => 'Magneto-optical disc',
                'o' => 'CD-ROM',                // Optical disc
                'r' => 'Remote resource',    // n Nettdokumenter
            ),
            'f' => array(
                'a' => 'Moon',         // in the Moon writing system
                'b' => 'Braille',      // in the Braille writing system
                'c' => 'Combination ', // in a combination of two or more of the other defined types
                'd' => 'No writing system',
            ),
            'o' => array(
                'u' => 'Kit',
                '|' => 'Kit',
            ),
            's' => array(
                'd' => 'Music CD',             // v CD-er
                'e' => 'Cylinder',
                'g' => 'Sound cartridge',
                'i' => 'Sound-track film',
                'q' => 'Roll',
                's' => 'Sound cassette',
                't' => 'Sound-tape reel',
                'u' => 'Unspecified',
                'w' => 'Wire recording',
            ),
            'v' => array(
                'c' => 'Videocartridge',
                'd' => 'Videodisc',           // w DVD-er
                'f' => 'Videocassette',
                'r' => 'Videoreel',
            ),
        );
        if (isset($f007values[$x1]) && isset($f007values[$x1][$x2])) {
            return $f007values[$x1][$x2];
        }
        // TODO: LOG IT!
        return $default;
    }

    protected function parseMaterial($data)
    {
        // See http://www.loc.gov/marc/ldr06guide.html

        // The Leader/06 (Type of record) character position contains a one-character 
        // alphabetic code that differentiates MARC records created for various types of
        // content and materials.

        // The 008 field contains character positions that provide coded information about
        // the record as a whole and about special bibliographic aspects of the item cataloged.

        // Field 006 (Fixed-length data elements - Additional material characteristics) 
        // permits coding for additional aspects of an electronic resource (including any 
        // computer file aspects) if the 007 and 008 fields do not adequately describe it.

        // leader[6] : Type of record
        // leader[7] : Bibliographic level
        // Field 008/18-34 Configuration

        // LDR/06
        $recordTypes = array(
            'a' => 'Language material',
            'c' => 'Notated music',
            'd' => 'Manuscript notated music',
            'e' => 'Cartographic material',
            'f' => 'Manuscript cartographic material',
            'g' => 'Projected medium',
            'i' => 'Nonmusical sound recording',
            'j' => 'Musical sound recording',
            'k' => 'Two-dimensional nonprojectable graphic',
            'm' => 'Computer file',
            'o' => 'Kit',
            'p' => 'Mixed materials',
            'r' => 'Three-dimensional artifact or naturally occurring object',
            't' => 'Manuscript language material',
        );

        // LDR/07
        $bibliographicLevels = array(
            'a' => 'Monographic component part',
            'b' => 'Serial component part',
            'c' => 'Collection',
            'd' => 'Subunit',
            'i' => 'Integrating resource',
            'm' => 'Monograph/Item',
            's' => 'Serial',
        );

        // 007/00 (Category of material)
        $materialCategories = array(
            'a' => 'Map',
            'c' => 'Electronic resource',
            'd' => 'Globe',
            'f' => 'Tactile material',
            'g' => 'Projected graphic',
            'h' => 'Microform',
            'k' => 'Nonprojected graphic',
            'm' => 'Motion picture',
            'o' => 'Kit',
            'q' => 'Notated music',
            'r' => 'Remote-sensing image',
            's' => 'Sound recording',
            't' => 'Text',                 // p Trykt materiale
            'v' => 'Videorecording',
            'z' => 'Unspecified',
        );

        $videoFormats = array(
            'a' => 'Beta (1/2 in., videocassette)',
            'b' => 'VHS (1/2 in., videocassette)',
            'c' => 'U-matic (3/4 in., videocasstte)',
            'd' => 'EIAJ (1/2 in., reel)',
            'e' => 'Type C (1 in., reel)',
            'f' => 'Quadruplex (1 in. or 2 in., reel)',
            'g' => 'Laserdisc',
            'h' => 'CED (Capacitance Electronic Disc) videodisc',
            'i' => 'Betacam (1/2 in., videocassette)',
            'j' => 'Betacam SP (1/2 in., videocassette)',
            'k' => 'Super-VHS (1/2 in., videocassette)',
            'm' => 'M-II (1/2 in., videocassette)',
            'o' => 'D-2 (3/4 in., videocassette)',
            'p' => '8 mm.',
            'q' => 'Hi-8 mm.',
            's' => 'Blu-ray',
            'u' => 'Unknown',
            'v' => 'DVD',
        );

        // If Leader/06 = a and Leader/07 = a, c, d, or m: Books
        // If Leader/06 = a and Leader/07 = b, i, or s: Continuing Resources
        // If Leader/06 = t: Books
        // If Leader/06 = c, d, i, or j: Music
        // If Leader/06 = e, or f: Maps
        // If Leader/06 = g, k, o, or r: Visual Materials
        // If Leader/06 = m: Computer Files
        // If Leader/06 = p: Mixed Materials

        $ldr = str_split($data->text('marc:leader'));
        $f007 = str_split($data->text('marc:controlfield[@tag="007"]'));
        $f008 = str_split($data->text('marc:controlfield[@tag="008"]'));

        $material = 'Unknown';
        $this->material = $material;
        $this->electronic = false;

        if (count($ldr) < 8) return;
        if (count($f007) < 2) return;

        switch ($ldr[6]) {

            case 'a':
                if (in_array($ldr[7], array('a','c','d','m'))) {
                    $material = 'Book';
                }
                if (in_array($ldr[7], array('b','i','s'))) {
                    $material = 'Series';
                }
                break;

            case 't':
                $material = 'Book';
                break;

            case 'c':
            case 'd':
            case 'i':
            case 'j':
                $material = 'Music';
                break;

            case 'e':
            case 'f':
                $material = 'Map';
                break;

            case 'g':
            case 'k':
            case 'o':
            case 'r':
                $material = 'Visual';
                break;

            case 'm':
                $material = 'File';
                // used for computer software, numeric data, not for e-books or e-journals!
                break;

            case 'p':
                $material = 'Mixed';
                break;

        }

        $online = ($f007[0] == 'c' && $f007[1] == 'r');

        if ($material == 'File') {
            $material = $this->getMaterialSubtypeFrom007($f007[0], $f007[1], $material);


        } else if ($material == 'Visual') {
            $material = $this->getMaterialSubtypeFrom007($f007[0], $f007[1], $material);


            if (isset($f007[4]) && isset($videoFormats[$f007[4]])) {
                $material = $videoFormats[$f007[4]]; // DVD, Blu-ray            
            }

        } else if ($material == 'Music') {
            if ($f007[0] == 't') {
                $material = 'Sheet music';
            } else {
                $material = $this->getMaterialSubtypeFrom007($f007[0], $f007[1], $material);
            }

        } else if ($material == 'Series') {
            switch ($f008[21]) {
                case 'p':
                    $material = 'Periodical';
                    break;

                case 'm':
                    $material = 'Series';  // Monographic series (merk: skiller ikke mellom 'flerbindsverk' og 'serieinnførsel')
                    break;

            }
        }

        $this->material = $material;
        $this->electronic = $online;
    }

    public function __construct(QuiteSimpleXmlElement $data) {

        $this->parseMaterial($data);

        // Control fields
        $this->id = $data->text('marc:controlfield[@tag="001"]');

        // 003: MARC code for the agency whose system control number is
        // contained in field 001 (Control Number)
        // See http://www.loc.gov/marc/authority/ecadorg.html
        $this->agency = $data->text('marc:controlfield[@tag="003"]');

        // 005: Modified
        $this->modified = $this->parseDateTime($data->text('marc:controlfield[@tag="005"]'));

        // 008: Extract *some* information
        $f008 = $data->text('marc:controlfield[@tag="008"]');
        $this->created = $this->parseDateTime(substr($f008, 0, 6));

        $authors = array();
        $subjects = array();
        $forms = array();
        $classifications = array();
        $series = array();
        $notes = array();
        $isbns = array();

        // Relationships
        $preceding = array();
        $succeeding = array();
        $part_of = array();
        $other_form = array();

        $this->is_series = false;
        $this->is_multivolume = false;

        foreach ($data->xpath('marc:datafield') as $node) {
            $marcfield = intval($node->attributes()->tag);
            switch ($marcfield) {

                // 010 - Library of Congress Control Number (NR)
                case 10:
                    $this->lccn = $node->text('marc:subfield[@code="a"]');
                    break;

                // 020 - International Standard Book Number (R)
                case 20:                                                            // Test added
                    $isbn = $node->text('marc:subfield[@code="a"]');
                    $isbn = preg_replace('/^([0-9\-xX]+).*$/', '\1', $isbn);
                    if (empty($isbn)) break;
                    array_push($isbns, $isbn);
                    break;

                // 040 - Cataloging Source (NR)
                case 40:                                                            // Test added
                    $x = $node->text('marc:subfield[@code="e"]');
                    if ($x) $this->catalogingRules = $x;
                    // Value from http://www.loc.gov/standards/sourcelist/descriptive-conventions.html
                    break;

                // 060 - National Library of Medicine Call Number (R)
                case 60:
                    $cl = array('system' => 'nlm');

                    $map = array(
                        'a' => 'number'
                    );
                    foreach ($map as $key => $val) {
                        $t = $node->text('marc:subfield[@code="' . $key . '"]');
                        if (!is_array($val)) $val = array($val);
                        if (count($val) > 2) $t = preg_replace('/' . $val[1] . '/', $val[2], $t);
                        if (!empty($t)) $cl[$val[0]] = $t;
                    }

                    $classifications[] = $cl;
                    break;

                // 080 - Universal Decimal Classification Number (R)
                case 80:
                    $cl = array('system' => 'udc');

                    $map = array(
                        'a' => array('number', '^.*?([0-9.\/:()]+).*$', '\1'),
                        '2' => 'edition',
                    );
                    foreach ($map as $key => $val) {
                        $t = $node->text('marc:subfield[@code="' . $key . '"]');
                        if (!is_array($val)) $val = array($val);
                        if (count($val) > 2) $t = preg_replace('/' . $val[1] . '/', $val[2], $t);
                        if (!empty($t)) $cl[$val[0]] = $t;
                    }

                    $classifications[] = $cl;
                    break;

                // 082 - Dewey Decimal Classification Number (R)
                case 82:
                    $cl = array('system' => 'ddc');

                    $map = array(
                        'a' => array('number', '^.*?([0-9.]+)\/?([0-9.]*).*$', '\1\2'),
                        '2' => 'edition',
                        'q' => 'assigning_agency'
                    );
                    foreach ($map as $key => $val) {
                        $t = $node->text('marc:subfield[@code="' . $key . '"]');
                        if (!is_array($val)) $val = array($val);
                        if (count($val) > 2) $t = preg_replace('/' . $val[1] . '/', $val[2], $t);
                        if (!empty($t)) $cl[$val[0]] = $t;
                    }

                    $classifications[] = $cl;
                    break;

                // 084 - Other Classification Number (R)
                case 84:
                    $cl = array();

                    $map = array(
                        'a' => 'number',
                        '2' => 'system',
                        'q' => 'assigning_agency'
                    );
                    foreach ($map as $key => $val) {
                        $t = $node->text('marc:subfield[@code="' . $key . '"]');
                        if (!is_array($val)) $val = array($val);
                        if (count($val) > 2) $t = preg_replace('/' . $val[1] . '/', $val[2], $t);
                        if (!empty($t)) $cl[$val[0]] = $t;
                    }

                    // Only add classifications with a system assigned. 
                    // "Local classification" is deprecated!
                    if (isset($cl['system'])) {
                        $classifications[] = $cl;
                    }
                    break;

                /*
                case 89:
                    if (!isset($this->klass)) $this->klass = array();
                    $klass = $node->text('marc:subfield[@code="a"]');
                    $klass = preg_replace('/[^0-9.]/', '', $klass);
                    foreach ($this->klass as $kitem) {
                        if (($kitem['kode'] == $klass) && ($kitem['system'] == 'dewey')) {
                            continue 3;
                        }
                    }
                    array_push($this->klass, array('kode' => $klass, 'system' => 'dewey'));
                    break;
                */

                case 100:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                    );
                    $author['normalizedName'] = $author['name'];
                    $spl = explode(', ', $author['name']);
                    if (count($spl) == 2) {
                        $author['name'] = $spl[1] . ' ' . $spl[0];
                    }
                    $this->parseRelator($node, $author, 'main');
                    $this->parseAuthority($node, $author);

                    $authors[] = $author;
                    break;

                case 110:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                    );
                    $this->parseRelator($node, $author, 'corporate');
                    $this->parseAuthority($node, $author);

                    $authors[] = $author;
                    break;

                case 130:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                    );
                    $spl = explode(', ', $author['name']);
                    if (count($spl) == 2) {
                        $author['name'] = $spl[1] . ' ' . $spl[0];
                    }
                    $this->parseRelator($node, $author, 'uniform_title');
                    $this->parseAuthority($node, $author);

                    $authors[] = $author;
                    break;

                // 245 : Title Statement (NR)
                case 245:
                    $title = rtrim($node->text('marc:subfield[@code="a"]'), " \t\n\r\0\x0B:-");
                    $subtitle = $node->text('marc:subfield[@code="b"]');
                    $this->title = $title;
                    if (!empty($subtitle)) {
                        $this->title .= ' : ' . $subtitle;
                    }
                    /*
                    if (preg_match('/elektronisk ressurs/', $node->text('marc:subfield[@code="h"]'))) {
                        $this->electronic = true;
                    } else {
                        $this->electronic = false;
                    }
                    */

                    // $n : Number of part/section of a work (R)
                    $part_no = $node->text('marc:subfield[@code="n"]');
                    if ($part_no !== '') $this->part_no = $part_no;

                    // $p : Name of part/section of a work (R)
                    $part_name = $node->text('marc:subfield[@code="p"]');
                    if ($part_name !== '') $this->part_name = $part_name;

                    // $h : Medium (NR)
                    $medium = $node->text('marc:subfield[@code="h"]');
                    if ($medium !== '') $this->medium = $medium;

                    break;

                case 250:
                    $this->edition = $node->text('marc:subfield[@code="a"]');
                    break;

                case 260:
                    $this->publisher = $node->text('marc:subfield[@code="b"]');
                    $y = preg_replace('/^.*?([0-9]{4}).*$/', '\1', current($node->xpath('marc:subfield[@code="c"]')));
                    $this->year = $y ? intval($y) : null;
                    break;

                case 300:
                    $this->extent = $node->text('marc:subfield[@code="a"]');
                    
                    # 2.5B2 "327 s.", 2.5B4 "48 [i.e. 96] s.", 2.5B7 "[93] s."
                    preg_match(
                        '/\[?([0-9]+)\]? (s.|p.|pp.)/',
                        $node->text('marc:subfield[@code="a"]'),
                        $matches
                    );
                    if ($matches) $this->pages = intval($matches[1]);

                    # 2.5B6 Eks: "s. 327-698" (flerbindsverk)
                    preg_match(
                        '/(s.|p.|pp.) ([0-9]+)-([0-9]+)/',
                        $node->text('marc:subfield[@code="a"]'),
                        $matches
                    );
                    if ($matches) $this->pages = intval($matches[3]) - intval($matches[2]) + 1;

                /*
                case 490:
                    $serie = array(
                        'title' => $node->text('marc:subfield[@code="a"]'),
                        'volume' => $node->text('marc:subfield[@code="v"]')
                    );
                    $this->series[] = $serie;
                    break;
                */

                // 500 : General Note (R)
                case 500:

                    // $a - General note (NR)
                    $notes[] = $node->text('marc:subfield[@code="a"]');
                    break;

                case 505:

                    // <datafield tag="520" ind1=" " ind2=" ">
                    //     <subfield code="a">"The conceptual changes brought by modern physics are important, radical and fascinating, yet they are only vaguely understood by people working outside the field. Exploring the four pillars of modern physics - relativity, quantum mechanics, elementary particles and cosmology - this clear and lively account will interest anyone who has wondered what Einstein, Bohr, Schro&#x308;dinger and Heisenberg were really talking about. The book discusses quarks and leptons, antiparticles and Feynman diagrams, curved space-time, the Big Bang and the expanding Universe. Suitable for undergraduate students in non-science as well as science subjects, it uses problems and worked examples to help readers develop an understanding of what recent advances in physics actually mean"--</subfield>
                    //     <subfield code="c">Provided by publisher.</subfield>
                    // </datafield>
                    $this->contents = $node->text('marc:subfield[@code="a"]');
                    break;

                case 520:

                    // <datafield tag="520" ind1=" " ind2=" ">
                    //     <subfield code="a">"The conceptual changes brought by modern physics are important, radical and fascinating, yet they are only vaguely understood by people working outside the field. Exploring the four pillars of modern physics - relativity, quantum mechanics, elementary particles and cosmology - this clear and lively account will interest anyone who has wondered what Einstein, Bohr, Schro&#x308;dinger and Heisenberg were really talking about. The book discusses quarks and leptons, antiparticles and Feynman diagrams, curved space-time, the Big Bang and the expanding Universe. Suitable for undergraduate students in non-science as well as science subjects, it uses problems and worked examples to help readers develop an understanding of what recent advances in physics actually mean"--</subfield>
                    //     <subfield code="c">Provided by publisher.</subfield>
                    // </datafield>
                    $this->summary = array(
                        'assigning_source' => $node->text('marc:subfield[@code="c"]'),
                        'text' => $node->text('marc:subfield[@code="a"]')
                    );
                    break;

                // 580 : Complex Linking Note (R)
                case 580:

                    if ($data->has('marc:datafield[@tag="780"]')) {
                        $preceding['note'] = $node->text('marc:subfield[@code="a"]');

                    } else if ($data->has('marc:datafield[@tag="785"]')) {
                        $succeeding['note'] = $node->text('marc:subfield[@code="a"]');

                    } else if ($data->has('marc:datafield[@tag="773"]')) {
                        $part_of['note'] = $node->text('marc:subfield[@code="a"]');
                    }
                    break;

                case 650:
                    $ind2 = $node->attr('ind2');
                    $emne = $node->text('marc:subfield[@code="a"]');

                    // topical, geographic, chronological, or form aspects
                    $tmp = array('parts' => array());

                    // $term = trim($emne, '.');
                    $tmp['term'] = trim($emne, '.');

                    $vocabularies = array(
                        '0' => 'lcsh',
                        '1' => 'lccsh', // LC subject headings for children's literature
                        '2' => 'mesh', // Medical Subject Headings
                        '3' => 'atg', // National Agricultural Library subject authority file (?)
                        // 4 : unknown
                        '5' => 'cash', // Canadian Subject Headings
                        '6' => 'rvm', // Répertoire de vedettes-matière
                    );

                    $voc = $node->text('marc:subfield[@code="2"]');
                    if (isset($vocabularies[$ind2])) {
                      $tmp['vocabulary'] = $vocabularies[$ind2];
                    } else if (!empty($voc)) {
                      $tmp['vocabulary'] = $voc;
                    }

                    $subdivtypes = array(
                        'v' => 'form',
                        'x' => 'general',
                        'y' => 'chronological',
                        'z' => 'geographic',
                    );
                    foreach ($node->xpath('marc:subfield') as $subdiv) {
                        $code = $subdiv->attr('code');
                        if (in_array($code, array_keys($subdivtypes))) {
                            $subdiv = trim($subdiv, '.');
                            $tmp['parts'][] = array('value' => $subdiv, 'type' => $subdivtypes[$code]);
                            $tmp['term'] .= '--' . $subdiv;
                        }
                    }

                    array_push($subjects, $tmp);
                    break;


                case 655:
                    $ind2 = $node->attr('ind2');
                    $emne = $node->text('marc:subfield[@code="a"]');

                    // topical, geographic, chronological, or form aspects
                    $tmp = array('parts' => array());

                    // $term = trim($emne, '.');
                    $tmp['term'] = trim($emne, '.');

                    $vocabularies = array(
                        '0' => 'lcsh',
                        '1' => 'lccsh', // LC subject headings for children's literature
                        '2' => 'mesh', // Medical Subject Headings
                        '3' => 'atg', // National Agricultural Library subject authority file (?)
                        // 4 : unknown
                        '5' => 'cash', // Canadian Subject Headings
                        '6' => 'rvm', // Répertoire de vedettes-matière
                    );

                    $voc = $node->text('marc:subfield[@code="2"]');
                    if (isset($vocabularies[$ind2])) {
                      $tmp['vocabulary'] = $vocabularies[$ind2];
                    } else if (!empty($voc)) {
                      $tmp['vocabulary'] = $voc;
                    }

                    $subdivtypes = array(
                        'v' => 'form',
                        'x' => 'general',
                        'y' => 'chronological',
                        'z' => 'geographic',
                    );
                    foreach ($node->xpath('marc:subfield') as $subdiv) {
                        $code = $subdiv->attr('code');
                        if (in_array($code, array_keys($subdivtypes))) {
                            $subdiv = trim($subdiv, '.');
                            $tmp['parts'][] = array('value' => $subdiv, 'type' => $subdivtypes[$code]);
                            $tmp['term'] .= '--' . $subdiv;
                        }
                    }

                    array_push($forms, $tmp);
                    break;

                case 700:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                    );
                    $spl = explode(', ', $author['name']);
                    if (count($spl) == 2) {
                        $author['name'] = $spl[1] . ' ' . $spl[0];
                    }

                    $this->parseRelator($node, $author, 'added');
                    $this->parseAuthority($node, $author);

                    $dates = $node->text('marc:subfield[@code="d"]');
                    if (!empty($dates)) {
                        $author['dates'] = $dates;
                    }

                    $authors[] = $author;
                    break;

                case 710:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                    );

                    $this->parseRelator($node, $author, 'added_corporate');
                    $this->parseAuthority($node, $author);

                    $authors[] = $author;
                    break;

                // 773 : Host Item Entry (R)
                // See also: 580
                case 773:
                    $part_of = isset($part_of) ? $part_of : array();
                    $part_of['relationship'] = $node->text('marc:subfield[@code="i"]');
                    $part_of['title'] = $node->text('marc:subfield[@code="t"]');
                    $part_of['issn'] = $node->text('marc:subfield[@code="x"]');
                    $part_of['isbn'] = $node->text('marc:subfield[@code="z"]');
                    $part_of['bibsys_id'] = preg_replace('/\(NO-TrBIB\)/', '', $node->text('marc:subfield[@code="w"]'));
                    $part_of['volume'] = $node->text('marc:subfield[@code="v"]');
                    break;

                // 776 : Additional Physical Form Entry (R)
                case 776:
                        // <marc:datafield tag="776" ind1="0" ind2=" ">
                        //     <marc:subfield code="z">9781107602175</marc:subfield>
                        //     <marc:subfield code="w">(NO-TrBIB)132191512</marc:subfield>
                        // </marc:datafield>
                    $other_form = $this->parseRelationship($node);
                    break;

                // 780 : Preceding Entry (R)
                // Information concerning the immediate predecessor of the target item
                case 780:
                    // <marc:datafield tag="780" ind1="0" ind2="0">
                    //     <marc:subfield code="w">(NO-TrBIB)920713874</marc:subfield>
                    //     <marc:subfield code="g">nr 80(1961)</marc:subfield>
                    // </marc:datafield>

                    if (!isset($preceding['items'])) {
                        $preceding['items'] = array();
                    }
                    $preceding['items'][] = $this->parseRelationship($node);

                    $ind2 = $node->attr('ind2');
                    $relationship_types = array(
                        '0' => 'Continues',
                        '1' => 'Continues in part',
                        '2' => 'Supersedes',
                        '3' => 'Supersedes in part',
                        '4' => 'Formed by the union of',  // ... and ...',
                        '5' => 'Absorbed',
                        '6' => 'Absorbed in part',
                        '7' => 'Separated from',
                    );
                    if (isset($relationship_types[$ind2])) {
                        $preceding['relationship_type'] = $relationship_types[$ind2];
                    }

                    break;

                // 785 : Succeeding Entry (R)
                // Information concerning the immediate successor to the target item
                case 785:
                    // <marc:datafield tag="785" ind1="0" ind2="0">
                    //     <marc:subfield code="w">(NO-TrBIB)920713874</marc:subfield>
                    //     <marc:subfield code="g">nr 80(1961)</marc:subfield>
                    // </marc:datafield>

                    if (!isset($succeeding['items'])) {
                        $succeeding['items'] = array();
                    }
                    $succeeding['items'][] = $this->parseRelationship($node);

                    $ind2 = $node->attr('ind2');
                    $relationship_types = array(
                        '0' => 'Continued by',
                        '1' => 'Continued in part by',
                        '2' => 'Superseded by',
                        '3' => 'Superseded in part by',
                        '4' => 'Absorbed by',
                        '5' => 'Absorbed in part by',
                        '6' => 'Split into',  // ... and ...',
                        '7' => 'Merged with',  // ... to form ...',
                        '8' => 'Changed back to',
                    );

                    if (isset($relationship_types[$ind2])) {
                        $succeeding['relationship_type'] = $relationship_types[$ind2];
                    }
                    break;

                // 830 : Series Added Entry – Uniform Title (R)
                case 830:
                    $serie = array(
                        'title' => $node->text('marc:subfield[@code="a"]'),
                        'id' => preg_replace('/\(NO-TrBIB\)/', '', $node->text('marc:subfield[@code="w"]')),
                        'volume' => $node->text('marc:subfield[@code="v"]')
                    );
                    $series[] = $serie;
                    break;

                case 856:
                case 956:
                    # MARC 21 uses field 856 for electronic "links", where you can have URLs for example covers images and/or blurbs.
                    # 956 ?

                        // <marc:datafield tag="856" ind1="4" ind2="2">
                        //     <marc:subfield code="3">Beskrivelse fra forlaget (kort)</marc:subfield>
                        //     <marc:subfield code="u">http://content.bibsys.no/content/?type=descr_publ_brief&amp;isbn=0521176832</marc:subfield>
                        // </marc:datafield>
                        // <marc:datafield tag="956" ind1="4" ind2="2">
                        //     <marc:subfield code="3">Omslagsbilde</marc:subfield>
                        //     <marc:subfield code="u">http://innhold.bibsys.no/bilde/forside/?size=mini&amp;id=9780521176835.jpg</marc:subfield>
                        //     <marc:subfield code="q">image/jpeg</marc:subfield>
                        // </marc:datafield>
                    $description = $node->text('marc:subfield[@code="3"]');
                    if (in_array($description, array('Cover image', 'Omslagsbilde'))) {
                        $this->cover_image = $node->text('marc:subfield[@code="u"]');

                        // Silly hack to get larger images from Bibsys:
                        $this->cover_image = str_replace('mini','stor',$this->cover_image);
                        $this->cover_image = str_replace('LITE','STOR',$this->cover_image);
                    }
                    if (in_array($description, array('Beskrivelse fra forlaget (kort)', 'Beskrivelse fra forlaget (lang)'))) {
                        $this->description = $node->text('marc:subfield[@code="u"]');
                    }
                    break;

                // 991 Kriterium für Sekundärsortierung (R) ???
                // Ref: http://ead.nb.admin.ch/web/marc21/dmarcb991.pdf
                // Hvor i BIBSYSMARC kommer dette fra?
                case 991:

                    // Multi-volume work (flerbindsverk), parts linked through 773 w
                    if ($node->text('marc:subfield[@code="a"]') == 'volumes') {
                        $this->is_multivolume = true;
                    }

                    // Series (serier), parts linked through 830 w
                    if ($node->text('marc:subfield[@code="a"]') == 'parts') {
                        $this->is_series = true;
                    }

                    break;

            }
        }

        if (!empty($preceding)) {
            $this->preceding = $preceding;
        }
        if (!empty($succeeding)) {
            $this->succeeding = $succeeding;
        }
        if (count($part_of)) {
            $this->part_of = $part_of;
        }
        if (!empty($other_form)) {
            $this->other_form = $other_form;
        }

        $this->isbns = $isbns;
        $this->series = $series;
        $this->authors = $authors;
        $this->subjects = $subjects;
        $this->forms = $forms;
        $this->classifications = $classifications;
        $this->notes = $notes;
    }

}
