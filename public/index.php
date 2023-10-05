<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("OGDP Export service.");
    return $response;
});

$app->get('/csv', function (Request $request, Response $response, $args) {
    $url = "https://dilrmp.gov.in/meityDashboard?level=1&scode=32";
    $options = [
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ],
        "http" => [
            "header" => "User-Agent: Mozilla/5.0"
        ]
    ];
    $context = stream_context_create($options);
    $json_data = file_get_contents($url, false, $context);
    $data = json_decode($json_data, true);

    // Create a stream for the response body
    $stream = fopen('php://memory', 'w+');

    // Add column headers
    fputcsv($stream, array_keys(reset($data)));

    // Add data rows
    foreach ($data as $row) {
        fputcsv($stream, $row);
    }

    // Return to the start of the stream
    rewind($stream);
    $csv = stream_get_contents($stream);
    fclose($stream);

    // Set the headers and body for the CSV download
    $response = $response
        ->withHeader('Content-Type', 'text/csv')
        ->withHeader('Content-Disposition', 'attachment; filename="your_file.csv"');
    $response->getBody()->write($csv);

    return $response;
});

$app->get('/xml', function (Request $request, Response $response, $args) {
    $url = "https://dilrmp.gov.in/meityDashboard?level=1&scode=32";
    $options = [
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ],
        "http" => [
            "header" => "User-Agent: Mozilla/5.0"
        ]
    ];
    $context = stream_context_create($options);
    $json_data = file_get_contents($url, false, $context);
    $data = json_decode($json_data, true);

    $xml = new SimpleXMLElement('<root/>');

    // Assuming $data is an associative array
    foreach ($data as $record) {
        $recordElement = $xml->addChild('record');  // Creating a new 'record' element for each record
        foreach ($record as $key => $value) {
            $recordElement->addChild($key, htmlspecialchars($value));  // Adding child elements under 'record'
        }
    }

    $xmlOutput = $xml->asXML();

    $response = $response
        ->withHeader('Content-Type', 'application/xml')
        ->withHeader('Content-Disposition', 'attachment; filename="your_file.xml"');
    $response->getBody()->write($xmlOutput);

    return $response;
});


$app->get('/xls', function (Request $request, Response $response, $args) {
    $url = "https://dilrmp.gov.in/meityDashboard?level=1&scode=32";
    $options = [
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ],
        "http" => [
            "header" => "User-Agent: Mozilla/5.0"
        ]
    ];
    $context = stream_context_create($options);
    $json_data = file_get_contents($url, false, $context);
    $data = json_decode($json_data, true);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $rowNumber = 1;
    foreach ($data as $row) {
        $columnLetter = 'A';
        foreach ($row as $cell) {
            $sheet->setCellValue($columnLetter.$rowNumber, $cell);
            $columnLetter++;
        }
        $rowNumber++;
    }

    $writer = new Xlsx($spreadsheet);

    // Create a temporary file
    $temp_file = tempnam(sys_get_temp_dir(), 'phpspreadsheet');
    $writer->save($temp_file);

    // Read the temporary file into a string
    $xlsData = file_get_contents($temp_file);
    unlink($temp_file);  // Delete the temporary file

    // Set the headers and body for the XLSX download
    $response = $response
        ->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->withHeader('Content-Disposition', 'attachment; filename="your_file.xlsx"');
    $response->getBody()->write($xlsData);

    return $response;
});

$app->get('/ods', function (Request $request, Response $response, $args) {
    $url = "https://dilrmp.gov.in/meityDashboard?level=1&scode=32";
    $options = [
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ],
        "http" => [
            "header" => "User-Agent: Mozilla/5.0"
        ]
    ];
    $context = stream_context_create($options);
    $json_data = file_get_contents($url, false, $context);
    $data = json_decode($json_data, true);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $rowNumber = 1;
    foreach ($data as $row) {
        $columnLetter = 'A';
        foreach ($row as $cell) {
            $sheet->setCellValue($columnLetter.$rowNumber, $cell);
            $columnLetter++;
        }
        $rowNumber++;
    }

    $writer = new Ods($spreadsheet);

    // Create a temporary file
    $temp_file = tempnam(sys_get_temp_dir(), 'phpspreadsheet');
    $writer->save($temp_file);

    // Read the temporary file into a string
    $odsData = file_get_contents($temp_file);
    unlink($temp_file);  // Delete the temporary file

    // Set the headers and body for the ODS download
    $response = $response
        ->withHeader('Content-Type', 'application/vnd.oasis.opendocument.spreadsheet')
        ->withHeader('Content-Disposition', 'attachment; filename="your_file.ods"');
    $response->getBody()->write($odsData);

    return $response;
});


$app->run();
