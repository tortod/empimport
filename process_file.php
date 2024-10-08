<?php
session_start();
require 'vendor/autoload.php'; // Load PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $fileTmpPath = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];

        // Only allow Excel files
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['xls', 'xlsx'];

        if (in_array($fileExtension, $allowedExtensions)) {
            try {
                // Load the uploaded Excel file
                $spreadsheet = IOFactory::load($fileTmpPath);
                $txtFilePath = tempnam(sys_get_temp_dir(), 'csv_') . '.txt'; // Temporary file with .txt extension
                
                // Open a temporary TXT file for writing
                $txtFile = fopen($txtFilePath, 'w');
                $tabsWithData = []; // Track tabs that have data
                
                // Iterate over all sheets
                foreach ($spreadsheet->getAllSheets() as $index => $sheet) {
                    $sheetTitle = $sheet->getTitle();
                    $rowCount = 0;
					if ($_POST['Conversion_type'] == 'Employee') { // If Conversion type is Employee
						$skipLines = $index === 0 ? 3 : 4; // First tab: 3 lines header; other tabs: 4 lines header
					}else{ //If Conversion type is List
						$skipLines = 4;
					}
                    $maxColumns = 0; // To determine the last non-empty column in the header
                    $dataAdded = false;

                    // Determine the last non-empty column in the header
                    for ($i = 1; $i <= $skipLines; $i++) {
                        $headerRow = $sheet->getRowIterator($i)->current();
                        $cellIterator = $headerRow->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(false); // Loop through all cells
                        foreach ($cellIterator as $cell) {
                            $cellValue = $cell->getValue();
                            if (!is_null($cellValue) && trim($cellValue) !== '') {
                                $columnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
                                if ($columnIndex > $maxColumns) {
                                    $maxColumns = $columnIndex; // Update max columns based on the farthest non-empty column
                                }
                            }
                        }
                    }

                    // Iterate over each row in the sheet
                    foreach ($sheet->getRowIterator() as $row) {
                        $rowCount++;
                        if ($rowCount <= $skipLines) {
                            continue; // Skip header lines
                        }

                        $cellIterator = $row->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(false); // Loop through all cells in the row
                        $rowData = [];
                        $colCount = 0;

                        // Collect row data, but limit to $maxColumns
                        foreach ($cellIterator as $cell) {
                            $colCount++;
                            if ($colCount > $maxColumns) {
                                break; // Ignore cells beyond the last header column
                            }
                            $cellValue = $cell->getValue();
                            // Add the trimmed value (removing spaces) to the rowData array
                            $rowData[] = is_null($cellValue) ? '' : trim($cellValue);
                        }

                        // Check if row is empty (ignore rows with only empty or whitespace cells)
                        if (array_filter($rowData, fn($value) => $value !== '')) {
                            fputcsv($txtFile, $rowData); // Write row to TXT as comma-separated values (CSV)
                            $dataAdded = true;
                        }
                    }

                    // If data was added, track the tab name
                    if ($dataAdded) {
                        $tabsWithData[] = $sheetTitle;
                    }
                }

                fclose($txtFile);

                // Store the tabs with data in the session
                $_SESSION['tabs_with_data'] = $tabsWithData;

                // Serve the file directly for download
                header('Content-Type: text/plain');
				// The name of the extracted file is the same as the uploaded one with .txt
				$fileNameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);
				header("Content-Disposition: attachment; filename=".$fileNameWithoutExt.".txt");
                readfile($txtFilePath);

                // Remove the temporary file after download
                unlink($txtFilePath);

                // We do not output anything else here, just download the file.
                exit();

            } catch (Exception $e) {
                echo "Error processing file: " . $e->getMessage();
            }
        } else {
            echo "Please upload a valid Excel file.";
        }
    } else {
        echo "No file uploaded or there was an error during upload.";
    }
}
