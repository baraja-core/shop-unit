<?php

declare(strict_types=1);


namespace MatiCore\Unit;


/**
 * Class UnitException
 * @package App\Model
 */
class UnitException extends \Exception
{

	/**
	 * @param Unit $unit
	 * @throws UnitException
	 */
	public static function canNotDelete(Unit $unit): void
	{
		throw new self('Jednotku ' . $unit->getName() . ' nelze odstranit, protože je používána');
	}

	/**
	 * @throws UnitException
	 */
	public static function noUnits(): void
	{
		throw new self('V systému neexistuje žádná jednotka. Automatická instalace se nezdařila.');
	}
}