<?php
require_once '../config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

initSession();
logoutStudent();
redirect(baseUrl('student/login.php'));
