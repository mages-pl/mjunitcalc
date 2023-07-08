<?php 
include_once '../../config/config.inc.php';
include_once './mjunitcalc.php';

$unit = new Mjunitcalc();

$unit->cronUnit();

echo "complete cron unit";