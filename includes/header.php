<?php
/**
 * Reusable <head> + opening body/app-shell markup for internal (logged-in) pages.
 * Expects $pageTitle to be set before including this file.
 */
$pageTitle = $pageTitle ?? 'TimeSync';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> · TimeSync</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<script>const BASE_URL = <?= json_encode(BASE_URL) ?>;</script>
<div id="toast-container"></div>
<div class="app-shell">
