<?php
require_once __DIR__.'/../includes/sesion.php';
header('Location: '.(($_SESSION['usuario']['origen']??'')==='registrarAdmins'?'admin.php':'tienda.php'));exit;
