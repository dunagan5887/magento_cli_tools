<?php
/**
 * magento_1_app_code_report.php
 *
 * Created On : 2023-06-28
 *
 */

// Define directories
$modulesDir = file_get_contents('./.directory_to_read_m1');
$outputDir = file_get_contents('./.output_directory');

// Initialize arrays to hold results
$moduleResults = [];
$observerResults = [];
$rewriteResults = [];
// TODO Database tables created

$etc_config_files = array_merge(
    glob($modulesDir . '/local/*/*/etc/config.xml'),
    [] //glob($modulesDir . '/community/*/*/etc/config.xml')
);

foreach ($etc_config_files as $etc_config_filename) {
    // Load the XML file into a SimpleXML object
    $etc_config_file_xml = simplexml_load_file($etc_config_filename);

    // Find and process all module declarations
    foreach ($etc_config_file_xml->xpath('/config/modules') as $modulesNode) {
        $nodeChildren = $modulesNode->children();
        foreach($nodeChildren as $module_name => $moduleNode)
        {
            $moduleResults[] = $module_name;
        }
    }

    // global, frontend, adminhtml, crontab
    $event_xpaths_to_check = [
        '/config/global/events',
        '/config/frontend/events',
        '/config/adminhtml/events',
        '/config/crontab/events'
    ];

    foreach($event_xpaths_to_check as $event_xpath) {
        foreach ($etc_config_file_xml->xpath($event_xpath) as $eventsNode) {
            foreach($eventsNode->children() as $event_name => $eventChildNode) {
                foreach($eventChildNode->xpath('observers') as $observerNodeArrayElement) {
                    foreach($observerNodeArrayElement->children() as $observer_name => $observerNode) {
                        $observer_class = (string)$observerNode->class;
                        $observer_method = (string)$observerNode->method;
                        $observerResults[] = [$event_name, $observer_name, $observer_class, $observer_method];
                    }
                }
            }
        }
    }

    foreach ($etc_config_file_xml->xpath('//rewrite') as $rewriteNode) {
        $rewriteResults[] = [(string)$rewriteNode->children()];
    }
}

// Iterate over all PHP files in the directory for addAttribute instances
foreach (glob($modulesDir . '/local/*/*/sql/*/*.php') as $filename) {
    // Load the PHP file into a string
    $phpCode = file_get_contents($filename);

    // Find and process all addAttribute calls
    preg_match_all('/>addAttribute\s*\(\s*([^,]+)\s*,\s*\'([^\']+)\'/', $phpCode, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $attributeResults[] = [$match[1], $match[2]];
    }
}

// Generate detailed report
file_put_contents($outputDir . 'm1_active_nodes_summary.txt', "Found "
    . count($moduleResults) . " modules, " . count($observerResults) . " observers, "
    . count($rewriteResults) . " plugins, " . count($attributeResults) . " preferences, ");

// Generate CSV report
$csv = fopen($outputDir . 'm1_active_nodes_app_code_inspection.csv', 'w');
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
fputcsv($csv, ['Custom Rewrites Found']);
fputcsv($csv, []);
foreach($rewriteResults as $result) {
    fputcsv($csv, $result);
}
fputcsv($csv, []);
fputcsv($csv, ['Custom Attributes Found']);
fputcsv($csv, []);
foreach($attributeResults as $result) {
    fputcsv($csv, $result);
}

fclose($csv);

