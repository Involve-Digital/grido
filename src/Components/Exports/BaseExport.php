<?php

namespace Grido\Components\Exports;

use Grido\Components\Component;
use Grido\Grid;
use Nette\Application\IResponse;
use Nette\Utils\Strings;

/**
 * Exporting data.
 *
 * @package     Grido
 * @subpackage  Components
 *
 * @property int $fetchLimit
 * @property-write array $header
 * @property-write callable $customData
 */
abstract class BaseExport extends Component implements IResponse
{
	const ID = 'export';


	/** @var int */
	protected $fetchLimit = 100000;

	/** @var array */
	protected $header = [];

	/** @var callable */
	protected $customData;

	/** @var ?string */
	private $title;

	/** @var ?string */
	private $filename;

	/** @var array */
	protected $options;


	public function __construct(string $label = null, string $filename = null, array $options = [])
	{
		$this->label = $label;
		$this->filename = $filename;
		$this->options = $options;

		$this->monitor('Grido\Grid');
	}


	protected function attached(\Nette\ComponentModel\IComponent $presenter): void
	{
		parent::attached($presenter);
		if ($presenter instanceof Grid) {
			$this->grid = $presenter;
		}
	}


	abstract protected function printData(): void;

	abstract protected function setHttpHeaders(\Nette\Http\IResponse $httpResponse, string $label): void;

	public function setTitle(string $title): self
	{
		$this->title = $title;
		return $this;
	}


	public function getTitle(): ?string
	{
		return $this->title;
	}


	/**
	 * Sets a limit which will be used in order to retrieve data from datasource.
	 */
	public function setFetchLimit(int $limit): self
	{
		$this->fetchLimit = (int) $limit;
		return $this;
	}


	public function getFetchLimit(): int
	{
		return $this->fetchLimit;
	}


	/**
	 * Sets a custom header of result CSV file (list of field names).
	 */
	public function setHeader(array $header): self
	{
		$this->header = $header;
		return $this;
	}


	/**
	 * Sets a callback to modify output data. This callback must return a list of items. (array) function($datasource)
	 * DEBUG? You probably need to comment lines started with $httpResponse->setHeader in Grido\Components\Export.php
	 */
	public function setCustomData(callable $callback): self
	{
		$this->customData = $callback;
		return $this;
	}


	/**
	 * @internal
	 */
	public function handleExport(): void
	{
		!empty($this->grid->onRegistered) && $this->grid->onRegistered($this->grid);
		$this->grid->presenter->sendResponse($this);
	}


	/*	 * ************************* interface \Nette\Application\IResponse ************************** */

	public function send(\Nette\Http\IRequest $httpRequest, \Nette\Http\IResponse $httpResponse): void
	{
		$label = $this->label ? ucfirst(Strings::webalize($this->label)) : ucfirst($this->grid->name);

		$this->setHttpHeaders($httpResponse, $this->filename ?: $label);

		print chr(0xEF) . chr(0xBB) . chr(0xBF); //UTF-8 BOM
		$this->printData();
	}


}