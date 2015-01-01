<?php namespace Scriptotek\SimpleMarcParser;

use Carbon\Carbon;
use Danmichaelo\QuiteSimpleXmlElement\QuiteSimpleXmlElement;

class Record {

    protected $data;

    public function __get($name) {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
    }

    public function __set($name, $value) {
        if (is_null($value)) {
            unset($this->data[$name]);
        } else if (is_string($value) && empty($value)) {
            unset($this->data[$name]);
        } else {
            $this->data[$name] = $value;
        }
        // }
    }

    public function __isset($name) {
       return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
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
     * @return Carbon|null
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
     * @param  string $authority
     * @param  array &$out
     */
    protected function parseAuthority($authority, &$out) {
        if (!empty($authority)) {
            $out['id'] = $authority;
            if (preg_match('/\((.*?)\)(.*)/', $authority, $matches)) {
                // As used by at least OCLC and Bibsys
                $out['vocabulary'] = $matches[1];
                $out['id'] = $matches[2];
            }
        }
    }

    /**
     * Parse a "name node", personal or corporate, main or added, that
     * might have relators encapsulated.
     *
     * @param  QuiteSimpleXmlElement &$node
     * @param  array &$out
     * @param  string $default
     */
    protected function parseRelator(&$node, &$out, $default=null) {
        $relterm = $node->text('marc:subfield[@code="e"]');
        $relcode = $node->text('marc:subfield[@code="4"]');
        if (!empty($relcode)) {
            $out['role'] = $relcode;
        } elseif (!empty($relterm)) {
            $out['role'] = $relterm;
        } elseif (!is_null($default)) {
            $out['role'] = $default;            
        }
    }

    /**
     * Parse a "relationship node", one that have links to other records encapsulated.
     *
     * @param  QuiteSimpleXmlElement $node
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
