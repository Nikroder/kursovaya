<?php

if (!defined("WHMCS")) {
    die("Access denied");
}

function diskstatus_config() {
    return [
        "name" => "Disk Status Module",
        "description" => "Module to display and update disk status information.",
        "version" => "1.0",
        "author" => "Nikroder",
        "language" => "english",
    ];
}

function diskstatus_activate() {
    return [
        'status' => 'success',
        'description' => 'The Disk Status module has been activated successfully.',
    ];
}

function diskstatus_deactivate() {
    return [
        'status' => 'success',
        'description' => 'The Disk Status module has been deactivated successfully.',
    ];
}

function diskstatus_output($vars) {
    include __DIR__ . '/diskstatus_output.php';
}
