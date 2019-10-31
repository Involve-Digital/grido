<?php
/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\Components;

use Grido\Exception;
use Grido\Grid;
use Grido\Helpers;

/**
 * Operation with one or more rows.
 *
 * @package     Grido
 * @subpackage  Components
 * @author      Petr Bugyík
 *
 * @property-read string $primaryKey
 * @method void onSubmit(string $operation, array $ids) Description
 */
class Operation extends Component
{
	const ID = 'operations';


	/** @var array callback on operation submit */
	public $onSubmit;

	/** @var string */
	protected $primaryKey;


	public function __construct(Grid $grid, array $operations, callable $onSubmit)
	{
		$this->grid = $grid;
		$grid->addComponent($this, self::ID);

		$grid['form'][$grid::BUTTONS]->addSubmit(self::ID, 'OK')
			->onClick[] = [$this, 'handleOperations'];

		$grid['form']->addContainer(self::ID)
			->addSelect(self::ID, 'Selected', $operations)
			->setPrompt('Grido.Selected');

		$grid->onRender[] = function(Grid $grid) {
			$this->addCheckers($grid['form'][Operation::ID]);
		};

		$this->onSubmit[] = $onSubmit;
	}


	/**
	 * Set client side confirm for operation.
	 */
	public function setConfirm(string $operation, string $message): Operation
	{
		$message = $this->translate($message);
		$this->grid->onRender[] = function(Grid $grid) use ($operation, $message) {
			$grid['form'][Operation::ID][Operation::ID]->getControlPrototype()->setAttribute(
				"data-grido-confirm-$operation", $message
			);
		};

		return $this;
	}


	public function setPrimaryKey(string $primaryKey): Operation
	{
		$this->primaryKey = $primaryKey;
		return $this;
	}


	/*	 * ******************************************************************************************* */

	public function getPrimaryKey(): string
	{
		if ($this->primaryKey === null) {
			$this->primaryKey = $this->grid->primaryKey;
		}

		return $this->primaryKey;
	}


	/*	 * ******************************************************************************************* */

	/**
	 * @internal
	 */
	public function handleOperations(\Nette\Forms\Controls\SubmitButton $button): void
	{
		$grid = $this->getGrid();
		!empty($grid->onRegistered) && $grid->onRegistered($grid);
		$form = $button->getForm();
		$this->addCheckers($form[self::ID]);

		$values = $form[self::ID]->values;
		if (empty($values[self::ID])) {
			$httpData = $form->getHttpData();
			if (!empty($httpData[self::ID][self::ID]) && $operation = $httpData[self::ID][self::ID]) {
				$grid->__triggerUserNotice("Operation with name '$operation' does not exist.");
			}

			$grid->reload();
		}

		$ids = [];
		$operation = $values[self::ID];
		unset($values[self::ID]);

		foreach ($values as $key => $val) {
			if ($val) {
				$ids[] = $key;
			}
		}

		$this->onSubmit($operation, $ids);
		$grid->page = 1;

		if ($this->presenter->isAjax()) {
			$grid['form'][self::ID][self::ID]->setValue(null);
			$grid->getData(true, false);
		}

		$grid->reload();
	}


	/**
	 * @param \Nette\Forms\Container $container
	 * @throws Exception
	 * @internal
	 */
	public function addCheckers(\Nette\Forms\Container $container): void
	{
		$items = $this->grid->getData();
		$primaryKey = $this->getPrimaryKey();

		foreach ($items as $item) {
			try {
				$primaryValue = $this->grid->getProperty($item, $primaryKey);
				if (!isset($container[$primaryValue])) {
					$container->addCheckbox(Helpers::formatColumnName($primaryValue))
						->controlPrototype->title = $primaryValue;
				}
			} catch (\Exception $e) {
				throw new Exception(
					'You should define some else primary key via $grid->setPrimaryKey() ' .
					"because currently defined '$primaryKey' key is not suitable for operation feature."
				);
			}
		}
	}


}