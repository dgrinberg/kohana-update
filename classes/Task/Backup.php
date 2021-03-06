<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Task to backup and email the database
 *
 * @package    DBUpdate
 * @category   Helpers
 * @author     Veraida Pty Ltd
 * @copyright  (c) 2014 Veraida Pty Ltd
 * @license    http://kohanaframework.org/license
 */
class Task_Backup extends Minion_Task
{
    private $_config;
    
    /**
    * A set of config options that this task accepts
    * @var array
    */
    protected $_options = array(
        'env' => NULL
    );
            
    public function build_validation(Validation $validation)
    {
        return parent::build_validation($validation)
                ->rule('env', 'not_empty');
    }
    
    /**
     * Back ups the database (MySQL)
     *
     * @return null
     */
    protected function _execute(array $params)
    {
        $env = $params['env'];
        
        $this->_config = Kohana::$config->load('update')->get($env);
        $db_config = Kohana::$config->load('database')->get($env);

        $backup_file = $this->backup_database(
                $db_config['connection']['conf'],
                $db_config['connection']['database']);
        
        if ($this->_config['backup']['send_email'] == true) {
            $this->send_email($backup_file);
        }
    }
    
    private function backup_database($db_conf, $db_name)
    {
        $backupfile = $this->_config['backup']['backup_path'] . $db_name . '-' . date("YmdHis") . '.sql.gz';
        echo("mysqldump --defaults-extra-file=$db_conf $db_name | gzip > $backupfile");
        system("mysqldump --defaults-extra-file=$db_conf $db_name | gzip > $backupfile");
        
        return $backupfile;
    }
    
    private function send_email($backup_file)
    {
        $email = Email::factory(
            $this->_config['backup']['email_subject'],
            $this->_config['backup']['email_body'])
                ->to(explode(',', $this->_config['backup']['email_to']))
                ->from($this->_config['backup']['email_from']);
        $email->attach_file($backup_file);
        $email->send();
    }
}
