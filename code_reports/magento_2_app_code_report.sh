#!/bin/bash

# Define the directory to be searched
DIR="DIRECTORY_TO_SEARCH"

# Define the patterns to be searched for
declare -A PATTERNS
PATTERNS[CUSTOM_MODULE]='<module'
PATTERNS[CUSTOM_EVENT_OBSERVER]='<observer'
PATTERNS[CUSTOM_PLUGIN]='<plugin'
PATTERNS[CUSTOM_DI_PREF]='<preference'
PATTERNS[CUSTOM_ATTRIBUTE]='>addAttribute('

declare -A COUNTS

echo "Detailed Report: " > /etc/magento_cli_tools/output/app_code_report/detailed.txt

# Loop over each pattern
for PATTERN_KEY in "${!PATTERNS[@]}"; do

    PATTERN=${PATTERNS[${PATTERN_KEY}]}

    # Initialize counters
    COUNT=0

    # If pattern is CUSTOM_MODULE, restrict search to etc/module.xml files
    if [ "${PATTERN_KEY}" = "CUSTOM_MODULE" ]; then
        while read -r FILE; do
            while read -r LINE; do
                echo "Found '${PATTERN_KEY}' in: ${FILE}:${LINE}" >> /etc/magento_cli_tools/output/app_code_report/detailed.txt
                ((COUNT++))
            done < <(grep -n "${PATTERN}" "${FILE}")
        done < <(find "${DIR}" -path '*/etc/module.xml')
    else
        # Search for the pattern and write to detailed report
        while read -r LINE; do
            echo "Found '${PATTERN_KEY}' in: ${LINE}" >> /etc/magento_cli_tools/output/app_code_report/detailed.txt
            ((COUNT++))
        done < <(grep -r -n "${PATTERN}" "${DIR}")
    fi

    # Store count for final summary
    COUNTS[${PATTERN_KEY}]=${COUNT}
done

# Write final summary
echo -e "Final Summary:" > /etc/magento_cli_tools/output/app_code_report/summary.txt
for PATTERN_KEY in "${!PATTERNS[@]}"; do
    echo "Found ${COUNTS[${PATTERN_KEY}]} instances of '${PATTERN_KEY}'." >> /etc/magento_cli_tools/output/app_code_report/summary.txt
done
