<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Database update controller.
 *
 * @package    DBUpdate
 * @category   Helpers
 * @author     Veraida Pty Ltd
 * @copyright  (c) 2014 Veraida Pty Ltd
 * @license    http://kohanaframework.org/license
 */

class Controller_Update extends Controller {
    
    private $db;
    private $current_version;
    
    public function before()
    {
        $this->db = Database::instance();
        $this->current_version = $this->current_db_version();
    }

    public function action_index()
    {
        $scripts = $this->get_scripts();
        $this->db->begin();
        try {
            foreach ($scripts as $script)
            {
                if ($script->is_newer($this->current_version))
                {
                    $script->run();
                    $this->update_db_version($script);
                }
            }
            $this->db->commit();
        } 
        catch (Database_Exception $ex) {
            $this->db->rollback();
            Log::instance()->add(Log::ERROR, 'Error Upgrading database: '.$ex->getMessage());
        }
    }
    
    private function get_scripts()
    {
        if ($this->version_table_exists()) {
            return $this->get_update_scripts();
        } else {
            return $this->get_complete_scripts();
        }
    }
    
    private function config()
    {
        return Kohana::$config->load('update')->get('default');
    }
    
    private function current_db_version()
    {
        if ($this->version_table_exists()) {
            $results = DB::query(
                    Database::SELECT, 
                    'SELECT version FROM `db_version` ORDER BY id DESC LIMIT 1'
            )->execute();
            return $results[0]['version'];
        } else {
            return '0.0.0';
        }
    }
    
    private function update_db_version($script)
    {
        DB::insert('db_version', array('version', 'filename', 'stamp_created'))
            ->values(array(
                $script->version,
                $script->filename,
                null
            ))
            ->execute();
    }
    
    private function sort_scripts_version($a, $b)
    {
        return version_compare($a->version, $b->version);
    }
    
    private function convert_file_array(&$item)
    {
        $item = new Model_UpdateScript($item);
    }
    
    private function version_table_exists()
    {
        $tables = Database::instance()->list_tables('%db_version');
        return (count($tables) > 0);
    }
    
    private function get_update_scripts()
    {
        $config = Kohana::$config->load('update')->get('default');
        $scripts = Kohana::list_files($config['scripts_path']);
        array_walk($scripts, array($this, 'convert_file_array'));
        usort($scripts, array($this, 'sort_scripts_version'));
        return array_filter($scripts, array($this, 'is_update_script'));
    }
    
    private function get_complete_scripts()
    {
        $config = Kohana::$config->load('update')->get('default');
        $scripts = Kohana::list_files($config['scripts_path']);
        array_walk($scripts, array($this, 'convert_file_array'));
        usort($scripts, array($this, 'sort_scripts_version'));
        return array_filter($scripts, array($this, 'is_complete_script'));
    }
    
    private function is_update_script($script)
    {
        return ($script->is_update());
    }
    
    private function is_complete_script($script)
    {
        return ($script->is_complete());
    }
}