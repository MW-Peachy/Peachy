<?php

/**
 * @author Sam Korn <smoddy@gmail.com>
 * @author Cyberpower678 <maximilian.doerr@gmail.com>
 * @copyright Copyright (c) 2009, Sam Korn
 * @license http://opensource.org/licenses/mit-license.php MIT License
*/

/**
 * Modify and use templates
 * 
 * Facilitate the isolation and editing of templates.
 * 
 * @todo Convert this to Peachy standards
 */
class Template {
    /**
     * Text preceeding the template
     * @var string
     */
    private $before;
    /**
     * Text of the template on creation
     * @var string
     */
    private $templatestring;
    /**
     * Text that follows the template
     * @var string
     */
    private $after;
    /**
     * Double curly-bracket and any white-space before the template's name
     * @var string
     */
    private $open;
    /**
     * Name of template
     * @var string
     */
    private $name;
    /**
     * Array of fields of the template
     * 
     * The keys of the array are the names of the fields as they appear to MediaWiki.
     * 
     * @var array
     */
    private $fields;
    /**
     * The double curly-brakced that closes the template
     * @var string
     */
    private $end;
    
    /**
     * Extract a template from a string
     * 
     * Find the template $name in the string $text and build template from this.  Only 
     * the first occurence will be found -- others must be found using {@link Template::$after}.
     * 
     * @param string $text Text to find template in
     * @param string $name Name of the temlate to find
     */
    public function __construct($text,$name) {
        if (false === ($from = stripos($text,'{{' . $name))) {
            unset ($this);
            return;
        }
        
        $name = "(?i:" . substr(preg_quote($name,'/'),0,1) . ")" . substr($name,1);
        preg_match("/\{\{" . $name . "\s*(?:(?:\|.*)|(?:\}.*))/s",$text,$match);
        if (isset($match[0])) {
            $from = stripos($text,$match[0]);
        } else {
            unset ($this);
            return;
        }
        
        $i = 2;
        $counter = 2;
        
        while (strlen($match[0]) > $i) {
            if ($match[0][$i] == '{') {
                $counter++;
            }
            elseif ($match[0][$i] == '}') {
                $counter--;
            }
            
            if ($counter == 1) {
                $end = $i + 2;
                
                $this->before = substr($text,0,$from);
                $this->templatestring = substr($text,$from,$end);
                $this->after = substr($match[0],$end);
                
                break;
            }
            
            $i++;
        }
        
        preg_match('/(\{\{\s*)([^|}]*)(.*)/s',$this->templatestring,$match);
        
        $this->open = $match[1];
        $this->name = $match[2];
        
        if (false === strpos($this->templatestring,'|')) {
            $this->fields = array();
            $this->end = '}}';
            return;
        }
        
        $subtemplate = 0;
        $current = '';
        $link = false;
        $lastletter = '';
        
        for ($i = 0 ; $i < strlen($match[3]) ; $i++) {
            if ($current && !$subtemplate && !$link && (($match[3][$i] == '|') || ($match[3][$i] == '}'))) {
                $fields[] = $current;
                $current = '';
                $lastletter = $match[3][$i];
                continue;
            }
            
            if (!$current && !$subtemplate && !$link && ($lastletter == '}') && ($match[3][$i] == '}')) {
                break;
            }
            
            if (($lastletter == '{') && ($match[3][$i] == '{')) {
                $subtemplate++;
            }
            
            if (($lastletter == '}') && ($match[3][$i] == '}')) {
                $subtemplate--;
            }
            
            if ($link && ($lastletter == ']') && ($match[3][$i] == ']')) {
                $link = false;
            }
            
            if (!$link && ($lastletter == '[') && ($match[3][$i] == '[')) {
                $link = true;
            }
            
            if (!$subtemplate && !$current && !$link && ($match[3][$i] == '|') && ($lastletter == '|')) {
                $fields[] = '';
            }
            
            if ((!$subtemplate && !$link && ($match[3][$i] != '|')) || $subtemplate || $link) {
                $current .= $match[3][$i];
            }
            
            $lastletter = $match[3][$i];
        }
        
        $last = (count($fields)) - 1;
        if ($fields[$last] == '}') { //this is actually impossible to set, so it will always indicate an empty field
            $fields[$last] = '';
        }
        
        $i = 0;
        
        if ($fields) {
            foreach ($fields as $field) {
                if (preg_match('/\s*([^=\|\}]*?)\s*=/',$field,$match)) {
                    $this->fields[$match[1]] = $field;
                } else {
                    $this->fields[++$i] = $field;
                }
            }
        }
        
        $this->end = '}}';
    }
    
    public function __get($var) {
        return $this->$var;
    }
    public function __set($var,$val) {
        return;
    }
    
    public function __toString() {
        $return = $this->open;
        $return .= $this->name;
        if (count($this->fields != 0)) {
            foreach ($this->fields as $field) {
                $return .= "|$field";
            }
        }
        $return .= $this->end;
        return $return;
    }
    
    /**
     * Return the string used in {@link __construct()} with the new template.
     * 
     * @return string
     */
    public function wholePage() {
        return $this->before . ((string) $this) . $this->after;
    }
    
    /**
     * Get the value of a field
     * 
     * @param string $fieldname Name of the field to find
     * @return string|boolean Value of template if it exists, otherwise boolean false
     */
    public function fieldvalue($fieldname) {
    	if( is_numeric($fieldname)) {
    		return trim($this->fields[$fieldname]);
    	}
        if (isset($this->fields[$fieldname])) {
            return trim(substr($this->fields[$fieldname],strpos($this->fields[$fieldname],'=') + 1));
        } else {
            return false;
        }
    }
    
    /**
     * Change the name of a field
     * 
     * @param string $oldname Name of the field to migrate
     * @param string $newname New name of the field
     */
    public function renamefield($oldname,$newname) {
        foreach ($this->fields as $name => $field) {
            if ($name != $oldname) {
                $newfields[$name] = $field;
                continue;
            }
            
            $newfields[$newname] = preg_replace('/^(\s*)' . preg_quote($oldname,'/') . '(\s*=)/is',"$1" . $newname . "$2",$field);
        }
        $this->fields = $newfields;
    }
    
    /**
     * Delete a field
     * 
     * @param string $fieldname Name of field to delete
     */
    public function removefield($fieldname) {
        unset($this->fields[$fieldname]);
    }
    
    /** 
     * Rename template
     * 
     * @param string $newname New name of template
     */
    public function rename($newname) {
        $this->name = $newname;
    }
    
    /** 
     * Add a field to the template
     * 
     * If the fieldname is not given, the parameter will be added effectively as 
     * a numbered parameter.
     * 
     * @param string $value Value of parameter
     * @param string fieldname Name of parameter
     */
    public function addfield($value,$fieldname = '') {
        if (!$fieldname) {
            $fieldname = (max(array_keys($this->fields)) + 1);
        } else {
            $this->fields[$fieldname] = $fieldname . ' = ';
        }
        
        $this->fields[$fieldname] .= $value;
    }
    
    /**
     * Does the field exist?
     * 
     * If a field with the name specified by $fieldname exists, return true. Else, return false/
     * 
     * @param string $fieldname Name of field to search for
     * @return boolean
     */
    public function fieldisset($fieldname) {
        if (isset($this->fields[$fieldname])) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Update field value
     * 
     * Update the value of field $fieldname to $value.
     * 
     * If the field does not exist, add it.
     * 
     * @param string $fieldname Name of field to update
     * @param string $value Value to update to
     */
    public function updatefield($fieldname,$value) {
        if (!$this->fieldisset($fieldname)) {
            $this->addfield($value,$fieldname);
            return;
        }
        
        $oldvalue = $this->fieldvalue($fieldname);
        $this->fields[$fieldname] = preg_replace('/^(.*?=\s*)' . preg_quote($oldvalue,'(\s*)/') . '\s*/is',"$1$value$2",$this->fields[$fieldname]);
    }
    
    public static function extract ($text, $name) {
        $template = new Template ($text,$name);
        
        if (!$template->name) {
            unset($template);
            return false;
        } else {
            return $template;
        }
    }
}