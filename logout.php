<?php
require_once __DIR__ . '/includes/functions.php';
startSession();
session_destroy();
redirect('/login.php');
