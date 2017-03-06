<?php
namespace Input\Model;

use Application\Model\BaseModel;

class Log extends BaseModel
{
    private $_methodName;
    private $_dateTime;
    private $_entityId;
    private $_messageContent;

    /**
     * Construct.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_methodName = '';
        $this->_dateTime = date('Y-m-d H:i:s');
        $this->_entityId = '';
        $this->_messageContent = '';
    }

    /**
     * sets logging method name
     *
     * @param string $methodName  The logging method name
     *
     * @return void
     */
    public function setMethodName($methodName)
    {
        $this->_methodName = $methodName;
    }

    /**
     * sets log date-time
     *
     * @return void
     */
    public function setDateTime()
    {
        $this->_dateTime = date('Y-m-d H:i:s');
    }

    /**
     * sets log entityId
     *
     * @param string $entityId  The log entityId
     *
     * @return void
     */
    public function setEntityId($entityId)
    {
        $this->_entityId = $entityId;
    }

    /**
     * sets log message
     *
     * @param string $subject  The subject of the message
     * @param string $message  The message content
     *
     * @return void
     */
    public function setMessage($subject, $message)
    {
        $this->_messageContent .= "/*" . $subject . ": " . $message . "*/\n";
    }

    /**
     * writes log
     *
     * @param string $logLevel  The level of the current log
     *
     * @return void
     */
    public function writeLog($logLevel)
    {
        $logFilePath = realpath(dirname(__FILE__) . '/../../../../../') . '/logs';
        
        $allowedLogLevel = 'debug';
        $logLevelArr = array('debug', 'info', 'warning', 'error');
        
        $currentLevelValue = array_search($logLevel, $logLevelArr);
        $allowedLevelValue = array_search($allowedLogLevel, $logLevelArr);

        $content = "/*----------------------------------------------------------*/\n" .
                   "/*Date & Time : " . $this->_dateTime . "*/\n" .
                   "/*Log Level: " . $logLevel . "*/\n";
        if (!empty($this->_methodName)) {
            $content .= "/*Method: " . $this->_methodName . "*/\n";
        }
        if (!empty($this->_userId)) {
            $content .= "/*User Id: " . $this->_userId . "*/\n";
        }
        if (!empty($this->_entityId)) {
            $content .= "/*Entity Id: " . $this->_entityId . "*/\n";
        }
        $content .= $this->_messageContent;

        if ($allowedLevelValue <= $currentLevelValue) {
            if ( !file_exists($logFilePath) ) {
                mkdir($logFilePath, 0755, true);
            }
            if (($handle = fopen($logFilePath . '/log_' . date("Y_m_d") . '.txt', "a"))) {
                fwrite($handle, $content);
                fclose($handle);
            }
        }
        
        $this->_messageContent = '';
    }

    
}
