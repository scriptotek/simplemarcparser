<?php namespace Scriptotek\SimpleMarcParser;

use Illuminate\Support\Contracts\JsonableInterface;
use Danmichaelo\QuiteSimpleXmlElement\QuiteSimpleXmlElement;

class BibliographicRecord extends Record implements JsonableInterface {

    public function __construct(QuiteSimpleXmlElement $data) {

        $leader = $data->text('marc:leader');
        // Field 008/18-34 Configuration
        // If Leader/06 = a and Leader/07 = a, c, d, or m: Books
        // If Leader/06 = a and Leader/07 = b, i, or s: Continuing Resources
        // If Leader/06 = t: Books
        // If Leader/06 = c, d, i, or j: Music
        // If Leader/06 = e, or f: Maps
        // If Leader/06 = g, k, o, or r: Visual Materials
        // If Leader/06 = m: Computer Files
        // If Leader/06 = p: Mixed Materials

        $l6 = substr($leader, 6, 1);
        $l7 = substr($leader, 7, 1);
        $material = '';
        if ($l6 == 'a' && in_array($l7, array('a','c','d','m'))) {
            $material = 'book';
        }
        if ($l6 == 't') {
            $material = 'book';
        }
        if ($l6 == 'a' && in_array($l7, array('b','i','s'))) {
            $material = 'series';
        }
        if (in_array($l6, array('c','d','i','j'))) {
            $material = 'music';
        }
        if (in_array($l6, array('e','f'))) {
            $material = 'map';
        }
        if (in_array($l6, array('g','k','o','r'))) {
            $material = 'visual';
        }
        if ($l6 == 'm') {
            $material = 'file';
        }
        if ($l6 == 'p') {
            $material = 'mixed';
        }
        $this->material = $material;

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

                // 082 - Dewey Decimal Classification Number (R)
                case 82:                                                            // Test?
                    $cl = array('system' => 'dewey');

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
                        'role' => 'main'
                    );
                    $spl = explode(', ', $author['name']);
                    if (count($spl) == 2) {
                        $author['name'] = $spl[1] . ' ' . $spl[0];
                    }
                    $this->parseAuthority($node, $author);

                    $authors[] = $author;
                    break;

                case 110:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                        'role' => 'corporate'
                    );
                    $this->parseAuthority($node, $author);

                    $authors[] = $author;
                    break;

                case 130:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                        'role' => 'uniform'
                    );
                    $spl = explode(', ', $author['name']);
                    if (count($spl) == 2) {
                        $author['name'] = $spl[1] . ' ' . $spl[0];
                    }
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
                    if (preg_match('/elektronisk ressurs/', $node->text('marc:subfield[@code="h"]'))) {
                        $this->electronic = true;
                    } else {
                        $this->electronic = false;
                    }

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
                    preg_match(
                        '/([0-9]+) (s.|p.|pp.)/',
                        $node->text('marc:subfield[@code="a"]'),
                        $matches
                    );
                    if ($matches) {
                        $this->pages = intval($matches[1]);
                    }
                    break;

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
                      $tmp = array('subdivisions' => array());

                      $term = trim($emne, '.');
                      if ($term !== '') $tmp['term'] = $term;

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

                      $topical = $node->text('marc:subfield[@code="x"]');
                      if ($topical !== '') $tmp['subdivisions']['topical'] = trim($topical, '.');

                      $chrono = $node->text('marc:subfield[@code="y"]');
                      if ($chrono !== '') $tmp['subdivisions']['chronological'] = $chrono;

                      $geo = $node->text('marc:subfield[@code="z"]');
                      if ($geo !== '') $tmp['subdivisions']['geographic'] = $geo;

                      $form = $node->text('marc:subfield[@code="v"]');
                      if ($form !== '') $tmp['subdivisions']['form'] = $form;

                      array_push($subjects, $tmp);
                    break;

                case 700:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                    );
                    $author['role'] = $node->text('marc:subfield[@code="4"]') 
                        ?: ($node->text('marc:subfield[@code="e"]') ?: 'added');
                    $spl = explode(', ', $author['name']);
                    if (count($spl) == 2) {
                        $author['name'] = $spl[1] . ' ' . $spl[0];
                    }

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
                        'role' => 'added_corporate'
                    );
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
                    $part_of['id'] = preg_replace('/\(NO-TrBIB\)/', '', $node->text('marc:subfield[@code="w"]'));
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

        $this->preceding = $preceding;
        $this->succeeding = $succeeding;
        $this->part_of = $part_of;
        $this->other_form = $other_form;

        $this->isbns = $isbns;
        $this->series = $series;
        $this->authors = $authors;
        $this->subjects = $subjects;
        $this->classifications = $classifications;
        $this->notes = $notes;
    }

}
