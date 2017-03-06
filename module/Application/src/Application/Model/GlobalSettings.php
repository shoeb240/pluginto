<?php

namespace Application\Model;

class GlobalSettings
{
    public function getBaseUrl()
    {
        return rtrim("http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']), '/');
    }
    
    public function getHostUrl()
    {
        return "http://" . $_SERVER['HTTP_HOST'];
    }
    
    public function getConsumerKey()
    {
        return 'qyprdgxaJNDjs1uQqrnOXejpe3289T';
    }
    
    public function getConsumerSecret()
    {
        return 'qm435WLfbgk0kY56dgYOf3OSJqSfdnEHo4hrsJY2';
    }
}