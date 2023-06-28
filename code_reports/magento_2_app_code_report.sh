#!/bin/bash

# Define the directory to be searched
mapfile -t DIR < ./.directory_to_read
DIR_TO_READ=${DIR[0]}

mapfile -t OUTPUT_DIRECTORY_ARRAY < ./.output_directory
OUTPUT_DIRECTORY=${OUTPUT_DIRECTORY_ARRAY[0]}
DETAILED_REPORT_FILE="${OUTPUT_DIRECTORY}/text_pattern_detailed.txt"
SUMMARY_REPORT_FILE="${OUTPUT_DIRECTORY}/text_pattern_summary.txt"

# Define the patterns to be searched for
declare -A PATTERNS
PATTERNS[CUSTOM_MODULE]='<module'
PATTERNS[CUSTOM_EVENT_OBSERVER]='<observer'
PATTERNS[CUSTOM_PLUGIN]='<plugin'
PATTERNS[CUSTOM_DI_PREF]='<preference'
PATTERNS[CUSTOM_ATTRIBUTE]='>addAttribute('

declare -A COUNTS

echo "Detailed Report: " > ${DETAILED_REPORT_FILE}

# Loop over each pattern
for PATTERN_KEY in "${!PATTERNS[@]}"; do

    PATTERN=${PATTERNS[${PATTERN_KEY}]}

    # Initialize counters
    COUNT=0

    # If pattern is CUSTOM_MODULE, restrict search to etc/module.xml files
    if [ "${PATTERN_KEY}" = "CUSTOM_MODULE" ]; then
        while read -r FILE; do
            while read -r LINE; do
                echo "Found '${PATTERN_KEY}' in: ${FILE}:${LINE}" >> ${DETAILED_REPORT_FILE}
                ((COUNT++))
            done < <(grep -n "${PATTERN}" "${FILE}")
        done < <(find "${DIR_TO_READ}" -path '*/etc/module.xml')
    else
        # Search for the pattern and write to detailed report
        while read -r LINE; do
            echo "Found '${PATTERN_KEY}' in: ${LINE}" >> ${DETAILED_REPORT_FILE}
            ((COUNT++))
        done < <(grep -r -n "${PATTERN}" "${DIR_TO_READ}")
    fi

    # Store count for final summary
    COUNTS[${PATTERN_KEY}]=${COUNT}
done

# Write final summary
echo -e "Final Summary:" > ${SUMMARY_REPORT_FILE}
for PATTERN_KEY in "${!PATTERNS[@]}"; do
    echo "Found ${COUNTS[${PATTERN_KEY}]} instances of '${PATTERN_KEY}'." >> ${SUMMARY_REPORT_FILE}
done
