<?php

require __DIR__ . '/adminer.dist.php';

function adminer_object() {
    return new class() extends Adminer {
        public function login($login, $password) {
            return true;
        }
    };
}
