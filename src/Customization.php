<?php
/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido;

use DirectoryIterator;
use Nette;

/**
 * Customization.
 *
 * @package     Grido
 * @author      Petr Bugyík
 *
 * @property string|array $buttonClass
 * @property string|array $iconClass
 */
class Customization
{

	use Nette\SmartObject;
	const TEMPLATE_DEFAULT = 'default';
	const TEMPLATE_BOOTSTRAP = 'bootstrap';


	/** @var Grid */
	protected $grid;

	/** @var string|array */
	protected $buttonClass;

	/** @var string|array */
	protected $iconClass;

	/** @var array */
	protected $templateFiles = [];


	public function __construct(Grid $grid)
	{
		$this->grid = $grid;
	}


	/**
	 * @param string|array $class
	 * @return Customization
	 */
	public function setButtonClass($class): self
	{
		$this->buttonClass = $class;
		return $this;
	}


	/**
	 * @param string|array $class
	 * @return Customization
	 */
	public function setIconClass($class): self
	{
		$this->iconClass = $class;
		return $this;
	}


	public function getButtonClass(): ?string
	{
		return is_array($this->buttonClass) ? implode(' ', $this->buttonClass) : $this->buttonClass;
	}


	public function getIconClass(string $icon = null): string
	{
		if ($icon === null) {
			$class = $this->iconClass;
		} else {
			$this->iconClass = (array) $this->iconClass;
			$classes = [];
			foreach ($this->iconClass as $fontClass) {
				$classes[] = "{$fontClass} {$fontClass}-{$icon}";
			}
			$class = implode(' ', $classes);
		}

		return $class;
	}


	public function getTemplateFiles(): array
	{
		if (empty($this->templateFiles)) {
			foreach (new DirectoryIterator(__DIR__ . '/templates') as $file) {
				if ($file->isFile()) {
					$this->templateFiles[$file->getBasename('.latte')] = realpath($file->getPathname());
				}
			}
		}

		return $this->templateFiles;
	}


	public function useTemplateDefault(): self
	{
		$this->grid->setTemplateFile($this->getTemplateFiles()[self::TEMPLATE_DEFAULT]);
		return $this;
	}


	public function useTemplateBootstrap(): self
	{
		$this->grid->setTemplateFile($this->getTemplateFiles()[self::TEMPLATE_BOOTSTRAP]);
		return $this;
	}


}