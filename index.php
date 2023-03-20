<?php
$data = [
    [
        'illustration' => 'https://i.imgur.com/UYiroysl.jpg',
    ],
    [
        'illustration' => 'https://i.imgur.com/UPrs1EWl.jpg',
    ],
    [
        'illustration' => 'https://i.imgur.com/MABUbpDl.jpg',
    ],
    [
        'illustration' => 'https://i.imgur.com/KZsmUi2l.jpg',
    ],
    [
        'illustration' => 'https://i.imgur.com/2nCt3Sbl.jpg',
    ],
    [
        'illustration' => 'https://i.imgur.com/lceHsT6l.jpg',
    ],
];
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);