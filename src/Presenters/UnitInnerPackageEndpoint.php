<?php

declare(strict_types=1);

namespace Baraja\Shop\Unit;


use Baraja\Doctrine\EntityManager;
use Baraja\Doctrine\EntityManagerException;
use Baraja\Shop\Entity\Unit\Unit;
use Baraja\StructuredApi\BaseEndpoint;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Tracy\Debugger;

class UnitInnerPackageEndpoint extends BaseEndpoint
{
	private Unit|null $editedUnit;


	public function __construct(
		private UnitManager $unitManager,
		private EntityManager $entityManager,
	) {
	}


	public function actionDefault(): void
	{
		$this->sendJson(
			[
				'units' => $this->unitManager->getUnits(),
			],
		);
	}


	/**
	 * @param string $id
	 * @throws AbortException
	 */
	public function actionDetail(string $id): void
	{
		try {
			$this->editedUnit = $this->unitManager->getById($id);

			$this->template->unit = $this->editedUnit;
		} catch (NonUniqueResultException | NoResultException $e) {
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


	public function handleDefault(string $id): void
	{
		try {
			$unit = $this->unitManager->getById($id);
			$this->unitManager->setDefaultUnit($unit);
			$this->flashMessage('Jednotka ' . $unit->getName() . ' byla nastavena jako výchozí.', 'success');
		} catch (NoResultException | NonUniqueResultException $e) {
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
		} catch (NoResultException | NonUniqueResultException $e) {
			$this->flashMessage('Požadovaná jednotka neexistuje.', 'error');
		} catch (UnitException $e) {
			Debugger::log($e);
			$this->flashMessage('Jednotku nelze odstranit, protože je využívána.', 'error');
		}

		$this->redirect('default');
	}


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
		$form->onSuccess[] = function (Form $form, ArrayHash $values): void {
			try {
				$unit = $this->unitManager->createUnit($values->name, $values->shortcut);

				$this->flashMessage('Jednotka byla úspěšně vytvořena.', 'success');
			} catch (EntityManagerException $e) {
				$this->flashMessage('Chyba při ukládání do databáze.', 'error');
			}

			$this->redirect('default');
		};

		return $form;
	}


	public function createComponentEditForm(): Form
	{
		if ($this->editedUnit === null) {
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
		$form->onSuccess[] = function (Form $form, ArrayHash $values): void {
			try {
				$this->editedUnit->setName($values->name);
				$this->editedUnit->setShortcut($values->shortcut);

				$this->entityManager->flush($this->editedUnit);
				$this->flashMessage('Změny byly úspěšně uloženy.', 'success');
			} catch (EntityManagerException $e) {
				$this->flashMessage('Chyba při ukládání do databáze.', 'error');
			}

			$this->redrawControl('flashes');
		};

		return $form;
	}


	public function createComponentUnitTable(string $name): MatiDataGrid
	{
		$grid = new MatiDataGrid($this, $name);

		$grid->setDataSource(
			$this->entityManager->getRepository(Unit::class)
				->createQueryBuilder('unit')
				->select('unit')
				->orderBy('unit.name', 'ASC'),
		);

		$grid->addColumnText('code', 'Kód')
			->setFitContent();

		$grid->addColumnText('name', 'Název')
			->setRenderer(
				function (Unit $unit): string {
					$link = $this->link('detail', ['id' => $unit->getId()]);

					return '<a href="' . $link . '">' . $unit->getName() . '</a>';
				},
			)
			->setTemplateEscaping(false);

		$grid->addColumnText('shortcut', 'Jednotka')
			->setFitContent();

		$grid->addAction('default', 'Default')
			->setRenderer(
				function (Unit $unit): string {
					$link = $this->link('default!', ['id' => $unit->getId()]);

					if ($unit->isDefault() === true) {
						return '<a href="#" class="btn btn-xs btn-success ajax"><i class="fas fa-home fa-fw"></i></a>';
					}

					return '<a href="' . $link . '" class="btn btn-xs btn-outline-secondary ajax"><i class="fas fa-minus fa-fw"></i></a>';
				},
			)
			->setTemplateEscaping(false);

		$grid->addAction('edit', 'Upravit')
			->setRenderer(
				function (Unit $unit): string {
					$link = $this->link('detail', ['id' => $unit->getId()]);

					return '<a href="' . $link . '" class="btn btn-xs btn-warning"><i class="fas fa-pen fa-fw"></i></a>';
				},
			)
			->setTemplateEscaping(false);

		$grid->addAction('delete', 'Smazat')
			->setRenderer(
				function (Unit $unit): string {
					$link = $this->link('delete!', ['id' => $unit->getId()]);

					return '<a href="' . $link . '" class="btn btn-xs btn-danger" onclick="return confirm(\''
						. $this->translator->translate('cms.main.deleteConfirm')
						. '\');"><i class="fas fa-trash fa-fw"></i></a>';
				},
			)
			->setTemplateEscaping(false);

		return $grid;
	}
}
