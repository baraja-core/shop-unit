<?php

declare(strict_types=1);

namespace MatiCore\Unit;

/**
 * Interface UnitManagerAccessor
 * @package MatiCore\Unit
 */
interface UnitManagerAccessor
{

	/**
	 * @return UnitManager
	 */
	public function get(): UnitManager;

}