<?php
session_start();
session_destroy();

header('Location: ../modules/list.php');
exit;
