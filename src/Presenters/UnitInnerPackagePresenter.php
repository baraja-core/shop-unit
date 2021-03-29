<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use Baraja\Doctrine\EntityManagerException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use MatiCore\Form\FormFactory;
use MatiCore\Unit\Unit;
use MatiCore\Unit\UnitException;
use MatiCore\Unit\UnitManager;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Tracy\Debugger;

/**
 * Class UnitInnerPackagePresenter
 * @package App\AdminModule\Presenters
 */
class UnitInnerPackagePresenter extends BaseAdminPresenter
{

	/**
	 * @var UnitManager
	 * @inject
	 */
	public UnitManager $unitManager;

	/**
	 * @var FormFactory
	 * @inject
	 */
	public FormFactory $formFactory;

	/**
	 * @var Unit|null
	 */
	private Unit|null $editedUnit;

	public function actionDefault(): void
	{
		$this->template->units = $this->unitManager->getUnits();
	}

	/**
	 * @param string $id
	 * @throws AbortException
	 */
	public function actionDetail(string $id): void
	{
		try{
			$this->editedUnit = $this->unitManager->getById($id);

			$this->template->unit = $this->editedUnit;
		}catch (NonUniqueResultException|NoResultException $e){
			$this->flashMessage('Požadovaná jednotka neexistuje', 'error');
			$this->redirect('default');
		}
	}

	public function handleInstall(): void
	{
		try {
			$this->unitManager->installUnits();

			$this->flashMessage('Jednotky byly úspěšně nainstalovány.', 'success');
		} catch (EntityManagerException $e) {
			Debugger::log($e);
			$this->flashMessage('Jednotky se nepodařilo nainstalovat.', 'error');
		}

		$this->redirect('default');
	}

	/**
	 * @param string $id
	 */
	public function handleDefault(string $id): void
	{
		try {
			$unit = $this->unitManager->getById($id);
			$this->unitManager->setDefaultUnit($unit);
			$this->flashMessage('Jednotka ' . $unit->getName() . ' byla nastavena jako výchozí.', 'success');
		} catch (NoResultException|NonUniqueResultException $e) {
			$this->flashMessage('Požadovaná jednotka neexistuje.', 'error');
		} catch (EntityManagerException $e) {
			Debugger::log($e);
			$this->flashMessage('Defaultni jednotku se nepodařilo nastavit.', 'error');
		}

		$this->redrawControl('flashes');
		$this->redrawControl('unit-list');
	}

	/**
	 * @param string $id
	 * @throws AbortException
	 */
	public function handleDelete(string $id): void
	{
		try {
			$unit = $this->unitManager->getById($id);
			$this->unitManager->removeUnit($unit);
			$this->flashMessage('Jednotka ' . $unit->getName() . ' byla odstraněna.', 'info');
		} catch (NoResultException|NonUniqueResultException $e) {
			$this->flashMessage('Požadovaná jednotka neexistuje.', 'error');
		} catch (UnitException $e) {
			Debugger::log($e);
			$this->flashMessage('Jednotku nelze odstranit, protože je využívána.', 'error');
		}

		$this->redirect('default');
	}

	/**
	 * @return Form
	 */
	public function createComponentCreateForm(): Form
	{
		$form = $this->formFactory->create();

		$form->addText('name', 'Název')
			->setRequired('Zadejte název jednotky');

		$form->addText('shortcut', 'Zkratka')
			->setRequired('Zadejte zkratku');

		$form->addSubmit('submit', 'Uložit');

		/**
		 * @param Form $form
		 * @param ArrayHash $values
		 */
		$form->onSuccess[] = function (Form $form, ArrayHash $values): void{
			try{
				$unit = $this->unitManager->createUnit($values->name, $values->shortcut);

				$this->flashMessage('Jednotka byla úspěšně vytvořena.', 'success');
			}catch (EntityManagerException $e){
				$this->flashMessage('Chyba při ukládání do databáze.', 'error');
			}

			$this->redirect('default');
		};

		return $form;
	}

	/**
	 * @return Form
	 * @throws UnitException
	 */
	public function createComponentEditForm(): Form
	{
		if($this->editedUnit === null){
			throw new UnitException('Unit for edit is null!');
		}

		$form = $this->formFactory->create();

		$form->addText('name', 'Název')
			->setDefaultValue($this->editedUnit->getName())
			->setRequired('Zadejte název jednotky');

		$form->addText('shortcut', 'Zkratka')
			->setDefaultValue($this->editedUnit->getShortcut())
			->setRequired('Zadejte zkratku');

		$form->addSubmit('submit', 'Save');

		/**
		 * @param Form $form
		 * @param ArrayHash $values
		 */
		$form->onSuccess[] = function (Form $form, ArrayHash $values): void{
			try{
				$this->editedUnit->setName($values->name);
				$this->editedUnit->setShortcut($values->shortcut);

				$this->entityManager->flush($this->editedUnit);
				$this->flashMessage('Změny byly úspěšně uloženy.', 'success');
			}catch (EntityManagerException $e){
				$this->flashMessage('Chyba při ukládání do databáze.', 'error');
			}

			$this->redrawControl('flashes');
		};

		return $form;
	}
}