<?php

require __DIR__ . '/adminer.dist.php';

function adminer_object() {
    return new class extends Adminer {
        function login($login, $password) {
            return true;
        }
    };
}
