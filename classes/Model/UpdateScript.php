<?php defined('SYSPATH') or die('No direct script access.');
/**
 * UpdateScript Model.
 *
 * @package    DBUpdate
 * @category   Models
 * @author     Veraida Pty Ltd
 * @copyright  (c) 2014 Veraida Pty Ltd
 * @license    http://kohanaframework.org/license
 */

class Model_UpdateScript {

    const TYPE_UPDATE = 'UPDATE';
    const TYPE_COMPLETE = 'COMPLETE';
    
    public $filepath;
    public $directory;
    public $filename;
    public $type;
    public $version;
    public $ext;
    
    public $scripts;

    public function __construct($filename)
    {
        $path_parts = pathinfo($filename);
        $fileparts = preg_split("/[_.]+/", $path_parts['filename']);
       
        $this->filepath = $filename;
        $this->directory = $path_parts['dirname'];
        $this->filename = $path_parts['filename'];
        $this->type = $fileparts[0];
        $this->version = $fileparts[1].'.'.$fileparts[2].'.'.$fileparts[3];
        $this->ext = $path_parts['extension'];
        
        $this->scripts = $this->split_file();
    }
    
    public function is_update()
    {
        return (strtoupper($this->type) == Model_UpdateScript::TYPE_UPDATE);
    }
    
    public function is_complete()
    {
        return (strtoupper($this->type) == Model_UpdateScript::TYPE_COMPLETE);
    }
    
    public function is_newer($current_version)
    {
        if (version_compare($this->version, $current_version) > 0)
        {
            return true;
        }
        return false;
    }
    
    private function split_file()
    {
        $contents = Filesystem::read_file($this->filepath);
        $fileparts = preg_split("/[;]+/", $contents);
        
        return array_filter($fileparts, array($this, 'is_script'));
    }
    
    private function is_script($script)
    {
        return (strlen(trim($script)) > 0);
    }
}