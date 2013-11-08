<?php

namespace Danmichaelo\SimpleMarcParser;

class SimpleMarcParser {

    public function __construct() {

    }

    public function parse(\Danmichaelo\QuiteSimpleXmlElement\QuiteSimpleXmlElement $record) {

        $output = array();

        $output['control_number'] = $record->text('marc:controlfield[@tag="001"]');
        $output['authors'] = array();
        $output['subjects'] = array();
        $output['electronic'] = false;

        foreach ($record->xpath('marc:datafield') as $node) {
            $marcfield = intval($node->attributes()->tag);
            switch ($marcfield) {
                case 8:                                                             // ???
                    $output['form'] = $node->text('marc:subfield[@code="a"]');
                    break;
                case 10:                                                            // ???
                    $output['lccn'] = $node->text('marc:subfield[@code="a"]');
                    break;
                case 20:                                                            // Test added
                    if (!isset($output['isbn'])) $output['isbn'] = array();
                    $isbn = explode(' ', $node->text('marc:subfield[@code="a"]'));
                    array_push($output['isbn'], $isbn[0]);
                    break;
                case 82:                                                            // Test?
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

                case 100:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                        'authority' => $node->text('marc:subfield[@code="0"]'),
                        'role' => 'main'
                    );
                    $output['authors'][] = $author;
                    break;

                case 110:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                        'authority' => $node->text('marc:subfield[@code="0"]'),
                        'role' => 'corporate'
                    );
                    $output['authors'][] = $author;
                    break;
                case 245:
                    $output['title'] = $node->text('marc:subfield[@code="a"]');
                    $output['subtitle'] = $node->text('marc:subfield[@code="b"]');
                    if (preg_match('/elektronisk ressurs/', $node->text('marc:subfield[@code="h"]'))) {
                        $output['electronic'] = true;
                    }
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
                case 490:
                case 491:
                    $output['series'] = array();
                    $output['series']['title'] = $node->text('marc:subfield[@code="a"]');
                    $output['series']['objectid'] = $node->text('marc:subfield[@code="n"]');
                    $output['series']['volume'] = $node->text('marc:subfield[@code="v"]');
                    break;

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
                      $tmp = array('emne' => trim($emne, '.'));

                      $system = $node->text('marc:subfield[@code="2"]');
                      if ($system !== false) $tmp['system'] = $system;

                      $subdiv = $node->text('marc:subfield[@code="x"]');
                      if ($subdiv !== false) $tmp['subdiv'] = trim($subdiv, '.');

                      $time = $node->text('marc:subfield[@code="y"]');
                      if ($time !== false) $tmp['time'] = $time;

                      $geo = $node->text('marc:subfield[@code="z"]');
                      if ($geo !== false) $tmp['geo'] = $geo;

                      array_push($output['subjects'], $tmp);
                    break;

                case 700:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                        'authority' => $node->text('marc:subfield[@code="0"]'),
                        'role' => 'added'
                    );
                    $output['authors'][] = $author;
                    break;

                case 710:
                    $author = array(
                        'name' => $node->text('marc:subfield[@code="a"]'),
                        'authority' => $node->text('marc:subfield[@code="0"]'),
                        'role' => 'added_corporate'
                    );
                    $output['authors'][] = $author;
                    break;

                case 776:
                        // <marc:datafield tag="776" ind1="0" ind2=" ">
                        //     <marc:subfield code="z">9781107602175</marc:subfield>
                        //     <marc:subfield code="w">(NO-TrBIB)132191512</marc:subfield>
                        // </marc:datafield>
                    $form = array(
                        'isbn' => $node->text('marc:subfield[@code="z"]'),
                        'recordid' => preg_replace('/\(NO-TrBIB\)/', '', $node->text('marc:subfield[@code="w"]'))
                    );
                    $output['other_form'] = $form;
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
                    $desc = $node->text('marc:subfield[@code="3"]');
                    if (in_array($desc, array('Cover image', 'Omslagsbilde'))) {
                        $output['cover_image'] = $node->text('marc:subfield[@code="u"]');

                        // Silly hack to get larger images from Bibsys:
                        $output['cover_image'] = str_replace('mini','stor',$output['cover_image']);
                        $output['cover_image'] = str_replace('LITE','STOR',$output['cover_image']);
                    }
                    if (in_array($desc, array('Beskrivelse fra forlaget (kort)', 'Beskrivelse fra forlaget (lang)'))) {
                        $output['description'] = $node->text('marc:subfield[@code="u"]');
                    }
                    break;

            }
        }
        return $output;
    }

}