<?php namespace Scriptotek\SimpleMarcParser;

class Record {

    protected $data;

    public function __get($name) {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
    }

    protected function parseAuthority(&$node, &$out) {
        $authority = $node->text('marc:subfield[@code="0"]');
        if (!empty($authority)) {
            $out['authority'] = $authority;
            $asplit = explode(')', $authority);
            if (substr($authority, 1, 8) === 'NO-TrBIB') {
                $out['bibsys_identifier'] = substr($authority, strpos($authority, ')') + 1);
            }
        }
    }

    protected function parseRelationship($node)
    {
        $rel = array();

        $x = preg_replace('/\(.*?\)/', '', $node->text('marc:subfield[@code="w"]'));
        if (!empty($x)) $rel['id'] = $x;

        $x = $node->text('marc:subfield[@code="t"]');
        if (!empty($x)) $rel['title'] = $x;

        $x = $node->text('marc:subfield[@code="g"]');
        if (!empty($x)) $rel['related_parts'] = $x;

        $x = $node->text('marc:subfield[@code="x"]');
        if (!empty($x)) $rel['issn'] = $x;

        $x = $node->text('marc:subfield[@code="z"]');
        if (!empty($x)) $rel['isbn'] = $x;

        return $rel;
    }
}
