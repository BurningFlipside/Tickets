<?php
require_once('Autoload.php');

class TicketSystemSettings extends Singleton
{
    protected $cache;
    protected $datatable;

    protected function __construct()
    {
        $this->cache = array();
        $dataset = DataSetFactory::get_data_set('tickets');
        $this->datatable = $dataset['Variables'];
    }

    public function getVariable($var_name, $default=false)
    {
        if(isset($this->cache[$var_name]))
        {
            return $this->cache[$var_name];
        }
        $vars = $this->datatable->read(new \Data\Filter("name eq '$var_name'"));
        if($vars === false || !isset($vars))
        {
            return $default;
        }
        $this->cache[$var_name] = $vars[0]['value'];
        return $this->cache[$var_name];
    }

    public function setVariable($var_name, $value)
    {
        $this->cache[$var_name] = $value;
        return $this->datatable->update(new \Data\Filter("name eq '$var_name'"), array('value'=>$value));
    }

    public function isTestMode()
    {
        return $this->getVariable('test_mode') === '1';
    }
}
?>
