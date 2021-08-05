<?php

namespace Grido\Components\Exports;

use Grido\Components\Columns\Column;
use Nette\Http\IResponse;

class PdfExport extends BaseExport
{

	protected function printData(): void
	{
		$columns = $this->grid[Column::ID]->getComponents();
		$header = [];
		$headerItems = $this->header ? $this->header : $columns;
		foreach ($headerItems as $column) {
			$header[] = $this->header ? $column : $column->getLabel();
		}

		$datasource = $this->grid->getData(false, false, false);
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
			unset($row);
		}

		// Tip: In template to make a new page use <pagebreak>
		$template = $this->getPresenter()->getTemplate();
		$template->setFile(__DIR__ . '/pdf_export.latte');
		$template->header = $header;
		$template->data = \Nette\Utils\ArrayHash::from($formattedData);
		$template->sums = \Nette\Utils\ArrayHash::from($sums);
		$template->columns = $columns;
//		dump($header, $row, $data, $columns, $template->data, $sums);die;
//		\Utils\Basic::downloadFile(null, 'test.html', false, $template->renderToString());

		$pdf = new \Joseki\Application\Responses\PdfResponse($template);

		// optional
//		$pdf->documentTitle = date("Y-m-d H:i") . " PDF export"; // creates filename
		$pdf->pageFormat = $this->options['pageFormat'] ?? count($columns) > 5 ? 'A4-L' : 'A4';
		$mpdf = $pdf->getMPDF();
		// Memory optim https://mpdf.github.io/troubleshooting/memory-problems.html
		// https://mpdf.github.io/reference/mpdf-variables/simpletables.html
//		$mpdf->simpleTables = true;
		// https://mpdf.github.io/reference/mpdf-variables/packtabledata.html
//		$mpdf->packTableData = true;
//		$mpdf->setFooter("|© www.PROJECT.sk|");
//		$pdf->outputDestination = $pdf::OUTPUT_DOWNLOAD;
//		$pdf->save = $pdf::OUTPUT_DOWNLOAD;
		echo $pdf->__toString();
	}


	protected function setHttpHeaders(IResponse $httpResponse, string $label): void
	{
		$encoding = 'utf-8';
		$httpResponse->setHeader('Content-Description', 'File Transfer');
		$httpResponse->setHeader('Content-Transfer-Encoding', 'binary');
		$httpResponse->setHeader('Content-Encoding', $encoding);
		$httpResponse->setHeader('Content-Type', "application/pdf; charset=$encoding");
		$httpResponse->setHeader('Content-Disposition', "attachment; filename=\"$label.pdf\"");
	}


}