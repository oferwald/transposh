#!/bin/bash

DB=wordpress
USER=root
echo
echo "Dropping transposh entries and tables from wordpress database"


SQL="Delete FROM \`wp_options\` WHERE \`option_name\` LIKE '%transposh%'; DROP TABLE wp_translations; DROP TABLE wp_translations_log;"


mysql $DB  --user=$USER --password -e "$SQL"

echo done !!!

