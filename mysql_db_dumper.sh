#!/bin/bash

# This dumps your databases one by one into mysql_dump_YYYY-MM-DD so it's easy to import to a new server.
# wget https://raw.githubusercontent.com/orbisius/server-tools/master/mysql_db_dumper.sh
# chmod 755 ./mysql_db_dumper.sh
# Svetoslav (Slavi) Marinov | http://orbisius.com

# Enter your own admin 
DB_USER="root"
DB_PASS="YOUR_MYSQL_ROOT_PASS"


DB_DUMP_DIR=mysql_dump_`date +%Y-%m-%d`
ALL_DBS_FILE=$DB_DUMP_DIR/databases.txt
GZIP="$(which gzip)"
MYSQL="$(which mysql)"
MYSQL_DUMP="$(which mysqldump)"

if [ ! -d "$DB_DUMP_DIR" ]; then
    echo Creating $DB_DUMP_DIR
    mkdir -p $DB_DUMP_DIR
fi

rm -f $ALL_DBS_FILE

# Dump all dbs first in a txt file so we can iterate one by one
echo Dumping all db names in a $ALL_DBS_FILE file.
$MYSQL --user=$DB_USER --password=$DB_PASS -Ns -e "SHOW DATABASES" > $ALL_DBS_FILE

# Dump each db separately in own file & gzip to the max.
for i in `cat $ALL_DBS_FILE` ; do
     SQL_OUTPUT_FILE=$DB_DUMP_DIR/$i.sql.gz
    echo Dumping $i in $SQL_OUTPUT_FILE;
    $MYSQL_DUMP --opt --user=$DB_USER --password=$DB_PASS --single-transaction --hex-blob --complete-insert --default-character-set=utf8 $i | $GZIP -9 > $SQL_OUTPUT_FILE;
done

