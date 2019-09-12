<?php

namespace Grido\Components\Exports;

use Grido\Components\Columns\Column;
use Nette\Http\IResponse;

class PdfExport extends BaseExport
{

	/**
	 * @return void
	 */
	protected function printData()
	{
		$columns = $this->grid[Column::ID]->getComponents();
		$header = [];
		$headerItems = $this->header ? $this->header : $columns;
		foreach ($headerItems as $column) {
			$header[] = $this->header ? $column : $column->getLabel();
		}

		$datasource = $this->grid->getData(FALSE, FALSE, FALSE);
		$iterations = ceil($datasource->getCount() / $this->fetchLimit);
		$formattedData = [];
		$sums = [];
		for ($i = 0; $i < $iterations; $i++) {
			$datasource->limit($i * $this->fetchLimit, $this->fetchLimit);
			$data = $this->customData ? call_user_func_array($this->customData, [$datasource]) : $datasource->getData();

			foreach ($data as $items) {
				$row = [];

				$columns = $this->customData ? $items : $columns;

				foreach ($columns as $columnName => $column) {
					$row[$columnName] = $this->customData ? $column : $column->renderExport($items);
					if ($column instanceof \Ciki\Grido\ColumnNumber && $column->getCalculateSum()) {
						if (!isset($sums[$columnName])) {
							$sums[$columnName] = 0;
						}
//						dump($row[$columnName], $column->getValueForSumCalculation($items), $column->getValue($items));
//						$sums[$columnName] += $column->getValueForSumCalculation($items);
						$sums[$columnName] += $row[$columnName] * 100; // => cents
					}
				}
				$formattedData[] = $row;
			}
		}

		// Tip: In template to make a new page use <pagebreak>
		$template = $this->getPresenter()->getTemplate();
		$template->setFile(__DIR__ . '/pdf_export.latte');
		$template->header = $header;
		$template->data = \Nette\Utils\ArrayHash::from($formattedData);
		$template->sums = \Nette\Utils\ArrayHash::from($sums);
		$template->columns = $columns;
//		dump($header, $row, $data, $columns, $template->data, $sums);die;

		$pdf = new \Joseki\Application\Responses\PdfResponse($template);

		// optional
//		$pdf->documentTitle = date("Y-m-d H:i") . " PDF export"; // creates filename
		$pdf->pageFormat = $this->options['pageFormat'] ?? count($columns) > 5 ? 'A4-L' : 'A4';
		$pdf->getMPDF()->setFooter("|© www.kartamesta.sk|");
//		$pdf->outputDestination = $pdf::OUTPUT_DOWNLOAD;
//		$pdf->save = $pdf::OUTPUT_DOWNLOAD;
		echo $pdf->__toString();
	}


	/**
	 * @param IResponse $httpResponse
	 * @param string $label
	 */
	protected function setHttpHeaders(IResponse $httpResponse, $label)
	{
		$encoding = 'utf-8';
		$httpResponse->setHeader('Content-Description', 'File Transfer');
		$httpResponse->setHeader('Content-Transfer-Encoding', 'binary');
		$httpResponse->setHeader('Content-Encoding', $encoding);
		$httpResponse->setHeader('Content-Type', "application/pdf; charset=$encoding");
		$httpResponse->setHeader('Content-Disposition', "attachment; filename=\"$label.pdf\"");
	}


}