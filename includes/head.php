<?php
// includes/head.php - Ortak HTML head elementi
?>
<!DOCTYPE html>
<html lang='tr'>

<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title><?= $page_title ?? 'DReklam - Site Takip Sistemi' ?></title>

    <!-- Tailwind CSS -->
    <script src='https://cdn.tailwindcss.com'></script>

    <!-- Google Fonts for FlashDesigner Theme -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>

    <!-- Custom CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
    <link href="assets/css/modern.css" rel="stylesheet">
    <link rel='stylesheet' href='assets/css/opera-fix.css'>


    <!-- Favicon -->
    <link rel='icon' type='image/png'
        href='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22></text></svg>'>

    <style>
        /* TAB FIX */
        #googlesheets-tab,
        #backup-tab {
            width: 100% !important;
            min-height: 400px !important;
        }
    </style>
</head>