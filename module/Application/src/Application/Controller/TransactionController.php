<?php
namespace Application\Controller;

use Application\Controller\ApplicationActionController;
use Zend\View\Model\ViewModel;


class TransactionController extends ApplicationActionController
{
    public function indexAction()
    {
        $transactions = $this->getObjectManager()->getRepository('\Application\Entity\Transaction')->findAll();

        return new ViewModel(array('transactions' => $transactions));
    }

    public function addAction()
    {
        if ($this->request->isPost()) {
            $amount = new \Application\Entity\Amount();
            $amount->setValue($this->getRequest()->getPost('amount'));
            $amount->setCurrency($this->getRequest()->getPost('currency'));
            
            $transaction = new \Application\Entity\Transaction();
            $transaction->setCompanyId($this->getRequest()->getPost('company_id'));
            $transaction->setAccountId($this->getRequest()->getPost('account_id'));
            $transaction->setTaxId($this->getRequest()->getPost('tax_id'));
            $transaction->setAmount($amount);
            $transaction->setDatetime(new \DateTime("now"));

            $this->getObjectManager()->persist($transaction);
            $this->getObjectManager()->flush();
            $newId = $transaction->getId();

            return $this->redirect()->toRoute('application/default', array('controller' => 'transaction', 'action' => 'index'));
        }

        $params = array();
        $companies = $this->getObjectManager()->getRepository('\Application\Entity\Company')->findAll();
        $params['companies'] = $companies;

        $accounts = $this->getObjectManager()->getRepository('\Application\Entity\Account')->findAll();
        $params['accounts'] = $accounts;

        $taxes = $this->getObjectManager()->getRepository('\Application\Entity\TaxCode')->findAll();
        $params['taxes'] = $taxes;

        return new ViewModel($params);
    }

    public function editAction()
    {
        $params = array();

        $id = (int) $this->params()->fromRoute('id', 0);
        $transaction = $this->getObjectManager()->find('\Application\Entity\Transaction', $id);
        $params['transaction'] = $transaction;

        if ($this->request->isPost()) {
            $amount = new \Application\Entity\Amount();
            $amount->setValue($this->getRequest()->getPost('amount'));
            $amount->setCurrency($this->getRequest()->getPost('currency'));

            $transaction->setCompanyId($this->getRequest()->getPost('company_id'));
            $transaction->setAccountId($this->getRequest()->getPost('account_id'));
            $transaction->setTaxId($this->getRequest()->getPost('tax_id'));
            $transaction->setAmount($amount);
            $transaction->setDatetime(new \DateTime("now"));

            $this->getObjectManager()->persist($transaction);
            $this->getObjectManager()->flush();

            return $this->redirect()->toRoute('application/default', array('controller' => 'transaction', 'action' => 'index'));
        }

        $companies = $this->getObjectManager()->getRepository('\Application\Entity\Company')->findAll();
        $params['companies'] = $companies;

        $accounts = $this->getObjectManager()->getRepository('\Application\Entity\Account')->findAll();
        $params['accounts'] = $accounts;

        $taxes = $this->getObjectManager()->getRepository('\Application\Entity\TaxCode')->findAll();
        $params['taxes'] = $taxes;

        return new ViewModel($params);
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $transaction = $this->getObjectManager()->find('\Application\Entity\Transaction', $id);

        if ($this->request->isPost()) {
            $this->getObjectManager()->remove($transaction);
            $this->getObjectManager()->flush();

            return $this->redirect()->toRoute('application/default', array('controller' => 'transaction', 'action' => 'index'));
        }

        return new ViewModel(array('transaction' => $transaction));
    }
    
}
