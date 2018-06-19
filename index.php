<?php
require 'vendor/autoload.php';

$run = function () {
    printVector(pm4Migration(pm4FilterTablesWithAppUid(query(connection("information_schema"),
                                                           "SELECT distinct TABLE_NAME as name
        FROM `COLUMNS`
        WHERE `TABLE_SCHEMA` = 'test' AND (`COLUMN_NAME` = 'PRO_UID' OR `COLUMN_NAME` = 'PRJ_UID')", PDO::FETCH_COLUMN))));
};

function pm4FilterTablesWithAppUid($vector)
{
    $res = [];
    $tablesWithId = query(
        connection("information_schema"),
                   "select distinct TABLE_NAME, COLUMN_NAME FROM COLUMNS WHERE TABLE_SCHEMA='test' AND (COLUMN_NAME = 'APP_UID')"
        , PDO::FETCH_ASSOC);
    $tablesWI = array2vector($tablesWithId, 'TABLE_NAME');
    foreach ($vector as $table) {
        $i = array_search($table, $tablesWI);
        if ($i !== false) {
            $res[] = $table;
        }
    }
    return $res;
}

function pm4FilterTablesWithoutAppUid($vector)
{
    $res = [];
    $exclude = pm4FilterTablesWithAppUid($vector);
    foreach ($vector as $v) {
        if (!in_array($v, $exclude)) {
            $res[] = $v;
        }
    }
    return $res;
}

function pm4Migration($vector)
{
    $res = [];
    foreach ($vector as $table) {
        $res[] = "        //".str_replace('_',' ',$table)."\n"
            . "        Schema::table('$table', function(Blueprint \$table) {
            \$table->integer('PRO_ID')->nullable();
            \$table->foreign('PRO_ID')->references('PRO_ID')->on('PROCESS')->onDelete('RESTRICT');
        });
";
    }
    return $res;
}
$run();
