<?php

declare(strict_types=1);

namespace Baraja\Shop\Unit;


use Baraja\Doctrine\EntityManager;
use Baraja\Shop\Entity\Unit\Unit;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class UnitManager
{
	public function __construct(
		private EntityManager $entityManager,
	) {
	}


	public function createUnit(string $name, ?string $code, string $shortcut): Unit
	{
		$unit = new Unit($name, $code ?? $shortcut, $shortcut);
		$this->entityManager->persist($unit);
		$this->entityManager->flush();

		return $unit;
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getById(int $id): Unit
	{
		return $this->entityManager->getRepository(Unit::class)
			->createQueryBuilder('unit')
			->where('unit.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getSingleResult();
	}


	public function getDefaultUnit(bool $retryCall = false): Unit
	{
		static $cache;

		if ($cache === null) {
			try {
				$cache = $this->entityManager->getRepository(Unit::class)
					->createQueryBuilder('unit')
					->select('unit')
					->where('unit.default = true')
					->getQuery()
					->getSingleResult();
			} catch (NoResultException) {
				if ($retryCall === true) {
					throw new \LogicException(
						'V systému neexistuje žádná jednotka. Automatická instalace se nezdařila.',
					);
				}
				$this->installUnits();
				$cache = $this->getDefaultUnit(true);
			} catch (NonUniqueResultException) {
				$list = $this->getUnits();
				$isFirst = true;
				foreach ($list as $unit) {
					if ($isFirst === true) {
						$unit->setDefault(true);
						$cache = $unit;
						$isFirst = false;
					} else {
						$unit->setDefault(false);
					}
				}
				$this->entityManager->flush();
			}
		}

		return $cache;
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getUnitByShortcut(string $shortcut): Unit
	{
		return $this->entityManager->getRepository(Unit::class)
			->createQueryBuilder('u')
			->where('u.shortcut = :s')
			->setParameter('s', $shortcut)
			->getQuery()
			->getSingleResult();
	}


	/**
	 * @throws NoResultException|NonUniqueResultException
	 */
	public function getUnitByCode(string $code): Unit
	{
		return $this->entityManager->getRepository(Unit::class)
			->createQueryBuilder('u')
			->where('u.code = :code')
			->setParameter('code', $code)
			->getQuery()
			->getSingleResult();
	}


	/**
	 * @return array<int, Unit>
	 */
	public function getUnits(): array
	{
		static $cache;
		if ($cache === null) {
			$cache = $this->entityManager->getRepository(Unit::class)
				->createQueryBuilder('unit')
				->select('unit')
				->orderBy('unit.name', 'ASC')
				->getQuery()
				->getResult();
		}

		return $cache;
	}


	public function setDefaultUnit(Unit $unit): void
	{
		foreach ($this->getUnits() as $item) {
			if ($item->getId() === $unit->getId()) {
				$item->setDefault(true);
			} else {
				$item->setDefault(false);
			}
		}
		$this->entityManager->flush();
	}


	/**
	 * @return array<int, string>
	 */
	public function getUnitsForForm(): array
	{
		static $cache;
		if ($cache === null) {
			$cache = [];
			foreach ($this->getUnits() as $unit) {
				$cache[$unit->getId()] = $unit->getShortcut() . ' - ' . $unit->getName();
			}
		}

		return $cache;
	}


	public function removeUnit(Unit $unit): void
	{
		$this->entityManager->remove($unit);
		$this->entityManager->flush();
	}


	private function installUnits(): void
	{
		foreach (Unit::DEFAULT_LIST as $unitData) {
			$unit = new Unit($unitData[0], $unitData[1], $unitData[2]);
			if ($unitData[3] === 1) {
				$unit->setDefault(true);
			}
			$this->entityManager->persist($unit);
		}
		$this->entityManager->flush();
	}
}
