<?php

/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\Components\Filters;

/**
 * Select box filter.
 *
 * @package     Grido
 * @subpackage  Components\Filters
 * @author      Petr Bugyík
 */
class Select extends Filter
{
	/** @var bool */
	private $multiple = false;

    /**
     * @param \Grido\Grid $grid
     * @param string $name
     * @param string $label
     * @param array $items for select
     * @param bool $multiple
     */
    public function __construct($grid, $name, $label, ?array $items = NULL, /*bool */$multiple = false)
    {
		$this->multiple = $multiple;
        parent::__construct($grid, $name, $label);

        if ($items !== NULL) {
            $this->getControl()->setItems($items);
        }
    }

    /**
     * @return \Nette\Forms\Controls\SelectBox
     */
    protected function getFormControl()
    {
        return $this->multiple ? new \Nette\Forms\Controls\MultiSelectBox($this->label) : new \Nette\Forms\Controls\SelectBox($this->label);
    }
}
