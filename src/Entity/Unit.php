<?php

declare(strict_types=1);


namespace MatiCore\Unit;


use Baraja\Doctrine\UUID\UuidIdentifier;
use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;

/**
 * Class Unit
 * @package App\Model
 * @ORM\Entity()
 * @ORM\Table(name="app__unit")
 */
class Unit
{

	use SmartObject;
	use UuidIdentifier;

	/**
	 * @var string
	 * @ORM\Column(type="string", unique=true)
	 */
	private string $code;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	private string $name;

	/**
	 * @var string
	 * @ORM\Column(type="string")
	 */
	private string $shortcut;

	/**
	 * @var bool
	 * @ORM\Column(type="boolean", name="is_default")
	 */
	private bool $default = false;

	/**
	 * Unit constructor.
	 * @param string $name
	 * @param string $code
	 * @param string $shortcut
	 */
	public function __construct(string $name, string $code, string $shortcut)
	{
		$this->name = $name;
		$this->code = $code;
		$this->shortcut = $shortcut;
	}

	/**
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->code;
	}

	/**
	 * @param string $code
	 */
	public function setCode(string $code): void
	{
		$this->code = $code;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name): void
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getShortcut(): string
	{
		return $this->shortcut;
	}

	/**
	 * @param string $shortcut
	 */
	public function setShortcut(string $shortcut): void
	{
		$this->shortcut = $shortcut;
	}

	/**
	 * @return bool
	 */
	public function isDefault(): bool
	{
		return $this->default;
	}

	/**
	 * @param bool $default
	 */
	public function setDefault(bool $default): void
	{
		$this->default = $default;
	}

}