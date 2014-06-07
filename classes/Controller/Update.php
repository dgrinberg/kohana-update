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
    
    private $break_on_error;
    private $replay;
    
    private $events;
    
    public function before()
    {
        $this->db = Database::instance();
        $this->current_version = $this->current_db_version();
    }

    public function action_index()
    {
        if ($this->request->query('breakOnError') != NULL) {
            $this->break_on_error = $this->request->query('breakOnError');
        } else {
            $this->break_on_error = false;
        }
        
        if ($this->request->query('replay') != NULL) {
            $this->replay = $this->request->query('replay');
        } else {
            $this->replay = false;
        }
        
        $this->log_header();
        
        $this->log('Current database version :- '. $this->current_version);
        
        $files = $this->get_files();
        foreach ($files as $file)
        {
            if ($file->is_newer($this->current_version) || $this->replay)
            {
                $this->execute_file($file);
            }
        }
        
        $this->log_footer();
    }
    
    private function get_files()
    {
        if ($this->version_table_exists()) {
            return $this->get_update_files();
        } else {
            return $this->get_complete_files();
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
    
    private function update_db_version($file)
    {
        DB::insert('db_version', array('version', 'filename', 'stamp_created'))
            ->values(array(
                $file->version,
                $file->filename,
                null
            ))
            ->execute();
        
        $this->log('Updated to version - '.$file->version);
    }
    
    private function sort_files_version($a, $b)
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
    
    private function get_update_files()
    {
        $config = Kohana::$config->load('update')->get('default');
        $scripts = Kohana::list_files($config['scripts_path']);
        array_walk($scripts, array($this, 'convert_file_array'));
        usort($scripts, array($this, 'sort_files_version'));
        return array_filter($scripts, array($this, 'is_update_file'));
    }
    
    private function get_complete_files()
    {
        $config = Kohana::$config->load('update')->get('default');
        $scripts = Kohana::list_files($config['scripts_path']);
        array_walk($scripts, array($this, 'convert_file_array'));
        usort($scripts, array($this, 'sort_files_version'));
        return array_filter($scripts, array($this, 'is_complete_file'));
    }
    
    private function is_update_file($script)
    {
        return ($script->is_update());
    }
    
    private function is_complete_file($script)
    {
        return ($script->is_complete());
    }
    
    private function log($message)
    {
        $this->events[] = $message;
        if (!$this->request->is_ajax()){
            echo $message . "\n";
        }
    }
    
    private function log_header()
    {
        if (!$this->request->is_ajax()){
            print(
               "<html>\n".
               "<head>\n".
               "\t<title>Database Update</title>\n".
               "</head>\n".
               "<body>\n".
               "\t<h1>Database Update</h1>\n".
               "\t<pre>\n"
            );
        }
    }
    
    private function log_footer()
    {
        if (!$this->request->is_ajax()){
            print(
               "\t</pre>\n".
               "</body>\n".
               "</html>\n"
            );
        }
    }
    
    private function execute_file($file)
    {
        $this->log('Running: '.$file->filename.' - ['.count($file->scripts).'] scripts');

        foreach ($file->scripts as $script) {
            try {
                $this->execute_script($script);
            }
            catch (Database_Exception $ex) {
                if ($this->break_on_error) {
                    break;
                }
            }
        }
        $this->update_db_version($file);
    }
    
    private function execute_script($script)
    {
        try {
            DB::query(NULL, $script)->execute();
        } 
        catch (Database_Exception $ex) {
            Log::instance()->add(Log::ERROR, 'Error Upgrading database: '.$ex->getMessage());
            $this->log('Error Upgrading database: '.$ex->getMessage());
            $this->log($script);
            throw $ex;
        }
    }
}