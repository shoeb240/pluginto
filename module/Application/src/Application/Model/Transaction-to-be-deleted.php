<?php

namespace Application\Model;

use Input\Model\Transaction as TransactionModel;

class Transaction
{
    /*public function getTransactionById($id)
    {
        $model = new TransactionModel($this->getServiceLocator());
        return $model->getEntityById($id);
    }
    
    public function createTransaction($data, $host)
    {
        $data = json_encode($data);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $host . '/input/transaction',
            CURLOPT_HTTPHEADER => array('Content-type: application/json',
                                        'Accept: application/json'),
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => 1,
        ));
        $resp = curl_exec($curl);
        $transaction_json = json_decode($resp);
        $transaction = (array) $transaction_json->data;
        curl_close($curl);
        
        return $transaction;
    }*/
   
}