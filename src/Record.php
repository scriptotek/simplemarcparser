<?php namespace Scriptotek\SimpleMarcParser;

use Illuminate\Support\Contracts\JsonableInterface;

class Record {

    protected $data;

    public function __get($name) {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
    }

    public function __set($name, $value) {
        if ($value === false || !empty($value)) {
            $this->data[$name] = $value;
        }
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options=0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
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
