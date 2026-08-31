<?php
require_once __DIR__ . '/../src/bootstrap.php';

Auth::logout();
redirect('index.php');
