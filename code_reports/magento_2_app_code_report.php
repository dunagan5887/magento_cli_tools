<?php
/**
 * magento_2_app_code_report.php
 *
 * Created On : 2023-06-27
 *
 */

// Define directories
$modulesDir = file_get_contents('./.directory_to_read');
$outputDir = file_get_contents('./.output_directory');

// Initialize arrays to hold results
$moduleResults = [];
$observerResults = [];
$pluginResults = [];
$preferenceResults = [];
$attributeResults = [];

foreach (glob($modulesDir . '*/*/etc/module.xml') as $module_filename) {
    // Load the XML file into a SimpleXML object
    $module_file_xml = simplexml_load_file($module_filename);

    // Find and process all module declarations
    foreach ($module_file_xml->xpath('//module') as $module) {
        $moduleResults[] = (string)$module['name'];
    }
}

$di_xml_files = array_merge(
    glob($modulesDir .  '*/*/etc/frontend/di.xml'),
    glob($modulesDir .  '*/*/etc/adminhtml/di.xml'),
    glob($modulesDir .  '*/*/etc/di.xml')
);

foreach ($di_xml_files as $filename) {
    // Load the XML file into a SimpleXML object
    $xml = simplexml_load_file($filename);

    // Find and process all plugin declarations
    foreach ($xml->xpath('//plugin') as $plugin) {
        $pluginResults[] = [(string)$plugin['name'], (string)$plugin['type']];
    }

    // Find and process all preference declarations
    foreach ($xml->xpath('//preference') as $preference) {
        $preferenceResults[] = [(string)$preference['for'], (string)$preference['type']];
    }
}

$event_config_files = array_merge(
    glob($modulesDir .  '*/*/etc/frontend/events.xml'),
    glob($modulesDir .  '*/*/etc/adminhtml/events.xml'),
    glob($modulesDir .  '*/*/etc/events.xml')
);

foreach ($event_config_files as $filename) {

    // Load the XML file into a SimpleXML object
    $xml = simplexml_load_file($filename);

    // Find and process all observer declarations
    foreach ($xml->xpath('//observer') as $observer) {
        $observerResults[] = [(string)$observer['name'], (string)$observer['instance']];
    }
}

// Iterate over all PHP files in the directory for addAttribute instances
foreach (glob($modulesDir . '*/*/Setup/*.php') as $filename) {
    // Load the PHP file into a string
    $phpCode = file_get_contents($filename);

    // Find and process all addAttribute calls
    preg_match_all('/>addAttribute\s*\(\s*([^,]+)\s*,\s*\'([^\']+)\'/', $phpCode, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $attributeResults[] = [$match[1], $match[2]];
    }
}

// Generate detailed report
file_put_contents($outputDir . 'active_nodes_summary.txt', "Found " . count($moduleResults) . " modules, " . count($observerResults) . " observers, " . count($pluginResults) . " plugins, " . count($preferenceResults) . " preferences, " . count($attributeResults) . " attributes.");

// Generate CSV report
$csv = fopen($outputDir . 'active_nodes_app_code_inspection.csv', 'w');
fputcsv($csv, []);
fputcsv($csv, ['Custom Modules Found']);
fputcsv($csv, []);
foreach($moduleResults as $result) {
    fputcsv($csv, [$result]);
}
fputcsv($csv, []);
fputcsv($csv, ['Custom Event Observers Found']);
fputcsv($csv, []);
foreach($observerResults as $result) {
    fputcsv($csv, $result);
}
fputcsv($csv, []);
fputcsv($csv, ['Custom Plugins Found']);
fputcsv($csv, []);
foreach($pluginResults as $result) {
    fputcsv($csv, $result);
}
fputcsv($csv, []);
fputcsv($csv, ['Custom Preferences Found']);
fputcsv($csv, []);
foreach($preferenceResults as $result) {
    fputcsv($csv, $result);
}
fputcsv($csv, []);
fputcsv($csv, ['Custom Attributes Found']);
fputcsv($csv, []);
foreach($attributeResults as $result) {
    fputcsv($csv, $result);
}

fclose($csv);

