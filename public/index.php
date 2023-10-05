<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

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


$app->run();
