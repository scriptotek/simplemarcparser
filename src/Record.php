<?php namespace Scriptotek\SimpleMarcParser;

use Illuminate\Support\Contracts\JsonableInterface;
use Carbon\Carbon;

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

    public function __isset($name) {
       return isset($this->data[$name]);
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

    /**
     * Parse a *Representation of Dates and Times* (ISO 8601).
     * The date requires 8 numeric characters in the pattern yyyymmdd. 
     * The time requires 8 numeric characters in the pattern hhmmss.f, 
     * expressed in terms of the 24-hour (00-23) clock.
     *
     * @param  string $value
     * @return Carbon\Carbon
     */
    protected function parseDateTime($value)
    {
        if (strlen($value) == 6) return Carbon::createFromFormat('ymdHis', $value. '000000');
        if (strlen($value) == 8) return Carbon::createFromFormat('YmdHis', $value . '000000');
        if (strlen($value) == 16) return Carbon::createFromFormat('YmdHis', substr($value, 0, 14)); // skip decimal fraction
    }

    /**
     * Parse a "name node", personal or corporate, main or added, that
     * might have authority information encapsulated.
     *
     * @param  Danmichaelo\QuiteSimpleXmlElement\QuiteSimpleXmlElement &$node
     * @param  array &$out
     */
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

    /**
     * Parse a "relationship node", one that have links to other records encapsulated.
     *
     * @param  Danmichaelo\QuiteSimpleXmlElement\QuiteSimpleXmlElement $node
     * @return array
     */
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
