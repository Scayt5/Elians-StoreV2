<?php
// 1. INCLUIR CONEXIÓN A LA BASE DE DATOS
require_once 'conexion.php';

// 2. CONFIGURACIÓN DEL NEGOCIO
$config = [
    // --- URL BASE PARA RASTREO REMOTO ---
    // En local usa: 'http://localhost:8080'
    // Con Ngrok cambia a: 'https://tu-enlace.ngrok-free.app'
    // En local para el cliente
    'base_url' => 'https://unfavourably-nontemperate-georgiana.ngrok-free.dev',

    'empresa' => [
        'nombre' => 'ELIANS STORE PERÚ',
        'ruc' => '10720157867',
        'direccion' => 'Av. Sinchi Roca Mz 11 Lt 14 (Esq. Col. Peruano Americano)',
        'referencia' => 'Urb. El Pinar - Comas',
        'telefono' => '993516112'
    ],
    'rutas' => [
        'logo' => 'logo.jpg'
    ]
];
?>