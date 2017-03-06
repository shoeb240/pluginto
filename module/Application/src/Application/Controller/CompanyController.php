<?php
namespace Application\Controller;

use Application\Controller\ApplicationActionController;
use Zend\View\Model\ViewModel;
use Application\Entity\Company;

class CompanyController extends ApplicationActionController
{
    public function indexAction()
    {
        $companies = $this->getObjectManager()->getRepository('\Application\Entity\Company')->findAll();

        return new ViewModel(array('companies' => $companies));
    }

    public function addAction()
    {
        if ($this->request->isPost()) {
            $company = new Company();
            $company->setVatId($this->getRequest()->getPost('vat_id'));
            $company->setCompanyName($this->getRequest()->getPost('company_name'));
            $company->setDisplayName($this->getRequest()->getPost('display_name'));
            $company->setName($this->getRequest()->getPost('name'));
            $company->setSurname($this->getRequest()->getPost('surname'));
            $company->setAddress($this->getRequest()->getPost('address'));
            $company->setCity($this->getRequest()->getPost('city'));
            $company->setPostcode($this->getRequest()->getPost('postcode'));
            $company->setCountry($this->getRequest()->getPost('country'));

            $this->getObjectManager()->persist($company);
            $this->getObjectManager()->flush();
            $newId = $company->getId();

            return $this->redirect()->toRoute('application/default', array('controller' => 'company', 'action' => 'index'));
        }
        return new ViewModel();
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $company = $this->getObjectManager()->find('\Application\Entity\Company', $id);

        if ($this->request->isPost()) {
            $company->setVatId($this->getRequest()->getPost('vat_id'));
            $company->setCompanyName($this->getRequest()->getPost('company_name'));
            $company->setDisplayName($this->getRequest()->getPost('display_name'));
            $company->setName($this->getRequest()->getPost('name'));
            $company->setSurname($this->getRequest()->getPost('surname'));
            $company->setAddress($this->getRequest()->getPost('address'));
            $company->setCity($this->getRequest()->getPost('city'));
            $company->setPostcode($this->getRequest()->getPost('postcode'));
            $company->setCountry($this->getRequest()->getPost('country'));

            $this->getObjectManager()->persist($company);
            $this->getObjectManager()->flush();

            return $this->redirect()->toRoute('application/default', array('controller' => 'company', 'action' => 'index'));
        }

        return new ViewModel(array('company' => $company));
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $company = $this->getObjectManager()->find('\Application\Entity\Company', $id);

        if ($this->request->isPost()) {
            $this->getObjectManager()->remove($company);
            $this->getObjectManager()->flush();

            return $this->redirect()->toRoute('application/default', array('controller' => 'company', 'action' => 'index'));
        }

        return new ViewModel(array('company' => $company));
    }

}
