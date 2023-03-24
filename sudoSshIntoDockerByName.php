<?php

$container_name_to_search_for = $argv[1];

$sdps_output = shell_exec('sudo docker ps');

$sdps_output_lines = explode("\n", $sdps_output);

foreach($sdps_output_lines as $line)
{
	if (strpos($line, $container_name_to_search_for) === false)
    {
        continue;
    }

    $words_in_line = explode(" ", $line);

    echo $words_in_line[0];
}

return null;
