<?php
/**
 * This file is part of the Grido (http://grido.bugyik.cz)
 *
 * Copyright (c) 2011 Petr Bugyík (http://petr.bugyik.cz)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 */

namespace Grido\DataSources;

use Grido\Components\Filters\Condition;
use Grido\Exception;
use Nette;

/**
 * Dibi Fluent data source.
 *
 * @package     Grido
 * @subpackage  DataSources
 * @author      Petr Bugyík
 *
 * @property-read \Dibi\Fluent $fluent
 * @property-read int $limit
 * @property-read int $offset
 * @property-read int $count
 * @property-read array $data
 */
class DibiFluent implements IDataSource
{

	use Nette\SmartObject;
	/** @var \Dibi\Fluent */
	protected $fluent;

	/** @var int */
	protected $limit;

	/** @var int */
	protected $offset;


	public function __construct(\Dibi\Fluent $fluent)
	{
		$this->fluent = $fluent;
	}


	public function getFluent(): \Dibi\Fluent
	{
		return $this->fluent;
	}


	public function getLimit(): int
	{
		return $this->limit;
	}


	public function getOffset(): int
	{
		return $this->offset;
	}


	protected function makeWhere(Condition $condition, \Dibi\Fluent $fluent = null)
	{
		$fluent = $fluent === null ? $this->fluent : $fluent;

		if ($condition->callback) {
			call_user_func_array($condition->callback, [$condition->value, $fluent]);
		} else {
			call_user_func_array([$fluent, 'where'], $condition->__toArray('[', ']'));
		}
	}


	/*	 * ******************************** inline editation helpers *********************************** */

	/**
	 * Default callback used when an editable column has customRender.
	 * @param mixed $id
	 * @param string $idCol
	 * @return \Dibi\Row
	 */
	public function getRow($id, string $idCol): \Dibi\Row
	{
		$fluent = clone $this->fluent;
		return $fluent
				->where("%n = %s", $idCol, $id)
				->fetch();
	}


	/*	 * ********************************* interface IDataSource *********************************** */

	public function getCount(): int
	{
		$fluent = clone $this->fluent;
		return $fluent->count();
	}


	public function getData(): array
	{
		return $this->fluent->fetchAll($this->offset, $this->limit);
	}


	public function filter(array $conditions): void
	{
		foreach ($conditions as $condition) {
			$this->makeWhere($condition);
		}
	}


	public function limit(int $offset, int $limit): void
	{
		$this->offset = $offset;
		$this->limit = $limit;
	}


	public function sort(array $sorting): void
	{
		foreach ($sorting as $column => $sort) {
			$this->fluent->orderBy("%n", $column, $sort);
		}
	}


	/**
	 * @param mixed $column
	 * @param array $conditions
	 * @param int $limit
	 * @return array
	 * @throws Exception
	 */
	public function suggest($column, array $conditions, int $limit): array
	{
		$fluent = clone $this->fluent;
		if (is_string($column)) {
			$fluent->removeClause('SELECT')->select("DISTINCT %n", $column)->orderBy("%n", $column, 'ASC');
		}

		foreach ($conditions as $condition) {
			$this->makeWhere($condition, $fluent);
		}

		$items = [];
		$data = $fluent->fetchAll(0, $limit);
		foreach ($data as $row) {
			if (is_string($column)) {
				$value = (string) $row[$column];
			} elseif (is_callable($column)) {
				$value = (string) $column($row);
			} else {
				$type = gettype($column);
				throw new Exception("Column of suggestion must be string or callback, $type given.");
			}

			$items[$value] = \Latte\Runtime\Filters::escapeHtml($value);
		}

		is_callable($column) && sort($items);
		return array_values($items);
	}


}