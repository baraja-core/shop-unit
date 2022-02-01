<?php

declare(strict_types=1);

namespace Baraja\Shop\Unit;


use Nette\DI\CompilerExtension;

final class UnitExtension extends CompilerExtension
{
	// MatiCore\Unit: %appDir%/../vendor/mati-core/unit/src/Entity

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('unitManager'))
			->setFactory(UnitManager::class);

		$builder->addAccessorDefinition($this->prefix('unitManagerAccessor'))
			->setImplement(UnitManagerAccessor::class);
	}
}
