<?php namespace Danmichaelo\SimpleMarcParser;

use Danmichaelo\QuiteSimpleXmlElement\QuiteSimpleXmlElement;

class BibliographicParser {

    public function __construct() {

    }

    public function parse(QuiteSimpleXmlElement $record) {

        $output = array();

        $output['id'] = $record->text('marc:controlfield[@tag="001"]');
        $output['authors'] = array();
        $output['subjects'] = array();
        $output['series'] = array();
        $output['electronic'] = false;
        $output['is_series'] = false;
        $output['is_multivolume'] = false;
        $output['fulltext'] = array();
        $output['classifications'] = array();

        foreach ($record->xpath('marc:datafield') as $node) {
            $marcfield = intval($node->attributes()->tag);
            switch ($marcfield) {
                /*
                case 8:                                                             // ???
                    $output['form'] = $node->text('marc:subfield[@code="a"]');
                    break;
                */

                // 010 - Library of Congress Control Number (NR)
                case 10:
                    $output['lccn'] = $node->text('marc:subfield[@code="a"]');
                    break;

                // 020 - International Standard Book Number (R)
                case 20:                                                            // Test added
                    $isbn = $node->text('marc:subfield[@code="a"]');
                    $isbn = preg_replace('/^([0-9\-]+).*$/', '\1', $isbn);
                    if (empty($isbn)) break;
                    if (!isset($output['isbn'])) $output['isbn'] = array();
                    array_push($output['isbn'], $isbn);
                    break;

                // 082 - Dewey Decimal Classification Number (R)
                case 82:                                                            // Test?
                    if (!isset($output['classifications'])) $output['classifications'] = array();
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

                    $output['classifications'][] = $cl;
                    break;

                /*
                case 89:
                    if (!isset($output['klass'])) $output['klass'] = array();
                    $klass = $node->text('marc:subfield[@code="a"]');
                    $klass = preg_replace('/[^0-9.]/', '', $klass);
                    foreach ($output['klass'] as $kitem) {
                        if (($kitem['kode'] == $klass) && ($kitem['system'] == 'dewey')) {
                            continue 3;
                        }
                    }
                    array_push($output['klass'], array('kode' => $klass, 'system' => 'dewey'));
                    break;
                */

                case 100:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                        'role' => 'main'
                    );
                    $authority = $node->text('marc:subfield[@code="0"]');
                    if (!empty($authority)) $author['authority'] = $authority;

                    $output['authors'][] = $author;
                    break;

                case 110:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                        'role' => 'corporate'
                    );
                    $authority = $node->text('marc:subfield[@code="0"]');
                    if (!empty($authority)) $author['authority'] = $authority;

                    $output['authors'][] = $author;
                    break;

                case 130:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                        'role' => 'uniform'
                    );
                    $authority = $node->text('marc:subfield[@code="0"]');
                    if (!empty($authority)) $author['authority'] = $authority;

                    $output['authors'][] = $author;
                    break;

                // 245 : Title Statement (NR)
                case 245:
                    $output['title'] = $node->text('marc:subfield[@code="a"]');
                    $output['subtitle'] = $node->text('marc:subfield[@code="b"]');
                    if (preg_match('/elektronisk ressurs/', $node->text('marc:subfield[@code="h"]'))) {
                        $output['electronic'] = true;
                    }

                    // $n : Number of part/section of a work (R)
                    $part_no = $node->text('marc:subfield[@code="n"]');
                    if ($part_no !== '') $output['part_no'] = $part_no;

                    // $p : Name of part/section of a work (R)
                    $part_name = $node->text('marc:subfield[@code="p"]');
                    if ($part_name !== '') $output['part_name'] = $part_name;

                    // $h : Medium (NR)
                    $medium = $node->text('marc:subfield[@code="h"]');
                    if ($medium !== '') $output['medium'] = $medium;

                    break;
                case 250:
                    $output['edition'] = $node->text('marc:subfield[@code="a"]');
                    break;
                case 260:
                    $output['publisher'] = $node->text('marc:subfield[@code="b"]');
                    $output['year'] = preg_replace('/[^0-9,]|,[0-9]*$/', '', current($node->xpath('marc:subfield[@code="c"]')));
                    break;
                case 300:
                    $output['pages'] = $node->text('marc:subfield[@code="a"]');
                    break;

                /*
                case 490:
                    $serie = array(
                        'title' => $node->text('marc:subfield[@code="a"]'),
                        'volume' => $node->text('marc:subfield[@code="v"]')
                    );
                    $output['series'][] = $serie;
                    break;
                */

                case 505:

                    // <datafield tag="520" ind1=" " ind2=" ">
                    //     <subfield code="a">"The conceptual changes brought by modern physics are important, radical and fascinating, yet they are only vaguely understood by people working outside the field. Exploring the four pillars of modern physics - relativity, quantum mechanics, elementary particles and cosmology - this clear and lively account will interest anyone who has wondered what Einstein, Bohr, Schro&#x308;dinger and Heisenberg were really talking about. The book discusses quarks and leptons, antiparticles and Feynman diagrams, curved space-time, the Big Bang and the expanding Universe. Suitable for undergraduate students in non-science as well as science subjects, it uses problems and worked examples to help readers develop an understanding of what recent advances in physics actually mean"--</subfield>
                    //     <subfield code="c">Provided by publisher.</subfield>
                    // </datafield>
                    $output['contents'] = $node->text('marc:subfield[@code="a"]');
                    break;

                case 520:

                    // <datafield tag="520" ind1=" " ind2=" ">
                    //     <subfield code="a">"The conceptual changes brought by modern physics are important, radical and fascinating, yet they are only vaguely understood by people working outside the field. Exploring the four pillars of modern physics - relativity, quantum mechanics, elementary particles and cosmology - this clear and lively account will interest anyone who has wondered what Einstein, Bohr, Schro&#x308;dinger and Heisenberg were really talking about. The book discusses quarks and leptons, antiparticles and Feynman diagrams, curved space-time, the Big Bang and the expanding Universe. Suitable for undergraduate students in non-science as well as science subjects, it uses problems and worked examples to help readers develop an understanding of what recent advances in physics actually mean"--</subfield>
                    //     <subfield code="c">Provided by publisher.</subfield>
                    // </datafield>
                    $output['summary'] = array(
                        'assigning_source' => $node->text('marc:subfield[@code="c"]'),
                        'text' => $node->text('marc:subfield[@code="a"]')
                    );
                    break;

                case 650:
                    $emne = $node->text('marc:subfield[@code="a"]');
                      $tmp = array();

                      $term = trim($emne, '.');
                      if ($term !== '') $tmp['term'] = $term;

                      $system = $node->text('marc:subfield[@code="2"]');
                      if ($system !== '') $tmp['system'] = $system;

                      $subdiv = $node->text('marc:subfield[@code="x"]');
                      if ($subdiv !== '') $tmp['subdiv'] = trim($subdiv, '.');

                      $time = $node->text('marc:subfield[@code="y"]');
                      if ($time !== '') $tmp['time'] = $time;

                      $geo = $node->text('marc:subfield[@code="z"]');
                      if ($geo !== '') $tmp['geo'] = $geo;

                      array_push($output['subjects'], $tmp);
                    break;

                case 700:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                        'role' => 'added'
                    );
                    $authority = $node->text('marc:subfield[@code="0"]');
                    if (!empty($authority)) $author['authority'] = $authority;

                    $output['authors'][] = $author;
                    break;

                case 710:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                        'role' => 'added_corporate'
                    );
                    $authority = $node->text('marc:subfield[@code="0"]');
                    if (!empty($authority)) $author['authority'] = $authority;

                    $output['authors'][] = $author;
                    break;

                case 773:
                    $output['part_of'] = array();
                    $output['part_of']['relationship'] = $node->text('marc:subfield[@code="i"]');
                    $output['part_of']['title'] = $node->text('marc:subfield[@code="t"]');
                    $output['part_of']['issn'] = $node->text('marc:subfield[@code="x"]');
                    $output['part_of']['id'] = preg_replace('/\(NO-TrBIB\)/', '', $node->text('marc:subfield[@code="w"]'));
                    $output['part_of']['volume'] = $node->text('marc:subfield[@code="v"]');
                    break;

                // 776 : Additional Physical Form Entry (R)
                case 776:
                        // <marc:datafield tag="776" ind1="0" ind2=" ">
                        //     <marc:subfield code="z">9781107602175</marc:subfield>
                        //     <marc:subfield code="w">(NO-TrBIB)132191512</marc:subfield>
                        // </marc:datafield>
                    $form = array(
                        'isbn' => $node->text('marc:subfield[@code="z"]'),
                        'id' => preg_replace('/\(NO-TrBIB\)/', '', $node->text('marc:subfield[@code="w"]'))
                    );
                    $output['other_form'] = $form; // TODO: Should be array!
                    break;

                // 830 : Series Added Entry – Uniform Title (R)
                case 830:
                    $serie = array(
                        'title' => $node->text('marc:subfield[@code="a"]'),
                        'id' => preg_replace('/\(NO-TrBIB\)/', '', $node->text('marc:subfield[@code="w"]')),
                        'volume' => $node->text('marc:subfield[@code="v"]')
                    );
                    $output['series'][] = $serie;
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
                        $output['cover_image'] = $node->text('marc:subfield[@code="u"]');

                        // Silly hack to get larger images from Bibsys:
                        $output['cover_image'] = str_replace('mini','stor',$output['cover_image']);
                        $output['cover_image'] = str_replace('LITE','STOR',$output['cover_image']);
                    }
                    if (in_array($description, array('Beskrivelse fra forlaget (kort)', 'Beskrivelse fra forlaget (lang)'))) {
                        $output['description'] = $node->text('marc:subfield[@code="u"]');
                    }
                    break;

                // 991 Kriterium für Sekundärsortierung (R) ???
                // Ref: http://ead.nb.admin.ch/web/marc21/dmarcb991.pdf
                // Hvor i BIBSYSMARC kommer dette fra?
                case 991:

                    // Multi-volume work (flerbindsverk), parts linked through 773 w
                    if ($node->text('marc:subfield[@code="a"]') == 'volumes') {
                        $output['is_multivolume'] = true;
                    }

                    // Series (serier), parts linked through 830 w
                    if ($node->text('marc:subfield[@code="a"]') == 'parts') {
                        $output['is_series'] = true;
                    }

                    break;

            }
        }
        return $output;
    }

}