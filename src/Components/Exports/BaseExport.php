<?php

namespace Grido\Components\Exports;

use Grido\Components\Component;
use Grido\Grid;
use Nette\Application\IResponse;
use Nette\Utils\Strings;
use OutOfRangeException;

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

	/** @type string */
	const ENCODING_UTF8 = 'UTF-8';
	const ENCODING_UTF16LE = 'UTF-16LE';


	/** @var int */
	protected $fetchLimit = 10000;

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

	/** @var string */
	protected $encoding;


	public function __construct(?string $label = null, ?string $filename = null, array $options = [])
	{
		$allowedEncoding = [
			self::ENCODING_UTF8,
			self::ENCODING_UTF16LE,
		];
		if (isset($options['encoding']) && !in_array($options['encoding'], $allowedEncoding, true)) {
			throw new OutOfRangeException("Encoding option must be one of " . join(',', $allowedEncoding));
		}

		$this->label = $label;
		$this->filename = $filename;
		$this->options = $options;
		$this->encoding = $options['encoding'] ?? self::ENCODING_UTF8;

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
		// need to handle too much data to be exported in PDF already here
		// before pdf headers are sent and memory exceeds as well
		if ($this instanceof PdfExport) {
			$dataCount = $datasource = $this->grid->getData(false, false, false)->getCount();
			$maxDataCount4pdf = 5000; // bulgarian constant
			if ($dataCount > $maxDataCount4pdf) {
				$this->grid->presenter->flashMessage('Zvolené dáta na PDF export sú príliš veľké. Zvoľte prosím menej dát použitím filtra alebo zvoľte iný typ exportu (napr. CSV).', 'error');
				$this->redirect('this');
			}
		}
		!empty($this->grid->onRegistered) && $this->grid->onRegistered($this->grid);
		$this->grid->presenter->sendResponse($this);
	}


	/*	 * ************************* interface \Nette\Application\IResponse ************************** */

	public function send(\Nette\Http\IRequest $httpRequest, \Nette\Http\IResponse $httpResponse): void
	{
		set_time_limit(0);
		$label = $this->label ? ucfirst(Strings::webalize($this->label)) : ucfirst($this->grid->name);

		$this->setHttpHeaders($httpResponse, $this->filename ?: $label);

		switch ($this->encoding) {
			case self::ENCODING_UTF8:
				print chr(0xEF) . chr(0xBB) . chr(0xBF); // BOM
				break;

			case self::ENCODING_UTF16LE:
				print chr(0xFF) . chr(0xFE); // BOM
				break;

			default:
				throw new OutOfRangeException("Encoding $this->encoding is not supported!");
				break;
		}
		$this->printData();
	}


}