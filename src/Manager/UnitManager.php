<?php

declare(strict_types=1);


namespace MatiCore\Unit;


use Baraja\Doctrine\EntityManager;
use Baraja\Doctrine\EntityManagerException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Nette\Utils\Strings;

/**
 * Class UnitManager
 * @package App\Model
 */
class UnitManager
{

	/**
	 * @var EntityManager
	 */
	private EntityManager $entityManager;

	/**
	 * UnitManager constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @param string $name
	 * @param string $shortcut
	 * @return Unit
	 * @throws EntityManagerException
	 */
	public function createUnit(string $name, string $shortcut): Unit
	{
		$code = Strings::upper(Strings::webalize($shortcut));
		$unit = new Unit($name, $code, $shortcut);

		$this->entityManager->persist($unit)->flush($unit);

		return $unit;
	}

	/**
	 * @param string $id
	 * @return Unit
	 * @throws NoResultException
	 * @throws NonUniqueResultException
	 */
	public function getById(string $id): Unit
	{
		return $this->entityManager->getRepository(Unit::class)
			->createQueryBuilder('unit')
			->select('unit')
			->where('unit.id = :id')
			->setParameter('id', $id)
			->getQuery()
			->getSingleResult();
	}

	/**
	 * @param bool $retryCall
	 * @return Unit
	 * @throws UnitException
	 */
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
			} catch (NoResultException $e) {
				if ($retryCall === true) {
					UnitException::noUnits();
				}

				$this->installUnits();
				$cache = $this->getDefaultUnit(true);
			} catch (NonUniqueResultException $e) {
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

				$this->entityManager->flush($list);
			}
		}

		return $cache;
	}

	/**
	 * @throws EntityManagerException
	 */
	public function installUnits(): void
	{
		$list = [
			['Kilometr', 'KM', 'km', 0],
			['Kilogram', 'KG', 'kg', 0],
			['Gram', 'G', 'g', 0],
			['Litr', 'L', 'l', 0],
			['Metr', 'M', 'm', 0],
			['Milimetr', 'MM', 'mm', 0],
			['Hodiny', 'HOD', 'hod', 0],
			['Kusy', 'KS', 'ks', 1],
		];

		$units = [];
		foreach ($list as $unitData) {
			$unit = new Unit($unitData[0], $unitData[1], $unitData[2]);
			if ($unitData[3] === 1) {
				$unit->setDefault(true);
			}

			$this->entityManager->persist($unit);

			$units[] = $unit;
		}

		$this->entityManager->flush($units);
	}

	/**
	 * @param string $shortCut
	 * @return Unit
	 * @throws NoResultException
	 * @throws NonUniqueResultException
	 */
	public function getUnitByShortcut(string $shortCut): Unit
	{
		return $this->entityManager->getRepository(Unit::class)
			->createQueryBuilder('u')
			->select('u')
			->where('u.shortcut = :s')
			->setParameter('s', $shortCut)
			->getQuery()
			->getSingleResult();
	}

	/**
	 * @param string $code
	 * @return Unit
	 * @throws NoResultException
	 * @throws NonUniqueResultException
	 */
	public function getUnitByCode(string $code): Unit
	{
		return $this->entityManager->getRepository(Unit::class)
			->createQueryBuilder('u')
			->select('u')
			->where('u.code = :code')
			->setParameter('code', $code)
			->getQuery()
			->getSingleResult();
	}

	/**
	 * @return Unit[]
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
					->getResult() ?? [];
		}

		return $cache;
	}

	/**
	 * @param Unit $unit
	 * @throws EntityManagerException
	 */
	public function setDefaultUnit(Unit $unit): void
	{
		$list = $this->getUnits();
		foreach ($list as $u) {
			if ($u->getId() === $unit->getId()) {
				$u->setDefault(true);
			} else {
				$u->setDefault(false);
			}
		}

		$this->entityManager->flush($list);
	}

	/**
	 * @return array
	 */
	public function getUnitsForForm(): array
	{
		static $cache;

		if ($cache === null) {
			$list = [];
			foreach ($this->getUnits() as $unit) {
				$list[$unit->getId()] = $unit->getShortcut() . ' - ' . $unit->getName();
			}
			$cache = $list;
		}

		return $cache;
	}

	/**
	 * @param Unit $unit
	 * @throws UnitException
	 */
	public function removeUnit(Unit $unit): void
	{
		try {
			$this->entityManager->remove($unit)->flush();
		} catch (EntityManagerException $e) {
			UnitException::canNotDelete($unit);
		}
	}

}