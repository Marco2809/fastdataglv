# Back Up Mysql Database phproject By oTTo
#!/bin/bash

NOW=$(date +"%m_%d_%Y")
FILE=/var/www/fastdata/DB-BACKUP/fd_ticket_$NOW
NAME=root
PASS=mysql1989
DB=fd_ticket


echo "Content-type: text/plain"
echo
echo "Tried to export file: "$FILE

#eliminare il commmento a seconda del risultato che si vuole ottenere
#dump non compresso
mysqldump --quote-names -u $NAME --password=$PASS $DB > $FILE.sql

#dump compresso con gzip
#mysqldump --quote-names -u $NAME --password=$PASS $DB| gzip -9 > $FILE.sql.gz

find /var/www/fastdata/DB-BACKUP/* -type f -mtime +5 -exec rm -R {} \;
