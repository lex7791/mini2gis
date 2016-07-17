<?php

ini_set('display_errors','off');

// ядро
require 'system/app.php';
require 'system/config.php';

app::init([
    'org.id',
    'org' => 'org.name',
    'org.building.id',
    'org.building',
    'org.rubric.id',
    'org.rubric',
    'org.coord',
    'building' => 'building.all',
    'rubric' => 'rubric.all',

]);
