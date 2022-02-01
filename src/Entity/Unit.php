<?php

declare(strict_types=1);

namespace Baraja\Shop\Entity\Unit;


use Baraja\Doctrine\Identifier\IdentifierUnsigned;
use Doctrine\ORM\Mapping as ORM;
use Nette\Utils\Strings;

#[ORM\Entity]
#[ORM\Table(name: 'shop__unit')]
class Unit
{
	use IdentifierUnsigned;

	public const DEFAULT_LIST = [
		['Kilometr', 'KM', 'km', 0],
		['Kilogram', 'KG', 'kg', 0],
		['Gram', 'G', 'g', 0],
		['Litr', 'L', 'l', 0],
		['Metr', 'M', 'm', 0],
		['Milimetr', 'MM', 'mm', 0],
		['Hodiny', 'HOD', 'hod', 0],
		['Kusy', 'KS', 'ks', 1],
	];

	#[ORM\Column(type: 'string', length: 6, unique: true)]
	private string $code;

	#[ORM\Column(type: 'string')]
	private string $name;

	#[ORM\Column(type: 'string', length: 6)]
	private string $shortcut;

	#[ORM\Column(name: 'is_default', type: 'boolean')]
	private bool $default = false;


	public function __construct(string $name, string $code, string $shortcut)
	{
		$this->setName($name);
		$this->setCode($code);
		$this->setShortcut($shortcut);
	}


	public function getCode(): string
	{
		return $this->code;
	}


	public function setCode(string $code): void
	{
		$this->code = Strings::upper(Strings::webalize($code));
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function setName(string $name): void
	{
		$this->name = $name;
	}


	public function getShortcut(): string
	{
		return $this->shortcut;
	}


	public function setShortcut(string $shortcut): void
	{
		$this->shortcut = $shortcut;
	}


	public function isDefault(): bool
	{
		return $this->default;
	}


	public function setDefault(bool $default): void
	{
		$this->default = $default;
	}
}
